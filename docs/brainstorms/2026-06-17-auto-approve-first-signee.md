# Brainstorm: Auto-Approve First Signee

## Goal
Ensure that the creator of a publishing request, if listed as a songwriter (signee), is automatically approved (accepted, accept terms, accepted at) when the request is created.

## Decisions & Selection
- **Status**: Set status to `'accepted'` (a valid enum value in the database, maps to "Sign Approved" in the UI).
- **Additional columns**: Set `accept_terms = true` and `accepted_at = now()`.
- **Promotion to Review**: If all signees are accepted (e.g. current user is the sole songwriter), transition publishing request status to `'review'` immediately and send completion notifications.
- **View Condition**: Change `@if($signee->status == 'accepted' && $signee->updated_at != $signee->created_at)` to `@if($signee->status == 'accepted')`.

## Proposed Changes
- [PublishingService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Publishing/PublishingService.php)
- [publishing.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/components/publishing.blade.php)
