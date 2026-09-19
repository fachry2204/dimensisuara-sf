## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: create release (track creation)
- MODIFIED: create publishing request
- NEW: manage compositions (musical works)
- REMOVED: (none)

---

## Phase 1: Database Layer

**Task 1.1: Create Migrations for Compositions & Split Sheets**
- **File**: `database/migrations/YYYY_MM_DD_HHMMSS_create_compositions_table.php`
- **Code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->constrained('user_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('iswc')->nullable();
            $table->timestamps();
        });

        Schema::create('split_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')->constrained('compositions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('split_sheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('split_sheet_id')->constrained('split_sheets')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('role', ['author', 'composer', 'author_composer']);
            $table->decimal('share', 5, 2)->nullable();
            $table->string('lmk')->nullable();
            $table->string('publisher')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('split_sheet_entries');
        Schema::dropIfExists('split_sheets');
        Schema::dropIfExists('compositions');
    }
};
```

**Task 1.2: Update Tracks and Publishings Tables**
- **File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_composition_id_to_tracks.php`
- **Code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tracks', function (Blueprint $table) {
            $table->foreignId('composition_id')->nullable()->constrained('compositions')->nullOnDelete();
        });
        Schema::table('publishings', function (Blueprint $table) {
            $table->foreignId('composition_id')->nullable()->constrained('compositions')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropForeign(['composition_id']);
            $table->dropColumn('composition_id');
        });
        Schema::table('publishings', function (Blueprint $table) {
            $table->dropForeign(['composition_id']);
            $table->dropColumn('composition_id');
        });
    }
};
```

**Task 1.3: Create Models**
- **File**: `app/Models/Composition.php`
- **Code**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Composition extends Model {
    protected $fillable = ['profileid', 'title', 'iswc'];

    public function splitSheet() {
        return $this->hasOne(SplitSheet::class);
    }
    
    public function tracks() {
        return $this->hasMany(Track::class);
    }
}
```
- **File**: `app/Models/SplitSheet.php`
- **Code**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitSheet extends Model {
    protected $fillable = ['composition_id'];

    public function entries() {
        return $this->hasMany(SplitSheetEntry::class);
    }
}
```
- **File**: `app/Models/SplitSheetEntry.php`
- **Code**:
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SplitSheetEntry extends Model {
    protected $fillable = [
        'split_sheet_id', 'name', 'email', 'phone', 'role', 'share', 'lmk', 'publisher'
    ];
}
```
- **File**: `app/Models/Track.php`
- **Modification**: Add `public function composition() { return $this->belongsTo(Composition::class); }`
- **Verification**: Run `php artisan migrate:fresh --seed` (or normal migrate) and verify foreign keys are applied correctly.

---

## Phase 2: Backend Logic

**Task 2.1: Composition API Endpoint (Dropdown Search)**
- **File**: `routes/web/customer.php`
  - **Code**: `Route::get('compositions/search', [CompositionController::class, 'search'])->name('compositions.search');`
- **File**: `app/Http/Controllers/CompositionController.php` (NEW)
- **Code**:
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Composition;

class CompositionController extends Controller {
    public function search(Request $request) {
        $query = $request->input('q');
        $compositions = Composition::where('profileid', current_user()->profile->id)
            ->where('title', 'LIKE', "%{$query}%")
            ->with('splitSheet.entries')
            ->limit(10)
            ->get();
        return response()->json(['success' => true, 'compositions' => $compositions], 200);
    }
}
```

**Task 2.2: Release Validation**
- **File**: `app/Http/Requests/StoreReleaseRequest.php` (if exists) or within validation service.
- **Code**: Add rules to validate that each object in the `compositions` array has a `title`, and that the sum of `share` in its `entries` equals exactly `100.00`.

**Task 2.3: Release Service Update (IDOR + Logic + Audit)**
- **File**: `app/Services/Releases/ReleaseService.php`
- **Code**: Modify `store` method.
```php
// Inside try block of store()
$profileid = current_user()->profile->id;
$compositionMap = []; // Map frontend refs to database IDs

