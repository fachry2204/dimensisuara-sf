# Contract System Restructuring — Implementation Plan

**Goal:** Restructure the contract system to use a unified owner morph, add `statement` as a contract type with sequential document numbering, and make the architecture extensible for future contract types.
**Architecture:** Rename `request_type`/`request_id` → `owner_type`/`owner_id` morph. Add `statement` type with its own independent numbering counter. Statement contract created during `adminApprove` alongside publishing contracts. NUON stays generate-only.
**Tech Stack:** Laravel (PHP), MySQL, Blade
**Depth:** Standard
**TDD Mode:** Relaxed (no existing test coverage for contract system)
**Brainstorm:** [2026-03-05-contract-system-restructuring](file:///g:/Project2/cms-soundfresh-id/docs/brainstorms/2026-03-05-contract-system-restructuring-brainstorm.md)

---

## Acceptance Criteria

- [ ] `contracts` table uses `owner_type`/`owner_id` morph instead of `request_type`/`request_id`
- [ ] Contract model `request()` renamed to `owner()` morph relation
- [ ] `statement` type supported in contract creation and validation
- [ ] Statement gets its own `prettyDocNum` format (e.g., `001/SP-PUB/MSV/III/2026`)
- [ ] Admin approval form accepts statement doc number (number, month, year)
- [ ] Statement contract created during `adminApprove` with owner = Publishing
- [ ] Statement PDF generation can receive the document number from its contract record
- [ ] All existing aggregator and publishing contract functionality remains intact
- [ ] NUON stays generate-only (no changes)

---

## Tasks

### Task 1: Database Migration — Rename Morph Columns

**Files:**
- Create: `database/migrations/2026_03_05_XXXXXX_rename_contract_morph_columns.php`

**What to do:**
Create a migration that renames `request_type` → `owner_type` and `request_id` → `owner_id` in the `contracts` table. Also update the index.

```php
Schema::table('contracts', function (Blueprint $table) {
    // Drop old index
    $table->dropIndex('contracts_request_type_request_id_index');
    
    // Rename columns
    $table->renameColumn('request_type', 'owner_type');
    $table->renameColumn('request_id', 'owner_id');
});

Schema::table('contracts', function (Blueprint $table) {
    // Add new index
    $table->index(['owner_type', 'owner_id'], 'contracts_owner_type_owner_id_index');
});
```

> [!IMPORTANT]
> The rename must be in a separate `Schema::table` call from adding the new index due to MySQL/doctrine limitations.

---

### Task 2: Update Contract Model

**Files:**
- Modify: `app/Models/Contract.php`

**What to do:**

1. Update `$fillable` — replace `request_type`, `request_id` with `owner_type`, `owner_id`
2. Rename `request()` method to `owner()` — keep it as `morphTo()`

```php
protected $fillable = [
    'type',
    'year',
    'month',
    'number',
    'docnum',
    'owner_type',
    'owner_id',
    'profileid',
    'sort',
];

public function owner(): MorphTo
{
    return $this->morphTo();
}
```

---

### Task 3: Update Signee Model

**Files:**
- Modify: `app/Models/Publishing/Signee.php`

**What to do:**

Update the `contract()` relation — change the morph name from `request` to `owner`:

```php
public function contract()
{
    return $this->morphOne(Contract::class, 'owner');
}
```

---

### Task 4: Add Publishing → Statement Contract Relation

**Files:**
- Modify: `app/Models/Publishing/Publishing.php`

**What to do:**

Add a `statementContract()` relation so we can access the statement's contract record (separate from the existing `statement()` which points to `publishing_documents`):

```php
public function statementContract()
{
    return $this->morphOne(Contract::class, 'owner');
}
```

---

### Task 5: Update ContractService — Validation & DocNum

**Files:**
- Modify: `app/Services/ContractService.php`

**What to do:**

1. In `store()` — add `statement` to the supported types array
2. In `prettyDocNum()` — add `statement` case with format `XXX/SP-PUB/MSV/MONTH/YEAR`
3. In `generatePublishingStatementPdf()` — accept a `[DOCNUM]` field from contract if available

**5a. Update `store()` validation (line 35):**
```php
if (!in_array($type, ['aggregator', 'publishing', 'statement'])) {
    throw new UnsupportedTypeException($type, ['aggregator', 'publishing', 'statement']);
}
```

**5b. Add `statement` case to `prettyDocNum()` (after line 511):**
```php
case 'statement':
    return str_pad($number, 3, '0', STR_PAD_LEFT) . '/' . 'SP-PUB' . '/MSV/' . $romans[$month] . '/' . $year;
    break;
```

> [!NOTE]
> The format `SP-PUB` is a placeholder — user should confirm the desired prefix. Adjust to actual business format.

**5c. Update `generatePublishingStatementPdf()` (line 212):**

Change the method signature to optionally accept a Contract (for the doc number):
```php
public static function generatePublishingStatementPdf(Publishing $publishing, ?Contract $contract = null)
```

Add `DOCNUM` to the fields array:
```php
$fields = [
    'DOCNUM' => $contract ? $contract->docnum : '-',
    'DATE' => self::prettyDate(date('Y-m-d')),
    'TITLE' => $publishing->title,
];
```

---

### Task 6: Update Statement HTML Template

**Files:**
- Modify: `resources/contract/publishing_statement.html`

**What to do:**

Add a `[DOCNUM]` placeholder to the statement template header. Currently the template starts with "SURAT PERNYATAAN" (line 69). Add the document number below it:

```html
<p style="text-align:center; margin-bottom: 15px"><strong><u>SURAT PERNYATAAN</u></strong></p>
<p style="text-align:center; margin-bottom: 15px">No. [DOCNUM]</p>
```

---

### Task 7: Update PublishingService.adminApprove

**Files:**
- Modify: `app/Services/Publishing/PublishingService.php`

**What to do:**

Update `adminApprove()` to:
1. Accept statement number/month/year from `$data`
2. Rename morph `request()` → `owner()` when associating contracts
3. Create a statement contract with owner = Publishing

```php
public function adminApprove(Publishing $publishing, $data)
{
    if($publishing->status != 'review') {
        throw new InvalidRequestStatusException($publishing->status, ['review']);
    }

    return DB::transaction(function () use ($publishing, $data) {
        
        // Loop through signees — create publishing contracts
        for ($i = 0; $i < count($data['signeeid']); $i++) {
            $signee = Signee::findOrFail($data['signeeid'][$i]);

            $payload = [
                'profileid' => $publishing->requestor->id,
                'type'      => 'publishing',
                'number'    => $data['number'][$i],
                'month'     => $data['month'][$i],
                'year'      => $data['year'][$i]
            ];

            $contract = $this->contractService->store($payload);
            $contract->owner()->associate($signee);  // renamed from request()
            $contract->save();
        }

        // Create statement contract
        $statementPayload = [
            'profileid' => $publishing->requestor->id,
            'type'      => 'statement',
            'number'    => $data['statement_number'],
            'month'     => $data['statement_month'],
            'year'      => $data['statement_year']
        ];
        $statementContract = $this->contractService->store($statementPayload);
        $statementContract->owner()->associate($publishing);
        $statementContract->save();

        // Update status
        $publishing->status = 'completed';
        $publishing->save();

        return $publishing;
    });
}
```

---

### Task 8: Update MainController — Pass Contract to Statement PDF

**Files:**
- Modify: `app/Http/Controllers/Admin/Publishing/MainController.php`

**What to do:**

Update `view_statement()` to load the statement contract and pass it to the PDF generator:

```php
public function view_statement($id)
{
    $publishing = Publishing::findOrFail($id);
    
    try {
        $contract = $publishing->statementContract;
        return $this->contractService->generatePublishingStatementPdf($publishing, $contract);
    }
    catch (\Throwable $th) {
        Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
        abort(500, $th->getMessage());
    }
}
```

---

### Task 9: Update CreateContractRequest Validation

**Files:**
- Modify: `app/Http/Requests/Admin/CreateContractRequest.php`

**What to do:**

Add `statement` to the `type` validation rule (line 27):
```php
'type' => 'required|string|in:publishing,aggregator,mcn,statement',
```

---

### Task 10: Update Admin Publishing Show View — Verify Modal

**Files:**
- Modify: `resources/views/admin/publishing/show.blade.php`

**What to do:**

1. Add statement number inputs to the verify modal form (after the signee loop, before form close)
2. Display statement `docnum` in the Contract & Statement tab when available

**10a. Add statement fields to verify modal (insert after line 334 `@endforeach`):**
```html
<div class="card mt-3">
    <div class="card-body">
        <div class="mb-3 fw-bold">Statement Document Number</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <input type="number" name="statement_number" class="form-control form-control-sm" placeholder="Number" required>
            </div>
            <div class="col-md-5 mb-3">
                <select name="statement_month" class="form-control form-control-sm" required>
                    <option value="" selected disabled>Month</option>
                    <option value="1">January (I)</option>
                    <!-- ... same month options ... -->
                    <option value="12">December (XII)</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <input type="number" name="statement_year" class="form-control form-control-sm" placeholder="Year" required>
            </div>
        </div>
    </div>
</div>
```

**10b. Show statement contract docnum in the Statement card (update line 109 area):**

Add the document number display when a statement contract exists:
```html
<div class="card-header">
    <h6 class="card-title mb-0 fw-bold">Statement</h6>
</div>
<div class="card-body">
    @if($publishing->statementContract)
        <div class="mb-2">
            <span class="fw-bold">Doc No:</span> {{ $publishing->statementContract->docnum }}
        </div>
    @endif
    <!-- ... existing generate/upload/delete buttons ... -->
</div>
```

---

### Task 11: Update MainController.approve — Accept Statement Fields

**Files:**
- Modify: `app/Http/Controllers/Admin/Publishing/MainController.php`

**What to do:**

Update the `approve()` method to pass statement fields through to the service:

```php
$data = $request->only([
    'signeeid',
    'number',
    'month',
    'year',
    'statement_number',
    'statement_month',
    'statement_year',
]);
```

---

### Task 12: Update MainController.show — Eager Load Statement Contract

**Files:**
- Modify: `app/Http/Controllers/Admin/Publishing/MainController.php`

**What to do:**

Add `statementContract` to the eager loading in `show()`:

```php
$publishing = Publishing::with([
    'requestor',
    'rejector.songwriter',
    'signees.songwriter.ktp',
    'signees.songwriter.npwppersonal',
    'signees.songwriter.signature',
    'signees.contract.file',
    'statement.file',
    'nuon.file',
    'statementContract',  // <-- add this
])->findOrFail($id);
```

---

## Verification Plan

### Manual Verification

Since this project has no automated test coverage for the contract system, verification will be manual.

> [!IMPORTANT]
> User should verify these steps in a development environment with seeded data.

1. **Run migration** — `php artisan migrate` should complete without errors
2. **Verify existing contracts** — Check that existing aggregator and publishing contracts still display correctly in the contract index pages and publishing show page
3. **Test admin approval flow:**
   - Navigate to a publishing request in `review` status
   - Open the verify modal → confirm statement number fields appear below the signee contract fields
   - Fill in all fields and submit
   - Verify that:
     - Per-signee publishing contracts are created (same as before)
     - A new statement contract is created with `type = 'statement'`
     - The statement contract has the correct `docnum` format
     - The statement contract's `owner_type` = `App\Models\Publishing\Publishing` and `owner_id` = publishing ID
4. **Test statement PDF generation:**
   - On the publishing show page, Contracts & Statement tab
   - Verify the statement card shows the document number
   - Click "Generate PDF" → confirm the PDF includes the document number
5. **Test NUON unchanged** — Generate a NUON PDF and verify it works as before
6. **Test aggregator contracts** — Create/view an aggregator contract to ensure nothing is broken
7. **Database verification** — Run a direct query to confirm the column rename: `SHOW COLUMNS FROM contracts` should show `owner_type` and `owner_id` instead of `request_type` and `request_id`
