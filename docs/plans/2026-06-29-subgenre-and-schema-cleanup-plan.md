# Implementation Plan - Subgenre and Schema Cleanup

Standardize the `tracks` table database schema (genre, subgenre, version columns) to use foreign keys pointing to their system tables (`_genres`, `_subgenres`, `_versions`), make subgenre nullable across both releases and tracks, completely remove `"N/A"` subgenre options, and sanitize input payloads so that `0`/empty values are stored as `NULL`.

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: Create Release (Single & Album)
- MODIFIED: Edit Release (Single & Album)
- MODIFIED: Submit Release validation flow
- MODIFIED: System Seeding

---

## Proposed Changes

### Database Layer

#### [NEW] [2026_06_29_000000_align_tracks_schema_columns.php](file:///d:/Project/cms-soundfresh-id/database/migrations/2026_06_29_000000_align_tracks_schema_columns.php)
Create a new migration to standardize `tracks` schema columns and foreign keys, and clean up `"N/A"` subgenre records.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clean up tracks with invalid versions/genres/subgenres
        // Convert any empty/0 string values to NULL first to prevent foreign key errors
        DB::table('tracks')
            ->whereIn('version', ['0', 'original', ''])
            ->update(['version' => null]);

        DB::table('tracks')
            ->whereIn('genre', ['0', ''])
            ->update(['genre' => null]);

        DB::table('tracks')
            ->whereIn('subgenre', ['0', ''])
            ->update(['subgenre' => null]);

        // 2. Clean up any invalid subgenres pointing to deleted/legacy subgenres (specifically 'N/A')
        DB::statement("UPDATE tracks SET subgenre = NULL WHERE subgenre IN (SELECT id FROM _subgenres WHERE name = 'N/A')");
        DB::statement("UPDATE releases SET subgenre = NULL WHERE subgenre IN (SELECT id FROM _subgenres WHERE name = 'N/A')");
        DB::statement("DELETE FROM _subgenres WHERE name = 'N/A'");

        // 3. Alter columns on tracks table to unsignedBigInteger
        Schema::table('tracks', function (Blueprint $table) {
            $table->unsignedBigInteger('genre')->nullable()->change();
            $table->unsignedBigInteger('subgenre')->nullable()->change();
            $table->unsignedBigInteger('version')->nullable()->change();
        });

        // 4. Add foreign keys
        Schema::table('tracks', function (Blueprint $table) {
            $table->foreign('genre')->references('id')->on('_genres')->onDelete('restrict');
            $table->foreign('subgenre')->references('id')->on('_subgenres')->onDelete('restrict');
            $table->foreign('version')->references('id')->on('_versions')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropForeign(['genre']);
            $table->dropForeign(['subgenre']);
            $table->dropForeign(['version']);

            $table->string('genre', 100)->nullable()->change();
            $table->string('subgenre', 100)->nullable()->change();
            $table->string('version', 100)->nullable()->default('original')->change();
        });
    }
};
```

#### [MODIFY] [genre.json](file:///d:/Project/cms-soundfresh-id/database/seeders/data/genre.json)
Remove all `"N/A"` subgenre strings from this file so they are not seeded in the future.

#### [MODIFY] [SystemSeeder.php](file:///d:/Project/cms-soundfresh-id/database/seeders/SystemSeeder.php)
Ensure `"N/A"` subgenres are deleted when running the seeder.

```php
        // Deactivate subgenres belonging to deactivated genres
        $deactivatedGenreIds = Genre::where('is_active', false)->pluck('id');
        Subgenre::whereIn('genreid', $deactivatedGenreIds)->update(['is_active' => false]);

        // Permanently delete "N/A" subgenres
        Subgenre::where('name', 'N/A')->delete();
```

---

### Backend Logic

#### [MODIFY] [AlbumRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/AlbumRequest.php)
Update `subgenre` validation rules to be `nullable|integer` and return early for empty/`0` values.

```php
            'subgenre' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($release) {
                    if ($value === 0 || $value === '0' || empty($value)) {
                        return;
                    }
                    ...
```

#### [MODIFY] [TrackRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/TrackRequest.php)
Update `subgenre` validation rules to be `nullable|integer` and return early for empty/`0` values.

```php
            'subgenre'                  => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($track) {
                    if ($value === 0 || $value === '0' || empty($value)) {
                        return;
                    }
                    ...
```

#### [MODIFY] [ReleaseService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Releases/ReleaseService.php)
Sanitize input values in `store` method so that empty string/`0` for `genre`, `subgenre`, and `version` are converted to `NULL` before database writes.

```php
            // Bulk set attributes
            foreach (['title', 'titlelang', 'recordlabel', 'version', 'genre', 'subgenre', 'releaseddate', 'releaseon', 'upc', 'tiktok_ytmusic_rerelease_date'] as $key) {
                $val = isset($data[$key]) ? $data[$key] : null;
                if (in_array($key, ['genre', 'subgenre', 'version']) && ($val === 0 || $val === '0' || $val === '')) {
                    $val = null;
                }
                $release->{$key} = $val;
            }
```

```php
                    // Bulk set attributes
                    foreach (['title', 'titlelang', 'version', 'genre', 'subgenre', 'instrumental', 'lyrics', 'lyriclang', 'explicit', 'isrc', 'duration'] as $key) {
                        $val = $input_track[$key] ?? null;
                        if (in_array($key, ['genre', 'subgenre', 'version']) && ($val === 0 || $val === '0' || $val === '')) {
                            $val = null;
                        }
                        $track->{$key} = $val;
                    }
```

---

### Frontend / Views

#### [MODIFY] [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php)
Correct JavaScript/Alpine initialization so that `null` values output safely as string `""` in HTML/JS, preventing PHP output rendering syntax errors.

```javascript
                id:             {{ old('id', $release->id) ?? 0 }},
                version:        "{{ old('version', $release->version) ?? 1 }}",
                genre:          "{{ old('genre', $release->genre) ?? 1 }}",
                subgenre:       "{{ old('subgenre', $release->subgenre) ?? '' }}",
                released:       {{ old('released', $release->released) ?? 0 }},
```

---

### Test Cases

#### [MODIFY] [ReleaseOptionsTest.php](file:///d:/Project/cms-soundfresh-id/tests/Feature/ReleaseOptionsTest.php)
Update test data to use `null` instead of `0` for subgenres in request payloads.

---

## Verification Plan

### Automated Tests
* Run database migrations and seed system data:
  ```bash
  php artisan migrate:refresh --seed
  ```
* Run the feature tests to ensure release options, validation rules, and schema changes pass correctly:
  ```bash
  php artisan test tests/Feature/ReleaseOptionsTest.php
  ```

### Manual Verification
* Access the create release form.
* Save a draft with "No Subgenre" selected. Confirm no database error occurs and the subgenre column in the database is saved as `NULL`.
* Submit the release and confirm validation passes when subgenre is not set.

---

## Acceptance Criteria
- [ ] Migration converts `tracks` columns (`genre`, `subgenre`, `version`) to `unsignedBigInteger` and adds foreign keys.
- [ ] `"N/A"` subgenres are deleted from the database and not seeded from `genre.json`.
- [ ] Subgenres are nullable in form requests and database (saving as `NULL` instead of `0`).
- [ ] All tests in `ReleaseOptionsTest.php` pass successfully.
