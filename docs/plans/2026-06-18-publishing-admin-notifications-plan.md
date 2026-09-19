# Implementation Plan: Publishing Admin Approval and Rejection Notifications

This plan adds notification emails, database entries, and WhatsApp alerts for the admin approval and rejection events of publishing requests. The notifications will include attached PDFs.

## Use Case Impact

AFFECTED USE CASES:
- **MODIFIED**: Verify and compile publishing contract (Admin approval triggers notifications and attachments)
- **MODIFIED**: Reject publishing request (Admin rejection triggers notifications)
- **NEW**: Receive final publishing contracts (Customer and guest songwriters get email attachments)
- **REMOVED**: (none)

---

## Phase 1: Model/Relationship Layer
*(No database changes or new models are required)*

---

## Phase 2: Backend Logic Layer

**Task 2.1: Update prepareDomPdf and generators in ContractService**
- **File**: [ContractService.php](file:///d:/Project/cms-soundfresh-id/app/Services/ContractService.php)
- **Code**:
  - Add optional `$stream = true` parameter to `prepareDomPdf`, returning `$dompdf->output()` if `false`.
  - Add optional `$stream = true` parameter to `generatePublishingContractPdf`, `generatePublishingStatementPdf`, and `generatePublishingNuonPdf`, passing it through.
- **Verification**: Run `php artisan test --filter PublishingTest` to ensure standard downloads aren't broken.
- **Pattern**: Parameterized service method execution.

**Task 2.2: Create Customer AdminApproved Notification**
- **File**: [NEW] [AdminApproved.php](file:///d:/Project/cms-soundfresh-id/app/Notifications/Customer/Publishing/AdminApproved.php)
- **Code**: Define standard channels (`mail`, `DatabaseChannel`, `WhatsAppService`) and attach the PKS, Statement, and NUON Statement PDFs if present.
- **Verification**: Instantiate the notification class via PHPUnit/tinker.
- **Pattern**: Laravel Notification system.

**Task 2.3: Create Guest AdminApproved Notification**
- **File**: [NEW] [AdminApproved.php](file:///d:/Project/cms-soundfresh-id/app/Notifications/Guest/Publishing/AdminApproved.php)
- **Code**: Define standard channels (`mail`, `DatabaseChannel` for authenticated users) and attach the Statement PDF.
- **Verification**: Instantiate the notification class via PHPUnit/tinker.
- **Pattern**: Laravel Notification system.

**Task 2.4: Create Customer AdminRejected Notification**
- **File**: [NEW] [AdminRejected.php](file:///d:/Project/cms-soundfresh-id/app/Notifications/Customer/Publishing/AdminRejected.php)
- **Code**: Define standard channels and include the rejection reason.
- **Verification**: Instantiate the notification class via PHPUnit/tinker.
- **Pattern**: Laravel Notification system.

**Task 2.5: Create Guest AdminRejected Notification**
- **File**: [NEW] [AdminRejected.php](file:///d:/Project/cms-soundfresh-id/app/Notifications/Guest/Publishing/AdminRejected.php)
- **Code**: Define standard channels and include the rejection reason.
- **Verification**: Instantiate the notification class via PHPUnit/tinker.
- **Pattern**: Laravel Notification system.

**Task 2.6: Trigger notifications in adminApprove and adminReject methods of PublishingService**
- **File**: [PublishingService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Publishing/PublishingService.php)
- **Code**:
  - At the end of `adminApprove()`, retrieve raw PDF contents (from storage if uploaded, else dynamically generated) and dispatch `Customer\Publishing\AdminApproved` and `Guest\Publishing\AdminApproved` notifications.
  - At the end of `adminReject()`, dispatch `Customer\Publishing\AdminRejected` and `Guest\Publishing\AdminRejected` notifications.
- **Verification**: Run `php artisan test --filter PublishingTest` to verify that standard approval flows pass without regression.
- **Pattern**: Event/Notification dispatching pattern.

---

## Verification Plan

### Automated Tests
Run existing tests to ensure no regressions:
```bash
php artisan test --filter PublishingTest
```

And write a new feature test verifying the notifications:
- **File**: [PublishingTest.php](file:///d:/Project/cms-soundfresh-id/tests/Feature/PublishingTest.php)
- **Code**: Assert notifications are sent and correct files are attached.
```bash
php artisan test --filter test_admin_decision_sends_notifications
```

### Manual Verification
1. Put request to `review` status.
2. In admin console, approve the request with single numbers.
3. Check the mail log or debug log to verify that:
   - Creator user gets 1 email containing 3 PDF attachments.
   - Guest signees get 1 email containing 1 statement PDF attachment.
4. Put another request in `review` status, reject it, and verify rejection emails are received by all signees.

---

## Acceptance Criteria
- [ ] Creator/first signee receives an admin-approved notification with Publishing Contract, Songwriter Statement, and NUON Statement attached.
- [ ] Guest signees receive an admin-approved notification with only the Songwriter Statement attached.
- [ ] Admin rejection triggers notifications containing the rejection reason to all songwriters (creator + guests).
- [ ] Attached PDFs are fetched from local storage if uploaded, or generated dynamically.
