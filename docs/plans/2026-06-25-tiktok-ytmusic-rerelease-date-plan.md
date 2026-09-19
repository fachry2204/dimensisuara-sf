# Implementation Plan - TikTok & YouTube Music Re-Release Date

Add an optional TikTok & YouTube Music re-release date under the release detail section of the aggregator release request.

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: create release, edit release, view release
- NEW: (none)
- REMOVED: (none)

---

## Proposed Changes

### Database Layer

#### [NEW] [2026_06_25_214932_add_tiktok_ytmusic_rerelease_date_to_releases_table.php](file:///d:/Project/cms-soundfresh-id/database/migrations/2026_06_25_214932_add_tiktok_ytmusic_rerelease_date_to_releases_table.php)
Create a new migration to add the `tiktok_ytmusic_rerelease_date` column to the `releases` table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->date('tiktok_ytmusic_rerelease_date')->nullable()->after('releaseon');
        });
    }

    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropColumn('tiktok_ytmusic_rerelease_date');
        });
    }
};
```

*Verification*: Run `php artisan migrate` and verify the table structure of `releases`.

#### [MODIFY] [Release.php](file:///d:/Project/cms-soundfresh-id/app/Models/Release.php)
Whitelist the new column in the `$fillable` array.

```diff
     protected $fillable = [
         'profileid',
         'old_album_id',
         'old_track_id',
         'type',
         'tracknum',
         'title',
         'titlelang',
         'primaryartists',
         'recordlabel',
         'version',
         'genre',
         'subgenre',
         'releasedir',
         'released',
         'releaseddate',
         'finalreleaseddate',
         'upc',
         'aggregator',
         'status',
         'reason',
         'releaseon',
+        'tiktok_ytmusic_rerelease_date',
         'delete_by',
         'verified_at',
         'contract_expired_at',
         'contract_auto_renew',
     ];
```

---

### Backend Layer

#### [MODIFY] [AlbumRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/AlbumRequest.php)
Add validation rules for `tiktok_ytmusic_rerelease_date`. Prepare with a comment for future offset checks.

```diff
     public function rules(): array
     {
         return [
             'title' => 'required|string|max:255',
             'titlelang' => 'required|string|max:255',
             'primaryartists' => 'required|array|min:1',
             'primaryartists.*.name' => 'required|filled|string|max:100',
             'primaryartists.*.social' => 'required|filled|string',
             'primaryartists.*.spotify' => 'nullable|string',
             'recordlabel' => 'required|string|max:100',
             'version' => 'required|integer',
             'genre' => 'required|integer',
             'subgenre' => 'required|integer',
             'released' => 'required|boolean',
             'releaseddate' => 'nullable|required_if:released,1|date',
             'upc' => 'nullable|required_if:released,1|string|max:15',
             'releaseon' => [
                 'required',
                 'date',
                 function ($attribute, $value, $fail) {
                     if (!Carbon::parse($value)->isAfter(today()->addDays(13))) {
                         $fail('The planned release date must be at least 14 days from today.');
                     }
                 },
             ],
+            'tiktok_ytmusic_rerelease_date' => [
+                'nullable',
+                'date',
+                // Comment for preparation of future offset validation:
+                // function ($attribute, $value, $fail) {
+                //     if ($value && !Carbon::parse($value)->isAfter(today()->addDays(13))) {
+                //         $fail('The TikTok & YouTube Music re-release date must be at least 14 days from today.');
+                //     }
+                // }
+            ],
             'tracknum' => 'required|integer|min:1',
             'type' => 'required|string|in:single,album',
             'status' => 'required|string|in:draft,rejected',
         ];
     }
```

#### [MODIFY] [ReleaseService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Releases/ReleaseService.php)
Add the date column to the attribute bulk-assignment array in the `store()` method.

```diff
             // Bulk set attributes
