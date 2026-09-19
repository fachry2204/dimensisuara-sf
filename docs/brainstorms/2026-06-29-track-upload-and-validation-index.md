# Brainstorm: Track Upload & Validation Index Inconsistency

## Proposed Changes

We will resolve two key issues:
1. **Unsetting input after track audio upload**: Allow users to upload the same file again (e.g. for another track or after resetting) by clearing the file input `value` in the `finally` block of `uploadAudioFile`.
2. **Incorrect track index in validation message**: Ensure track validation error messages correspond to the correct track order.

### Summary of Changes

| Component | Responsibility / Approach |
|---|---|
| **Frontend (JS)** | Clear file input value in `finally` block of `window.uploadAudioFile` in `track-form-scripts.blade.php`. |
| **Backend (Laravel)** | 1. Ensure `track_order` in `ReleaseService::store` defaults to inserting index `$index` if missing.<br>2. Explicitly sort tracks by `'pivot.track_order'` in `ReleaseService::submit` validation loop.<br>3. Distinguish duplicate validation attributes (e.g. title) between release and track by defining custom attributes in request classes and passing them to `Validator::make`. |

## Affected Use Cases
```
AFFECTED USE CASES:
- MODIFIED: save draft release, submit release for validation
- NEW: (none)
- REMOVED: (none)
```

## Decisions
- Reset the input file element's value to `''` upon upload completion.
- Align Eloquent collection keys with pivot `track_order` by sorting explicitly.
- Forward custom attributes and messages from the manual request instances to Laravel validator factory.
