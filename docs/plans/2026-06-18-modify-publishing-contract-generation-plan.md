# Implementation Plan: Modify Publishing Contract Generation

This plan refactors the contract generation logic for publishing requests so that only one PKS contract is generated per request (polymorphically owned by the `Publishing` request itself). It also updates the contract and statement documents to populate requestor profile details for the main songwriter inputs, updates the admin verification flow, and consolidates the documents list on the show view.

## Use Case Impact

AFFECTED USE CASES:
- **MODIFIED**: View completed publishing contract details (Admin)
- **MODIFIED**: Verify and compile publishing contract (Admin)
- **REMOVED**: (none)

---

## Phase 1: Model Layer

**Task 1.1: Update Relationships in Publishing model**
- **File**: [Publishing.php](file:///d:/Project/cms-soundfresh-id/app/Models/Publishing/Publishing.php)
- **Code**: Define `publishingContract` relationship and update `statementContract` relationship to filter by contract type:
```php
    public function statementContract()
    {
        return $this->morphOne(\App\Models\Contract::class, 'owner')->where('type', 'publishing_statement');
    }

    public function publishingContract()
    {
        return $this->morphOne(\App\Models\Contract::class, 'owner')->where('type', 'publishing');
    }
```
- **Verification**: Run `php artisan tinker --execute="App\Models\Publishing\Publishing::with(['statementContract', 'publishingContract'])->first()"` (should run without syntax errors).
- **Pattern**: Eloquent Polymorphic MorphOne relationship.

---

## Phase 2: Backend Logic Layer

**Task 2.1: Update adminApprove in PublishingService**
- **File**: [PublishingService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Publishing/PublishingService.php)
- **Code**: Rewrite the `adminApprove` method to store exactly one PKS contract associated directly with the `Publishing` model:
```php
    public function adminApprove(Publishing $publishing, $data)
    {
        // Validate status
        if ($publishing->status != 'review') {
            throw new InvalidRequestStatusException($publishing->status, ['review']);
        }

        return DB::transaction(function () use ($publishing, $data) {
            // Create single publishing contract
            $contractPayload = [
                'profileid' => $publishing->requestor->id,
                'type' => 'publishing',
                'number' => $data['contract_number'],
                'month' => $data['contract_month'],
                'year' => $data['contract_year']
            ];
            $contract = $this->contractService->store($contractPayload);
            $contract->owner()->associate($publishing);
            $contract->save();

            // Create statement contract
            $statementPayload = [
                'profileid' => $publishing->requestor->id,
                'type' => 'publishing_statement',
                'number' => $data['statement_number'],
                'month' => $data['statement_month'],
                'year' => $data['statement_year'],
            ];
            $statementContract = $this->contractService->store($statementPayload);
            $statementContract->owner()->associate($publishing);
            $statementContract->save();

            // Update status
            $publishing->status = 'completed';
            $publishing->contract_auto_renew = true;
            $publishing->save();

            return $publishing;
        });
    }
```
- **Verification**: Run `php artisan test --filter PublishingTest` to verify that status transitions and exceptions still work as expected.
- **Pattern**: Transactional Service Layer pattern.

**Task 2.2: Update approve action in Admin Publishing MainController**
- **File**: [MainController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/Admin/Publishing/MainController.php)
- **Code**: 
  - Update preloaded relations in `show()` method:
```php
        $publishing = Publishing::with([
            'requestor',
            'rejector',
            'signees.signature',
            'publishingContract.file',
            'statementContract.file',
            'statement.file',
            'nuon.file',
        ])->findOrFail($id);
```
  - Apply request validation and retrieve inputs in `approve()` method:
```php
        $request->validate([
            'contract_number'  => 'required|integer|min:1',
            'contract_month'   => 'required|integer|between:1,12',
            'contract_year'    => 'required|integer|min:2020',
            'statement_number' => 'required|integer|min:1',
            'statement_month'  => 'required|integer|between:1,12',
            'statement_year'   => 'required|integer|min:2020',
        ]);

        $data = $request->only([
            'contract_number',
            'contract_month',
            'contract_year',
            'statement_number',
            'statement_month',
            'statement_year',
        ]);
```
- **Verification**: Code compilation and manual verification when approving.
- **Pattern**: Thin Controller pattern.

**Task 2.3: Make PublishingContractController backward compatible**
- **File**: [ContractController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/Admin/Publishing/ContractController.php)
- **Code**: Update `get_data` AJAX method to handle leftJoin and fields mapping correctly regardless of whether the owner is a `Publishing` request or a legacy `Signee` model:
```php
            $query = Contract::where('type', 'publishing')
                ->select(
                    'contracts.id as id',
                    'contracts.created_at as created_at',
                    'contracts.type as type',
                    'contracts.number as number',
                    'contracts.month as month',
                    'contracts.year as year',
                    'contracts.sort as sort',
                    'contracts.docnum as docnum',
                    'publishings.id as publishingid',
                    'publishings.title as title',
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS fullname"),
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS songwriter")
                )
                ->leftJoin('user_profiles', 'user_profiles.id', 'contracts.profileid')
                ->leftJoin('publishings', function ($join) {
                    $join->on('publishings.id', '=', 'contracts.owner_id')
                         ->where('contracts.owner_type', '=', 'App\Models\Publishing\Publishing');
                });
```
And inside `get_data` transform callback:
```php
            $data->transform(function ($item) {
                $contract = Contract::with('owner', 'owner.requestor')->find($item->id);
                $item->file = (bool) $contract->file;
                $item->title = $contract->owner instanceof Publishing ? $contract->owner->title : $contract->owner?->publishing?->title;
                $item->requestid = $contract->owner instanceof Publishing ? $contract->owner->id : $contract->owner?->publishing?->id;
                $item->songwriter = $contract->owner instanceof Publishing ? $contract->owner->requestor?->full_name : $contract->owner?->full_name;

                return $item;
            });
```
- **Verification**: Datatable lists contracts without throwing SQL or PHP errors.
- **Pattern**: Backward compatibility logic.

**Task 2.4: Update ContractService PDF generation routines**
- **File**: [ContractService.php](file:///d:/Project/cms-soundfresh-id/app/Services/ContractService.php)
- **Code**:
  - In `generatePublishingContractPdf()`:
    - Retrieve details from requestor Profile `$profile = $publishing->requestor;`
    - Retrieve files morphically:
      - `[SIGNATURE2]` file: `$profile->identities()->where('type', 'signature')->first()`
      - `[KTP]` file: `$profile->identities()->where('type', 'ktp')->first()`
      - `[NPWP]` file: `$profile->identities()->where('type', 'npwppersonal')->first()`
  - In `generatePublishingStatementPdf()`:
    - Loop over `$publishing->signees` but conditionalize variables per songwriter:
      - If `$index == 0`: retrieve address (`$publishing->requestor->full_address`), signature (`$publishing->requestor->identities()->where('type', 'signature')->first()`), KTP (`$publishing->requestor->identities()->where('type', 'ktp')->first()`), and NPWP (`$publishing->requestor->identities()->where('type', 'npwppersonal')->first()`).
      - If `$index > 0`: address = `"-"`, signature = `$signee->signature`, KTP = `null`, NPWP = `null`.
  - In `generatePublishingNuonPdf()`:
    - Similarly, condition on `$index == 0` vs `$index > 0` for address, signature and KTP file mapping.
- **Verification**: Document generation displays the correct information and files.
- **Pattern**: Document/Template compiling pattern.

---

## Phase 3: Frontend Layer

**Task 3.1: Consolidate Documents table and update Verification Modal**
- **File**: [show.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/admin/publishing/show.blade.php)
- **Code**:
  - Replace the entire cards and loop elements in the `tabs-contracts` pane with a unified "Documents" table containing types: `Publishing Contract`, `Songwriter Statement`, and `NUON Statement`.
  - Replace verification modal inputs with single fields for the Publishing Contract (`contract_number`, `contract_month`, `contract_year`) and Statement Document (`statement_number`, `statement_month`, `statement_year`).
- **Verification**: Admin console verification modal renders one set of inputs and submits correctly. The documents list displays one consolidated table.
- **Pattern**: Unified User Interface pattern.

---

## Verification Plan

### Automated Tests
Run the feature tests to ensure basic validations and transitions are unhindered:
```bash
php artisan test --filter PublishingTest
```

### Manual Verification
1. Put a publishing request into `review` status.
2. In admin console, open details and verify click **Verify Publishing** modal displays single PKS contract inputs.
3. Submit verification and verify status transitions to `completed`.
4. Generate each PDF from the "Documents" table and verify that the creator is populated as the comparison details, and guest signees have address `"-"`.

---

## Acceptance Criteria
- [ ] Publishing Contract and Statement Document are created exactly once per publishing request.
- [ ] Publishing Contract fields (name, address, bank details, KTP, NPWP, signature) populate from the requestor profile.
- [ ] In statement documents, the first signer uses the requestor profile's address and signature, while other signees use their signee properties.
- [ ] Admin panel verification modal prompts for single PKS contract details and single SP statement details.
- [ ] Admin detail page renders all request documents in a single unified table under "Documents" header.