if (isset($data['compositions']) && is_array($data['compositions'])) {
    foreach ($data['compositions'] as $compData) {
        if (!empty($compData['id'])) {
            // Validate ownership (IDOR prevention)
            $composition = Composition::where('profileid', $profileid)
                                      ->findOrFail($compData['id']);
            $compositionMap[$compData['ref']] = $composition->id;
            
            // Check if composition is already locked by a publishing contract
            $isLocked = Publishing::where('composition_id', $composition->id)
                                  ->where('status', '!=', 'draft')->exists();
                                  
            if (!$isLocked) {
                // Safe to update title or split sheet
                $composition->update(['title' => $compData['title'], 'iswc' => $compData['iswc'] ?? null]);
                $composition->splitSheet()->delete(); // Rebuild entries
                $splitSheet = $composition->splitSheet()->create();
                foreach ($compData['entries'] as $entry) {
                    $splitSheet->entries()->create($entry);
                }
            }
        } else {
            // Create new composition
            $composition = Composition::create([
                'profileid' => $profileid,
                'title' => $compData['title'],
                'iswc' => $compData['iswc'] ?? null,
            ]);
            $splitSheet = $composition->splitSheet()->create();
            foreach ($compData['entries'] as $entry) {
                $splitSheet->entries()->create($entry);
            }
            $compositionMap[$compData['ref']] = $composition->id;
            
            \Log::info("Composition created '{$composition->title}'", ['model_id' => $composition->id]);
        }
    }
}

// When saving tracks:
foreach ($data['tracks'] as $trackData) {
    // ... setup track model ...
    if (isset($trackData['composition_ref']) && isset($compositionMap[$trackData['composition_ref']])) {
        $track->composition_id = $compositionMap[$trackData['composition_ref']];
    }
    $track->save();
}
```

**Task 2.4: Publishing Controller Modification**
- **File**: `app/Http/Controllers/PublishingController.php`
- **Code**: When `create()` is called via a track, auto-fill the form with `Track::with('composition.splitSheet.entries')`. Ensure songwriters array is pre-populated.

---

## Phase 3: Frontend

**Task 3.1: Alpine Store Update**
- **File**: `<script>` tag inside `resources/views/customer/releases/create.blade.php` (or extracted JS file)
- **Code**:
```javascript
Alpine.store('release', {
    // ... existing properties ...
    compositions: [],
    
    addComposition() {
        this.compositions.push({
            ref: Date.now().toString(), // unique ref
            id: null, // null if unsaved
            title: '',
            iswc: '',
            entries: [{ name: '', email: '', phone: '', role: 'author', share: 0, lmk: '', publisher: 'Soundfresh' }]
        });
    },
    
    validateShare(compIndex) {
        let total = this.compositions[compIndex].entries.reduce((sum, entry) => sum + parseFloat(entry.share || 0), 0);
        return total === 100;
    }
});
```

**Task 3.2: Blade Track Modal Redesign (2-Column)**
- **File**: `resources/views/customer/releases/create.blade.php`
- **Code**: Inside the track modal HTML:
```html
<div class="row">
    <!-- Left Column: Track Details -->
    <div class="col-md-6 border-end pr-4">
        <h4>Track Details</h4>
        <!-- Upload Audio, Title, ISRC, Explicit inputs go here -->
    </div>

    <!-- Right Column: Composition / Split Sheet -->
    <div class="col-md-6 pl-4">
        <h4>Composition & Split Sheet</h4>
        
        <!-- Selection Mode -->
        <div class="mb-3">
            <select class="form-select" x-model="track.composition_ref" @change="handleCompositionSelect($event)">
                <option value="">-- Create New Composition --</option>
                <template x-for="comp in $store.release.compositions" :key="comp.ref">
                    <option :value="comp.ref" x-text="comp.title"></option>
                </template>
            </select>
        </div>

        <!-- Split Sheet Builder (shown if editing the active ref) -->
        <template x-if="track.composition_ref">
            <div x-data="{ comp: $store.release.compositions.find(c => c.ref === track.composition_ref) }">
                <input type="text" x-model="comp.title" placeholder="Composition Title" class="form-control mb-2">
                <input type="text" x-model="comp.iswc" placeholder="ISWC (Optional)" class="form-control mb-3">
                
                <h5>Split Sheet Entries</h5>
                <template x-for="(entry, idx) in comp.entries" :key="idx">
                    <div class="card mb-2 p-2">
                        <input type="text" x-model="entry.name" placeholder="Name" class="form-control mb-1">
                        <div class="d-flex gap-2 mb-1">
                            <input type="email" x-model="entry.email" placeholder="Email" class="form-control">
                            <input type="text" x-model="entry.phone" placeholder="Phone" class="form-control">
                        </div>
                        <div class="d-flex gap-2">
                            <select x-model="entry.role" class="form-select">
                                <option value="author">Author</option>
                                <option value="composer">Composer</option>
                                <option value="author_composer">Author & Composer</option>
                            </select>
                            <input type="number" x-model="entry.share" placeholder="%" class="form-control" style="width: 80px;">
                        </div>
                    </div>
                </template>
                <button type="button" class="btn btn-sm btn-secondary" @click="comp.entries.push({name:'', email:'', phone:'', role:'author', share:0})">+ Add Writer</button>
                
                <!-- Validation Badge -->
                <div class="mt-2 text-end">
                    <span class="badge" 
                          :class="$store.release.validateShare($store.release.compositions.indexOf(comp)) ? 'bg-success' : 'bg-danger'"
                          x-text="'Total Share: ' + comp.entries.reduce((sum, e) => sum + Number(e.share), 0) + '%'">
                    </span>
                </div>
            </div>
        </template>
    </div>
