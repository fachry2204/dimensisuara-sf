# SoundOn Style Publishing & Distribution Integration

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: create release (track creation)
- MODIFIED: create publishing request
- REMOVED: manage compositions

---

## Phase 1: Database Layer

**Task 1.1: Cleanup Unapplied Migrations**
- **File**: `database/migrations/2026_06_16_133656_add_composition_id_to_tracks.php`
- **File**: `database/migrations/2026_06_16_133656_create_compositions_table.php`
- **Code**: Delete these files physically.
- **Verification**: `dir database\migrations\*composition*` (Should return nothing).
- **Pattern**: Standard Laravel schema rollback procedure.

**Task 1.2: Add Fields to Tracks Table**
- **File**: `database/migrations/2026_06_17_120000_add_iswc_and_writer_percentage_to_tracks_table.php`
- **Code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('iswc')->nullable();
            $table->decimal('writer_percentage', 5, 2)->nullable();
        });
    }

    public function down(): void {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['iswc', 'writer_percentage']);
        });
    }
};
```
- **Verification**: `php artisan migrate` runs without error.
- **Pattern**: Laravel schema modification.

**Task 1.3: Add Fields to Publishing Songwriters Table**
- **File**: `database/migrations/2026_06_17_120001_add_lmk_and_publisher_to_publishing_songwriters_table.php`
- **Code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('publishing_songwriters', function (Blueprint $table) {
            $table->string('lmk')->nullable();
            $table->string('publisher')->nullable();
        });
    }

    public function down(): void {
        Schema::table('publishing_songwriters', function (Blueprint $table) {
            $table->dropColumn(['lmk', 'publisher']);
        });
    }
};
```
- **Verification**: `php artisan migrate` runs without error.
- **Pattern**: Laravel schema modification.

**Task 1.4: Update Models**
- **File**: `app/Models/Track.php`
- **Code**: 
Remove `public function composition()` and `composition_id` from `$fillable`.
Add `'iswc', 'writer_percentage'` to `$fillable`.
```php
// Replace fillable array
protected $fillable = [
    'release_id', 'profileid', 'title', 'version', 'performer', 'primary_artist', 'featuring', 'remixer', 'author', 'composer', 'arranger', 'producer', 'p_line', 'c_line', 'isrc', 'duration_minute', 'duration_second', 'genre', 'language', 'lyrics', 'audio_file', 'status', 'rejected_reason', 'iswc', 'writer_percentage'
];
// Remove: public function composition() { ... }
```
- **Verification**: `php artisan tinker --execute="App\Models\Track::first()"` does not error.
- **Pattern**: Eloquent Model properties.

---

## Phase 2: Backend Logic (Releases & Distribution)

**Task 2.1: Update Release Form Validation**
- **File**: `app/Http/Requests/StoreReleaseRequest.php`
- **Code**: 
```php
// Inside rules() array, replace composition rules with:
'tracks.*.writer_percentage' => 'required|numeric|min:0|max:100',
'tracks.*.iswc' => 'nullable|string|max:100',
```
- **Verification**: Post request with `writer_percentage = 150` fails validation.
- **Pattern**: Form Request validation per Laravel docs.

**Task 2.2: Update Release Service**
- **File**: `app/Services/Releases/ReleaseService.php`
- **Code**: 
```php
// In store() method, remove the entire $compositionMap logic block.
// Inside `foreach ($data['tracks'] as $trackData)` replace:
$track->composition_id = $compositionMap[$trackData['temp_id']] ?? null;

// With:
$track->writer_percentage = $trackData['writer_percentage'] ?? 0;
$track->iswc = $trackData['iswc'] ?? null;
```
- **Verification**: Create a track, verify db has values.
- **Pattern**: Service Layer pattern.

---

## Phase 3: Backend Logic (Publishing)

