# Brainstorming: Release Options Synchronization & Soft Deprecation

- **Version**: `> 💊 Aspirin v1.1.0`
- **Date**: 2026-06-28
- **Topic**: Release Options Synchronization & Soft Deprecation

---

## Phase 1: UNDERSTAND

We have checked the current options in the database schema and compared them with the files in `docs/*.json`. The requirements are:
1. Implement **Approach A (Soft Deprecation)** by adding an `is_active` column to reference tables (`_contributors`, `_productions`, `_versions`, `_genres`, `_subgenres`).
2. Keep the `Original` version (ID 1) active.
3. Deprecate "Other" contributor (ID 42) and "Other" genre (ID 18) by setting their `is_active = false`.
4. In create/edit forms, show active options (and legacy if already selected); in show view, display all options.

---

## Phase 2: PROPOSE

To handle editing legacy releases/tracks containing inactive (deprecated) options, we propose three frontend/backend approaches:

| Option | Approach | Pros | Cons | Best Practice? |
|--------|----------|------|------|----------------|
| **Option A** | **Dynamic Inclusion via Alpine/Blade**<br>Dropdowns show active options, plus any inactive option currently selected by the record (using `x-show`). | • Seamless user experience: users can edit other fields without being forced to change legacy metadata.<br>• Clean UI: new items cannot select legacy options. | • Requires micro-conditionals in Blade templates. | ✅ Yes |
| **Option B** | **Disabled Options**<br>Render all options in dropdowns, but add `disabled` attribute to inactive options. | • Simple implementation. | • Users see deprecated options in the list, creating clutter.<br>• Browser-dependent behavior for pre-selected disabled options. | ❌ Less common |
| **Option C** | **Strict Mode (Force Update)**<br>Hide inactive options completely. If a legacy record is edited, the dropdown defaults to empty, forcing the user to pick an active option. | • Guarantees that saved edits only contain active, modern options. | • Bad user experience: blocks the user from saving minor edits unless they re-evaluate metadata. | ❌ Strict |

### Affected Use Cases
* **Create Release/Track**: Users will only see active options.
* **Edit Release/Track (Legacy)**: Users editing an old release with inactive options will see their selected legacy options in the dropdown but cannot choose *other* legacy options.
* **Show Release/Track**: Displays the names of all options (active and inactive) resolved via database relations.

---

## Phase 3: DESIGN (Option A Selected with Enhancements)

### 1. Database Schema Changes
We will create a migration to:
- Add a boolean `is_active` column (default `true`) with indexes to the reference tables: `_contributors`, `_productions`, `_versions`, `_genres`, `_subgenres`.

### 2. Auto-sync Seeders (Enhancement 1)
Modify `database/seeders/SystemSeeder.php` to sync options directly from `docs/*.json` files.
- Typo and spelling corrections (e.g. `Basson` $\rightarrow$ `Bassoon`, `Co Producer` $\rightarrow$ `Co-Producer`, `Accoustic` $\rightarrow$ `Acoustic`, `Remaster` $\rightarrow$ `Remastered`) will update existing record names directly to preserve foreign keys.
- New options will be inserted.
- Deletions in JSON will mark existing records as `is_active = false` (soft deprecating them).
- Special rules will preserve `Original` (version ID 1) as active, and set `Other` contributor (ID 42) and `Other` genre (ID 18) to `is_active = false`.

### 3. Controller & API Changes
- **Subgenres endpoint (`ReleasesController@get_subgenre`)**:
  Modify to retrieve active subgenres, plus include the track's current subgenre if it happens to be inactive:
  ```php
  $subgenres = Subgenre::where('genreid', $genreid)
      ->where(function($query) use ($currentSubgenreId) {
          $query->where('is_active', true);
          if ($currentSubgenreId) {
              $query->orWhere('id', $currentSubgenreId);
          }
      })
      ->orderBy('name')
      ->get();
  ```

### 4. Validation Rules Update (Enhancement 2)
In `AlbumRequest.php` and `TrackRequest.php`, we will validate that selected options are active unless they are already selected on the existing record:
- **AlbumRequest.php (`rules` method)**:
  Accept an optional `$release` instance. For fields `version`, `genre`, `subgenre`:
  ```php
  function ($attribute, $value, $fail) use ($release) {
      $item = Model::find($value);
      if (!$item) {
          $fail("The selected $attribute is invalid.");
          return;
      }
      if ($item->is_active || ($release && $release->{$attribute} == $value)) {
          return;
      }
      $fail("The selected $attribute is a legacy option and is no longer available.");
  }
  ```
- **TrackRequest.php (`rules` method)**:
  Accept an optional `$track` instance. For fields `version`, `genre`, `subgenre`, `contributors.*.role`, `productions.*.role`:
  Validate that it is active, or exists in `$track` currently stored attributes/arrays.

### 5. Frontend Blade & Alpine.js Changes
- **Blade Dropdowns (e.g., Version, Genre, Contributor, Production)**:
  Filter options by `is_active` using conditional rendering. Example:
  ```html
  <select class="form-select" x-model="contributor.role">
      @foreach (App\Models\System\Contributor::all() as $c)
          @if ($c->is_active)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
          @else
              <option value="{{ $c->id }}" x-show="contributor.role == {{ $c->id }}">{{ $c->name }} (Legacy)</option>
          @endif
      @endforeach
  </select>
  ```
  This renders the inactive options in the HTML but hides them unless they are currently selected.

---

## Phase 4: ENHANCEMENT OPPORTUNITIES

Both enhancements have been chosen:
1. **Seeder Auto-sync**: Automate importing/updating from JSON files in the seeder to prevent future manual syncing errors.
2. **Validation Rules update**: Add server-side validation to ensure new tracks/releases cannot submit inactive options, while allowing them for existing drafts/rejected status.
