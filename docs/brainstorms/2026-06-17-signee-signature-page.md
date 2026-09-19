# Brainstorm: Signee Signature Page Revision

## Goal
Revise the signee contract signing flow so that:
- The approval confirmation modal is removed.
- The "Post Term Period" checkbox is displayed directly on the main signing page.
- A signature canvas/upload field is displayed on the main page.
- An acknowledgment text is displayed before the signature.
- The signature is stored polymorphically directly onto the `Signee` model.
- The completed contract details view is refactored to read directly from the `Signee` model fields and its polymorphic signature relation.

## Decisions & Selection
- **Form Placement**: Placed directly under contract details, replacing the modal.
- **Validation**: Validate terms checked and signature canvas data / file.
- **Completed View**: Refactor `components/publishing-signee.blade.php` to drop `songwriter` references.

## Proposed Changes
- [Signee.php](file:///d:/Project/cms-soundfresh-id/app/Models/Publishing/Signee.php)
- [PublishingController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/Guest/PublishingController.php)
- [sign.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/guest/sign/publishing/sign.blade.php)
- [publishing-signee.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/components/publishing-signee.blade.php)