**Task 3.1: Update Publishing Request Validation**
- **File**: `app/Http/Requests/Publishing/StorePublishingRequest.php`
- **Code**: 
```php
// In rules() block, replace composition array logic with:
"songwriters"               => 'required|array|min:1',
"songwriters.*.id"          => 'nullable|integer',
"songwriters.*.name"        => 'required|string|max:255',
"songwriters.*.email"       => 'nullable|email|max:255',
"songwriters.*.ktp_num"     => 'nullable|string|max:255',
"songwriters.*.role"        => 'required|in:author,composer,author_composer',
"songwriters.*.publisher"   => 'nullable|string|max:255',
"songwriters.*.lmk"         => 'nullable|string|max:255',
"songwriters.*.share"       => 'required|numeric|min:0|max:100',

// In after() block:
function (Validator $validator) {
    if ($this->status !== 'draft') {
        $songwriters = $this->input('songwriters');
        if (isset($songwriters) && is_array($songwriters) && count($songwriters) > 0) {
            $total = 0;
            foreach ($songwriters as $sw) { $total += (float)($sw['share'] ?? 0); }
            if (abs($total - 100) >= 0.01) {
                $validator->errors()->add('songwriters', 'Total songwriter share must be 100%');
            }

            $firstEntry = $songwriters[0];
            $profile = current_user()->profile;
            $ownerName = trim($profile->first_name . ' ' . $profile->last_name) ?: current_user()->name;
            
            if (trim($firstEntry['name']) !== $ownerName) {
                $validator->errors()->add('songwriters', 'The first songwriter must match your account name.');
            }
            if (trim($firstEntry['email']) !== current_user()->email) {
                $validator->errors()->add('songwriters', 'The first songwriter must match your account email.');
            }
        }
    }
}
```
- **Verification**: Submit form with invalid name, see error.
- **Pattern**: Form Request After Validation Hooks.

**Task 3.2: Update Publishing Service**
- **File**: `app/Services/Publishing/PublishingService.php`
- **Code**: 
```php
// In create(): Replace Composition logic with:
$publishing->songwriters = json_encode($data['songwriters'] ?? []);
$publishing->composition_id = null;
$publishing->due_date = null;
$publishing->save();

// In publish(): Replace Composition SplitSheet extraction loop with:
foreach (json_decode($publishing->songwriters, true) as $sw) {
    $songwriter = \App\Models\Publishing\Songwriter::updateOrCreate(
        ['email' => $sw['email'], 'profileid' => $publishing->profileid],
        [
            'full_name' => $sw['name'],
            'ktp_num' => $sw['ktp_num'] ?? null,
            'lmk' => $sw['lmk'] ?? null,
            'publisher' => $sw['publisher'] ?? null,
        ]
    );

    $publishing->signees()->create([
        'token' => \Illuminate\Support\Str::uuid(),
        'profileid' => $publishing->profileid,
        'songwriterid' => $songwriter->id,
        'role' => $sw['role'],
        'share' => round((float) $sw['share'], 3),
        'status' => 'pending',
    ]);
}
$publishing->songwriters = null;
$publishing->save();
```
- **Verification**: Publish draft, check `publishing_signees` table populates.
- **Pattern**: Service Layer business logic.

---

## Phase 4: Frontend UI

**Task 4.1: Track Modal Simplification**
- **File**: `resources/views/customer/releases/create.blade.php`
- **Code**: Replace the `Composition & Split Sheet` block inside the track modal with:
```html
<label class="form-label mt-3">What percentage of this track was written by you?</label>
<div class="input-group mb-3">
    <input type="number" class="form-control" name="writer_percentage" x-model="track.writer_percentage" min="0" max="100">
    <span class="input-group-text">%</span>
</div>
<label class="form-label">ISWC (Optional)</label>
<input type="text" class="form-control mb-3" name="iswc" x-model="track.iswc">
```
- **Verification**: View Release track modal, confirm simpler fields load.

