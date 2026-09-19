# Brainstorm: Subgenre Schema and Validation Cleanup

## Phase 3: DESIGN (Option A)

### Data Flow
1. **Seeding:** `SystemSeeder` reads `genre.json` (with `"N/A"` removed), upserts genres/subgenres, and deletes legacy `"N/A"` subgenres from the database.
2. **Form Entry (Frontend):** 
   - User leaves the Subgenre dropdown as "No Subgenre" (sends empty string/`""` or `0` depending on selected option).
   - Alpine state initializes `subgenre` as `null`/`""`.
3. **Form Submission / Draft Saving (Backend):**
   - The controller receives the payload.
   - `ReleaseService::store()` intercepts the payload and maps empty/`0` values for `genre` and `subgenre` to database `NULL`.
   - The record is persisted in `releases` and `tracks` tables.
4. **Validation (On Submit):**
   - `AlbumRequest` and `TrackRequest` validate `subgenre` using `nullable|integer`.
   - The closure validator allows `null`, `0`, or empty string to pass validation.

### Database Changes
A new migration will modify the `tracks` table:
* Change column `genre` to `unsignedBigInteger` and nullable.
* Change column `subgenre` to `unsignedBigInteger` and nullable.
* Set existing invalid/`"N/A"` subgenre values to `NULL`.
* Add foreign key constraints:
  - `tracks.genre` -> `_genres(id)` with `onDelete('restrict')`
  - `tracks.subgenre` -> `_subgenres(id)` with `onDelete('restrict')`

### API & Validation Shape
* **`app/Http/Requests/Release/AlbumRequest.php`**
  ```php
  'subgenre' => [
      'nullable',
      'integer',
      function ($attribute, $value, $fail) use ($release) {
          if ($value === 0 || $value === '0' || empty($value)) {
              return;
          }
          $item = \App\Models\System\Subgenre::find($value);
          if (!$item) {
              $fail('The selected subgenre is invalid.');
              return;
          }
          if ($item->is_active || ($release && $release->subgenre == $value)) {
              return;
          }
          $fail('The selected subgenre is a legacy option and is no longer available.');
      }
  ]
  ```
* **`app/Http/Requests/Release/TrackRequest.php`**
  ```php
  'subgenre' => [
      'nullable',
      'integer',
      function ($attribute, $value, $fail) use ($track) {
          if ($value === 0 || $value === '0' || empty($value)) {
              return;
          }
          $item = \App\Models\System\Subgenre::find($value);
          if (!$item) {
              $fail('The selected subgenre is invalid.');
              return;
          }
          if ($item->is_active || ($track && $track->subgenre == $value)) {
              return;
          }
          $fail('The selected subgenre is a legacy option and is no longer available.');
      }
  ]
  ```

---

### Affected Use Cases
```
AFFECTED USE CASES (consolidated):
- MODIFIED: Create Release (Single & Album)
- MODIFIED: Edit Release (Single & Album)
- MODIFIED: Submit Release validation flow
- MODIFIED: System Seeding
```

***

## Phase 4: ENHANCEMENT OPPORTUNITIES

| # | Enhancement | Benefit | Priority |
|---|-------------|---------|----------|
| 1 | Standardize `version` column | Convert `tracks.version` to `unsignedBigInteger` and add foreign key to match `releases.version`. | Recommended |
| 2 | Hard delete legacy subgenre entries | Run a query in migration to delete any subgenre with name `"N/A"` from `_subgenres`. | Recommended |

Include any of these? (list numbers, 'all', or 'none')
