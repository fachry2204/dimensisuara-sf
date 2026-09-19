# Comprehensive Implementation Plan: Synchronize Release Options with Soft Deprecation

- **Version**: `> 💊 Aspirin v1.1.0`
- **Date**: 2026-06-28
- **Topic**: Release Options Synchronization & Soft Deprecation Plan

---

## Use Case Impact

AFFECTED USE CASES:
- **MODIFIED**: create release, edit release, submit release, admin aggregator create/edit release.
- **NEW**: idempotent system seeder with auto-sync from JSON configuration files.
- **REMOVED**: (none)

---

## Proposed Changes

### Phase 1: Database Migration
#### [NEW] [2026_06_28_000000_add_is_active_to_system_tables.php](file:///d:/Project/cms-soundfresh-id/database/migrations/2026_06_28_000000_add_is_active_to_system_tables.php)
Add an `is_active` column to: `_contributors`, `_productions`, `_versions`, `_genres`, `_subgenres`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['_contributors', '_productions', '_versions', '_genres', '_subgenres'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['_contributors', '_productions', '_versions', '_genres', '_subgenres'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
```

#### [MODIFY] [Contributor.php](file:///d:/Project/cms-soundfresh-id/app/Models/System/Contributor.php), [Production.php](file:///d:/Project/cms-soundfresh-id/app/Models/System/Production.php), [Version.php](file:///d:/Project/cms-soundfresh-id/app/Models/System/Version.php), [Genre.php](file:///d:/Project/cms-soundfresh-id/app/Models/System/Genre.php), [Subgenre.php](file:///d:/Project/cms-soundfresh-id/app/Models/System/Subgenre.php)
Add `is_active` to `$fillable` array in each of these models.

---

### Phase 2: Seeder Layer

#### [NEW] Move JSON files to seeder data folder
Move JSON files to `database/seeders/data/` (or copy them and remove from `docs/` as preferred):
- `database/seeders/data/contributor.json`
- `database/seeders/data/production.json`
- `database/seeders/data/version.json`
- `database/seeders/data/genre.json`

#### [MODIFY] [SystemSeeder.php](file:///d:/Project/cms-soundfresh-id/database/seeders/SystemSeeder.php)
Update `run()` method to:
- Rename spelling mistakes based on custom map to preserve existing records' IDs.
- Import new options from local JSON files.
- Automatically deactivate options that are no longer in the JSON files.
- Deactivate "Other" contributor (ID 42) and "Other" genre (ID 18).
- Keep "Original" version (ID 1) active.
- Verify idempotency.

---

### Phase 3: Backend Logic

#### [MODIFY] [ReleasesController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/ReleasesController.php) & [MainController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/Admin/Aggregator/MainController.php)
Update `get_subgenre` to support loading active subgenres plus the record's current subgenre if it is legacy (via request query param e.g. `?current=X`).

#### [MODIFY] [AlbumRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/AlbumRequest.php) & [TrackRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/TrackRequest.php)
Support custom rules closures that receive the release/track context. Validate that options must be active OR match the currently selected value. Define clean, descriptive custom failure messages.

---

### Phase 4: Frontend Blade/Alpine.js

#### [MODIFY] [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php) & [track-form-fields.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/partials/track-form-fields.blade.php) & [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/admin/aggregator/create.blade.php)
For each drop-down (versions, genres, contributors, productions), modify option rendering to conditionally include inactive choices when currently selected:
```html
@foreach(App\Models\System\Version::all() as $v)
    @if($v->is_active)
        <option value="{{ $v->id }}">{{ $v->name }}</option>
    @else
        <option value="{{ $v->id }}" x-show="$store.release.version == {{ $v->id }}">{{ $v->name }} (Legacy)</option>
    @endif
@endforeach
```

---

### Phase 5: Verification & Testing
1. Run database migration and seed:
   `php artisan migrate`
   `php artisan db:seed --class=SystemSeeder`
2. Run manual tests creating a new release. Verify inactive items do not show up.
3. Edit an existing release with inactive items. Check that dropdown shows `Other (Legacy)` or `Original (Legacy)` and saves successfully without error.

---

## Acceptance Criteria
- [ ] Database migration successfully adds `is_active` column to the 5 system tables.
- [ ] `SystemSeeder.php` successfully parses JSON config files in `database/seeders/data/` and seeds data idempotently.
- [ ] Spelling/typo corrections (`Bassoon`, `Acoustic`, `Co-Producer`, `alternative/indie`, `latin`, `Hip Hop/Rap`) are renamed on existing records directly.
- [ ] Inactive options (e.g. `Other` contributor, `Other` genre) are marked `is_active = false`.
- [ ] Legacy tracks/releases displaying inactive options correctly show them as `(Legacy)` and can submit/save without validation failures.
- [ ] New tracks/releases cannot view or select legacy options, and custom validation error messages are correctly returned if bypassed.