-            foreach (['title', 'titlelang', 'recordlabel', 'version', 'genre', 'subgenre', 'releaseddate', 'releaseon', 'upc'] as $key) {
+            foreach (['title', 'titlelang', 'recordlabel', 'version', 'genre', 'subgenre', 'releaseddate', 'releaseon', 'upc', 'tiktok_ytmusic_rerelease_date'] as $key) {
                 $release->{$key} = isset($data[$key]) ? $data[$key] : null;
             }
```

#### [MODIFY] [ReleasesController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/ReleasesController.php)
Whitelist the column in request parameter extraction for both `store()` and `update()`.

```diff
         // Extract data before try block for error logging
         $data = $request->only([
             'title',
             'titlelang',
             'recordlabel',
             'version',
             'ipl',
             'genre',
             'subgenre',
             'released',
             'releaseddate',
             'releaseon',
             'upc',
             'primaryartists',
             'type',
             'tracks',
             'tracks_json',
             'cover',
+            'tiktok_ytmusic_rerelease_date',
         ]);
```

#### [MODIFY] [MainController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/Admin/Aggregator/MainController.php)
Whitelist the column in the admin controller's `store()` and `update()` methods as well.

```diff
         // Extract data before try block for error logging
         $data = $request->only([
             'title',
             'titlelang',
             'recordlabel',
             'version',
             'ipl',
             'genre',
             'subgenre',
             'released',
             'releaseddate',
             'releaseon',
             'upc',
             'primaryartists',
             'type',
             'tracks',
             'tracks_json',
             'cover',
+            'tiktok_ytmusic_rerelease_date',
         ]);
```

#### [MODIFY] [validation.php (English)](file:///d:/Project/cms-soundfresh-id/resources/lang/en/validation.php)
Add translation attribute name mapping.

```diff
         'upc'                   => 'UPC',
         'releaseon'             => 'Release Date',
+        'tiktok_ytmusic_rerelease_date' => 'TikTok & YouTube Music Re-Release Date',
 
         'primaryartists'        => 'Primary Artists',
```

#### [MODIFY] [validation.php (Indonesian)](file:///d:/Project/cms-soundfresh-id/resources/lang/id/validation.php)
Add translation attribute name mapping.

```diff
         'upc'               => 'UPC',
         'releaseon'         => 'Tanggal rilis',
+        'tiktok_ytmusic_rerelease_date' => 'Tanggal Rilis Ulang TikTok & YouTube Music',
         'primaryartists'    => 'Artis utama',
```

---

### Frontend Layer

#### [MODIFY] [create.blade.php (Customer View)](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php)
1. Add the input field in step 3 (Release Detail) under "Planned Release Date".
2. Add the field initialization to the Alpine.js `$store.release` definition.

```diff
                         <div class="form-group mb-3">
                             <label for="releaseon" class="form-label">Planned Release Date</label>
                             <p>The planned release date should be at least 14 days from today.</p>
                             <input type="date" class="form-control" name="releaseon" id="releaseon"
                                 x-model="$store.release.releaseon" required
                                 min="{{ date('Y-m-d', strtotime('+14 days')) }}">
                         </div>
+                        <div class="form-group mb-3">
+                            <label for="tiktok_ytmusic_rerelease_date" class="form-label">TikTok & YouTube Music Re-Release Date (Optional)</label>
+                            <input type="date" class="form-control" name="tiktok_ytmusic_rerelease_date" id="tiktok_ytmusic_rerelease_date"
+                                x-model="$store.release.tiktok_ytmusic_rerelease_date">
+                            <!-- Preparation: date offset validation logic can be added/commented out here if needed in the future -->
+                        </div>
                     </div>
```

```diff
             Alpine.store('release', {
                 primaryartists: releasePrimaryArtist,
                 subgenres: JSON.parse(document.getElementById('json-subgenres').textContent),
                 tracks: JSON.parse(document.getElementById('json-tracks').textContent),
                 coverImage: @if($release->cover_url) "{{ $release->cover_url }}" @else"{{URL::asset('build/images/album-placeholder.webp')}}" @endif,
                 id:             {{ old('id', $release->id) ?? 0 }},
                 version:        {{ old('version', $release->version) ?? 1 }},
                 genre:          {{ old('genre', $release->genre) ?? 1 }},
                 subgenre:       {{ old('subgenre', $release->subgenre) ?? 0 }},
                 released:       {{ old('released', $release->released) ?? 0 }},
                 title: "{{ old('title', $release->title) ?? '' }}",
                 recordlabel: "{{ old('recordlabel', $release->recordlabel) ?? '' }}",
                 upc: "{{ old('upc', $release->upc) ?? '' }}",
                 releaseon: "{{ old('releaseon', $release->releaseon) ?? '' }}",
+                tiktok_ytmusic_rerelease_date: "{{ old('tiktok_ytmusic_rerelease_date', $release->tiktok_ytmusic_rerelease_date) ?? '' }}",
                 iplUrl: "{{ $release->ipl_url ?? '' }}",
```

#### [MODIFY] [create.blade.php (Admin View)](file:///d:/Project/cms-soundfresh-id/resources/views/admin/aggregator/create.blade.php)
Add the date input field in the Release Date Details section.

```diff
                 <div class="form-group mb-3">
                     <label for="releaseon" class="form-label">Planned Release Date</label>
                     <input type="date" class="form-control" name="releaseon" id="releaseon"
                         value="{{ old('releaseon', $release->releaseon) }}" required>
                 </div>
+                <div class="form-group mb-3">
+                    <label for="tiktok_ytmusic_rerelease_date" class="form-label">TikTok & YouTube Music Re-Release Date (Optional)</label>
+                    <input type="date" class="form-control" name="tiktok_ytmusic_rerelease_date" id="tiktok_ytmusic_rerelease_date"
+                        value="{{ old('tiktok_ytmusic_rerelease_date', $release->tiktok_ytmusic_rerelease_date) }}">
+                </div>
                 <div class="form-group mb-3">
                     <label class="form-label" for="upc">UPC</label>
```

#### [MODIFY] [release.blade.php Component](file:///d:/Project/cms-soundfresh-id/resources/views/components/release.blade.php)
Render the "TikTok & YouTube Music Re-Release Date" row if it has a value.

```diff
                                 @if($release->released)
                                     <tr>
                                         <td>Original Released Date</td>
                                         <td>{{ \Carbon\Carbon::parse($release->releaseddate)->format('F j, Y') }}</td>
                                     </tr>
                                 @endif
+                                @if($release->tiktok_ytmusic_rerelease_date)
+                                    <tr>
+                                        <td>TikTok & YouTube Music Re-Release Date</td>
+                                        <td>{{ \Carbon\Carbon::parse($release->tiktok_ytmusic_rerelease_date)->format('F j, Y') }}</td>
+                                    </tr>
+                                @endif
                                 <tr>
                                     <td>Version</td>
```

---

## Verification Plan

### Automated Tests
* None.

### Manual Verification
1. Create a release/album request as a customer.
2. In Step 3, enter a date under "TikTok & YouTube Music Re-Release Date".
3. Save draft and confirm that the field is successfully persisted to the database.
4. Open the "View Release" page and verify the date displays correctly in the Details section.
5. Edit the release request as an Admin, edit the re-release date, and save.

---

## Acceptance Criteria
- [ ] Users can enter an optional TikTok & YouTube Music re-release date when requesting/editing a release.
- [ ] Admins can view and edit the TikTok & YouTube Music re-release date.
- [ ] The date is displayed in the release details component if populated.
