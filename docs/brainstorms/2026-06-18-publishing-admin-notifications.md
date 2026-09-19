# Brainstorm: Publishing Admin Approval and Rejection Notifications

This document designs the notification routing and attachments when a publishing request is approved or rejected by an admin.

## Phase 3: Detailed Design (Option A)

### Data Flow
1. **Approval Flow**:
   - Admin approves the request -> `PublishingService@adminApprove` executes database updates.
   - Outside the transaction, the service retrieves the PDF contents for the Publishing Contract, Songwriter Statement, and NUON Statement.
   - The service dispatches `Customer\Publishing\AdminApproved` to the requestor with all three PDFs attached.
   - The service dispatches `Guest\Publishing\AdminApproved` to all guest songwriters/signees with only the Songwriter Statement PDF attached.
2. **Rejection Flow**:
   - Admin rejects the request -> `PublishingService@adminReject` saves the rejection reason.
   - The service dispatches `Customer\Publishing\AdminRejected` to the requestor.
   - The service dispatches `Guest\Publishing\AdminRejected` to all guest songwriters/signees.

### Key Components

#### 1. Backend Service Layer (`app/Services/Publishing/PublishingService.php`)
Retrieve PDF contents and dispatch notifications.
- **PKS Contract**:
  - If `$publishing->publishingContract->file` exists: retrieve from storage.
  - Else: call `ContractService::generatePublishingContractPdf($publishing->publishingContract, false)`.
- **Songwriter Statement**:
  - If `$publishing->statement && $publishing->statement->file` exists: retrieve from storage.
  - Else: call `ContractService::generatePublishingStatementPdf($publishing, $publishing->statementContract, false)`.
- **NUON Statement**:
  - If `$publishing->nuon && $publishing->nuon->file` exists: retrieve from storage.
  - Else: call `ContractService::generatePublishingNuonPdf($publishing, false)`.

#### 2. Notifications Layer
Four new notification classes implemented:
- **`App\Notifications\Customer\Publishing\AdminApproved`**
  - Attaches: `pksPdf`, `statementPdf`, `nuonPdf`
- **`App\Notifications\Guest\Publishing\AdminApproved`**
  - Attaches: `statementPdf` (only)
- **`App\Notifications\Customer\Publishing\AdminRejected`**
  - Sends rejection reason
- **`App\Notifications\Guest\Publishing\AdminRejected`**
  - Sends rejection reason

---

## Affected Use Cases (Consolidated)

AFFECTED USE CASES:
- **MODIFIED**: Verify and compile publishing contract (Admin approval sends notifications and attachments)
- **MODIFIED**: Reject publishing request (Admin rejection sends notifications to all songwriters)
- **NEW**: Receive final publishing contracts (Customer and guest songwriters get email attachments)
- **REMOVED**: (none)

---

## Phase 4: Enhancement Opportunities

| # | Enhancement | Benefit | Priority |
|---|-------------|---------|----------|
| 1 | WhatsApp Alerts on Admin Decision | Informs users and songwriters instantly via WhatsApp channels. | Recommended |
| 2 | Attachment Storage Caching | Prevents re-rendering PDFs dynamically if they already exist in local memory/cache. | Optional |
| 3 | Log notification dispatch status | Keep an audit trail of which songwriters were successfully emailed. | Optional |