**Task 4.2: Publishing Form Overhaul**
- **File**: `resources/views/customer/publishing/create.blade.php`
- **Code**: Replace `composition_draft` logic.
```html
<div class="form-group mb-4">
    <label class="form-label">Select Track</label>
    <select class="form-select" x-model="$store.publishing.track_id" @change="$store.publishing.methods.loadTrackData()">
        <option value="">-- Custom --</option>
        <template x-for="track in $store.publishing.tracks" :key="track.id">
            <option :value="track.id" x-text="track.title"></option>
        </template>
    </select>
</div>

<!-- Songwriter UI inside x-data loop -->
<h5 class="mb-3">Songwriter List</h5>
<template x-for="(entry, idx) in $store.publishing.songwriters" :key="idx">
    <div class="border p-3 mb-3 rounded bg-light">
        <input type="hidden" :name="`songwriters[${idx}][id]`" x-model="entry.id">
        <div class="row">
            <div class="col-md-6 mb-2">
                <input type="text" class="form-control" placeholder="Name" :name="`songwriters[${idx}][name]`" x-model="entry.name" :readonly="idx === 0">
            </div>
            <div class="col-md-6 mb-2">
                <input type="email" class="form-control" placeholder="Email" :name="`songwriters[${idx}][email]`" x-model="entry.email" :readonly="idx === 0">
            </div>
            <div class="col-md-6 mb-2">
                <input type="text" class="form-control" placeholder="Citizen ID (KTP)" :name="`songwriters[${idx}][ktp_num]`" x-model="entry.ktp_num" :readonly="idx === 0">
            </div>
            <div class="col-md-6 mb-2">
                <select class="form-select" :name="`songwriters[${idx}][role]`" x-model="entry.role">
                    <option value="author">Author</option>
                    <option value="composer">Composer</option>
                    <option value="author_composer">Author & Composer</option>
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <input type="text" class="form-control" placeholder="Publisher" :name="`songwriters[${idx}][publisher]`" x-model="entry.publisher" :readonly="idx === 0">
            </div>
            <div class="col-md-6 mb-2">
                <input type="text" class="form-control" placeholder="LMK" :name="`songwriters[${idx}][lmk]`" x-model="entry.lmk">
            </div>
            <div class="col-md-6">
                <div class="input-group">
                    <input type="number" class="form-control" placeholder="Share" :name="`songwriters[${idx}][share]`" x-model="entry.share">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <template x-if="idx !== 0">
                    <button type="button" class="btn btn-outline-danger" @click="$store.publishing.songwriters.splice(idx, 1)">Remove</button>
                </template>
            </div>
        </div>
    </div>
</template>
<button type="button" class="btn btn-sm btn-secondary" @click="$store.publishing.songwriters.push({name:'', email:'', ktp_num:'', role:'author', publisher:'', lmk:'', share:0})">+ Add Writer</button>
```
In Alpine state (`init` / `methods`):
```javascript
loadTrackData() {
    if(!this.track_id) return;
    const track = this.tracks.find(t => t.id == this.track_id);
    if(track) {
        this.title = track.title;
        this.iswc = track.iswc;
        this.genre = track.genre;
        this.language = track.language;
        this.duration_minute = track.duration_minute;
        this.duration_second = track.duration_second;
        
        // Auto-fill first songwriter
        this.songwriters[0] = {
            id: null,
            name: "{{ current_user()->profile->first_name ?? '' }} {{ current_user()->profile->last_name ?? '' }}".trim() || "{{ current_user()->name ?? '' }}",
            email: "{{ current_user()->email ?? '' }}",
            ktp_num: "{{ current_user()->profile->ktp_num ?? '' }}",
            role: 'author_composer',
            publisher: 'Soundfresh',
            lmk: '',
            share: track.writer_percentage || 0
        };
    }
}
```
- **Verification**: Select a track, see form prepopulate.
- **Pattern**: View-Model data binding (Alpine.js).

---

## Acceptance Criteria
- [ ] Composition migrations deleted; new tracks migration adds `writer_percentage` and `iswc`.
- [ ] Publishing songwriters table supports `lmk` and `publisher`.
- [ ] Creating a track accepts and validates `writer_percentage` (0-100).
- [ ] Selecting a track in Publishing auto-fills metadata and the first songwriter (user) locked to Soundfresh with the Track's `writer_percentage`.
- [ ] The Publishing backend correctly validates the total 100% share and first-user identity, generating standard `Signee` contracts dynamically.
