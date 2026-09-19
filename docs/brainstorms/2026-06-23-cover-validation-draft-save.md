# Brainstorm: Align Cover MIME Validation and Optimize Draft Saving

## Phase 1: Understand
- Checked code in `create.blade.php`, `ReleaseService.php`, and `ReleasesController.php`.
- Discovered frontend allows `image/png` in `uploadCover()`, while the backend strictly expects `image/jpeg`.
- Discovered that after a draft is saved, `Alpine.store('release').cover = ""` is called, which does not clear the HTML `<input type="file">` elements, causing re-uploads on subsequent saves.
- Discovered IPL does not update its indicator dynamically on draft saving because it relies on server-rendered Blade template.

## Phase 2: Propose
Aligned with the user on Option A (State-Driven Integration via Alpine.js):
- Restrict MIME validation to `image/jpeg`.
- Sync IPL upload state to Alpine.js and clear file inputs on successful draft save to avoid redundant re-uploads.

## Phase 3: Design

### Data Flow
- User uploads JPG cover -> validation passes frontend `['image/jpeg']` -> sets `coverImage` preview -> file input `#cover-input` holds the file.
- User uploads PDF IPL -> file input `#ipl` holds the file.
- User clicks "Save Draft" -> frontend constructs `FormData(form)` -> POST to `/releases/store` or `/releases/update/{id}`.
- Backend processes request:
  - If cover uploaded -> validates JPG MIME & 3000x3000px resolution -> stores file & thumbnail.
  - If IPL uploaded -> stores PDF file.
  - Returns JSON response:
    ```json
    {
      "success": true,
      "id": 123,
      "tracks": [45, 46],
      "ipl_url": "http://...",
      "ipl_name": "filename.pdf"
    }
    ```
- Frontend handles response:
  - Updates `Alpine.store('release').id` and `tracks` IDs.
  - Updates `Alpine.store('release').iplUrl` and `Alpine.store('release').iplName` with the returned values.
  - Clears file inputs: `#cover-input` and `#ipl`.
  - Renders "Current IPL" dynamically using `x-show` and `x-text` based on `iplUrl` and `iplName`.

### Key Components

#### Frontend (`resources/views/customer/releases/create.blade.php`):
- Update frontend MIME checking to only allow `image/jpeg`.
- Extend Alpine store to include `iplUrl` and `iplName` fields.
- Update IPL template block to dynamically show current IPL using `x-show` and `x-text`.
- Clear `#cover-input` and `#ipl` file values in the save/submit success callbacks.

#### Backend (`app/Http/Controllers/ReleasesController.php`):
- Return `ipl_url` and `ipl_name` in the success JSON responses of `store` and `update` methods.

---

### Affected Use Cases
```
AFFECTED USE CASES (consolidated):
- MODIFIED: Save Release Draft (Customer & Admin Aggregator), Submit Release (Customer)
```
