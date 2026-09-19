# Brainstorm: Modify Publishing Contract Generation

This document details the modifications to the publishing request contract generation logic. Instead of generating separate contracts for each songwriter (signee) on a request, we will generate a single publishing contract directly related to the publishing request itself.

## User Decisions & Clarifications
1. **Contract Mapping**: Direct relation of the single PKS contract to the `Publishing` request itself (`owner_type = 'App\Models\Publishing\Publishing'`).
2. **Requestor Details**: The requestor profile details will be utilized for the main PKS contract and as the first songwriter (index 0) in both statement documents (Songwriter Statement & NUON Statement).
3. **Verify Modal**: The admin verification modal will require a single set of inputs for the contract document number and a single set of inputs for the statement document number.
4. **Documents UI**: A single unified "Documents" table showing the Publishing Contract, the Songwriter Statement, and the NUON Statement.

---

## Detailed Design: Option A (Request-Centric)

### 1. Model Layer (`App\Models\Publishing\Publishing`)
Define the relationships on the request:
```php
public function statementContract()
{
    return $this->morphOne(Contract::class, 'owner')->where('type', 'publishing_statement');
}

public function publishingContract()
{
    return $this->morphOne(Contract::class, 'owner')->where('type', 'publishing');
}
```

### 2. Service Layer (`App\Services\Publishing\PublishingService`)
Modify `adminApprove` to accept and create one contract and one statement:
```php
public function adminApprove(Publishing $publishing, $data)
{
    // ... validation ...
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

### 3. Contract Generator (`App\Services\ContractService`)
- **`generatePublishingContractPdf`**:
  - Load `$publishing = $contract->owner`.
  - Resolve requestor details and identities (KTP, NPWP, Profile signature/creator signee signature).
  - Populate PKS placeholders.
- **`generatePublishingStatementPdf` & `generatePublishingNuonPdf`**:
  - Loop through signees. If index is `0` (creator), substitute profile details (address and signature from identities). If index > `0`, use `$signee` details (address `"-"`).

### 4. Admin View (`show.blade.php`)
- **Verification Form**:
  - Contract fields: `contract_number`, `contract_month`, `contract_year`.
  - Statement fields: `statement_number`, `statement_month`, `statement_year`.
- **Documents Table**:
  - Clean table structure displaying Publishing Contract, Statement of Songwriter, and NUON Statement in rows.

---

## Consolidated Use Case Impact

AFFECTED USE CASES (consolidated):
- **MODIFIED**: View completed publishing contract details (Admin)
- **MODIFIED**: Verify and compile publishing contract (Admin)
- **REMOVED**: (none)