</div>
```

## Phase 4: Publishing Flow Integration (New Request)

**Task 4.1: Update Publishing Request UI**
- **File**: `resources/views/customer/publishing/create.blade.php`
- **Code**: Replace the `Songwriter List` step with a `Composition & Split Sheet` step.
- Integrate the identical AlpineJS logic used in the track modal: 
  - `composition_ref` and `composition_draft` state.
  - Dropdown to select existing Compositions fetched via `/compositions/search`.
  - Lock the first entry's Name, Email, and Phone to the current user (just like in tracks).
- Remove the legacy `songwriters` array logic.

**Task 4.2: Update StorePublishingRequest Validation**
- **File**: `app/Http/Requests/Publishing/StorePublishingRequest.php`
- **Code**: Remove `songwriters.*` rules. Add validation for `composition` and `entries`.
  - Validate total share == 100%.
  - Validate first entry matches the submitting user's profile.

**Task 4.3: Refactor PublishingService@create**
- **File**: `app/Services/Publishing/PublishingService.php`
- **Code**: Instead of saving `songwriters` JSON to the database, process the composition logic (same as `ReleaseService`):
  - If selecting an existing composition, fetch and update it (if not locked).
  - If creating new, generate `Composition` and `SplitSheet`.
  - Set `composition_id` on the `Publishing` model.
  - Set `$publishing->songwriters = null;` since it's legacy.

**Task 4.4: Refactor PublishingService@publish (Signees)**
- **File**: `app/Services/Publishing/PublishingService.php`
- **Code**: Instead of looping through `json_decode($publishing->songwriters)`, loop through `$publishing->composition->splitSheet->entries`.
- To maintain compatibility with the existing `publishing_signees` table and signature flow, auto-find or create a `Songwriter` model based on the `SplitSheetEntry`'s email and name.
- Create the `Signee` record tied to that `Songwriter`.

---

## Acceptance Criteria
- [ ] Database migrations drop legacy composer fields and establish relational tables (Compositions, Split Sheets, Entries) with rollback `down()` methods.
- [ ] Release backend properly handles `compositions` array in payload, using `ref` logic to map new tracks to the unsaved composition.
- [ ] IDOR protection implemented: existing compositions are validated against `profileid`.
- [ ] Editing tracks with locked compositions creates a new composition or rejects the edit.
- [ ] Alpine.js efficiently maintains the frontend draft of the arrays without full page reloads.
- [ ] Blade Track Modal renders cleanly in a two-column responsive layout.
- [ ] Users are blocked from submitting the form if total shares per composition != 100%.
- [ ] Publishing form replaces legacy songwriter array with Composition UI.
- [ ] Publishing request gracefully creates/updates Compositions and auto-generates Signees behind the scenes for contract signature workflows.
 
 