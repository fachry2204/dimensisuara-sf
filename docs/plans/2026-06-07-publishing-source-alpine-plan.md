# Implementation Plan: Link Alpine.js to Publishing Source Selection

## Phase 1: Restructure
N/A

## Phase 2: Database Layer
N/A

## Phase 3: Backend Logic

### [MODIFY] `routes/web/customer.php`
- In `routes/web/customer.php`, under the `Route::prefix('releases')` group, add a new AJAX route:
```php
Route::get('_ajax/{id}/tracks', [ReleasesController::class, 'get_tracks'])->name('releases.get_tracks');
```

### [MODIFY] `app/Http/Controllers/ReleasesController.php`
- Add `get_tracks` method to fetch tracks for a given release via AJAX:
```php
public function get_tracks($id)
{
    try {
        $release = \App\Models\Release::where('profileid', current_user()->profile->id)->findOrFail($id);
        
        $tracks = $release->tracks->map(function ($track) {
            return [
                'id' => $track->id,
                'title' => $track->title,
                // other track data as needed
            ];
        });

        return response()->json($tracks);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Release not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to fetch tracks'], 500);
    }
}
```

## Phase 4: Frontend

### [MODIFY] `resources/views/customer/publishing/create.blade.php`
1. Update Alpine.js store initialization to include state and fetch logic:
```javascript
Alpine.store('publishing', {
    init() {
        this.methods.changeDuration();

        if (this.songwriters.length === 0) {
            this.methods.addSongwriter();
        }

        // Initialize edit state
        if (this.release_id) {
            this.methods.fetchTracks(true); // Pass true to preserve selected track_id
        }
    },
    duration: $('#duration').val(),
    songwriters: JSON.parse(document.getElementById('json-songwriters').textContent),
    
    // Add new state variables
    source: '{{ old('source', $publishing->source ?? 'existing') }}',
    release_id: '{{ old('release_title', $publishing->release_title ?? '') }}',
    track_id: '{{ old('track_title', $publishing->track_title ?? '') }}',
    tracks: [],
    loading_tracks: false,
    fetch_error: false,
    
    methods: {
        // ... existing methods ...
        
        async fetchTracks(isEditInit = false) {
            if (!Alpine.store('publishing').release_id) {
                Alpine.store('publishing').tracks = [];
                return;
            }
            
            Alpine.store('publishing').loading_tracks = true;
            Alpine.store('publishing').fetch_error = false;
            
            try {
                const response = await fetch(`/releases/_ajax/${Alpine.store('publishing').release_id}/tracks`);
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                Alpine.store('publishing').tracks = data;
                
                if (!isEditInit) {
                    Alpine.store('publishing').track_id = ''; // reset track selection on new release pick
                }
            } catch (error) {
                console.error("Failed to fetch tracks", error);
                Alpine.store('publishing').fetch_error = true;
                Alpine.store('publishing').tracks = [];
            } finally {
                Alpine.store('publishing').loading_tracks = false;
            }
        }
    }
});
```
2. Update the HTML elements to bind to Alpine store:
- `source_existing` & `source_new` radio buttons: `x-model="$store.publishing.source"`
- `source_existing_field` & `source_new_field`: `x-show="$store.publishing.source === 'existing'"` & `x-show="$store.publishing.source === 'new'"`
- `release_title` select: `x-model="$store.publishing.release_id"` and `@change="$store.publishing.methods.fetchTracks()"`
- `track_title` select: `x-model="$store.publishing.track_id"`
- Inside `track_title` select, populate with:
  ```html
  <template x-for="track in $store.publishing.tracks" :key="track.id">
      <option :value="track.id" x-text="track.title"></option>
  </template>
  ```
- Add loading and error indicators next to the track select dropdown.

## Acceptance Criteria
- [ ] Radio buttons "existing" and "new" toggle the visibility of the fieldsets correctly using Alpine state.
- [ ] Selecting an existing release triggers an AJAX fetch and correctly populates the track select options.
- [ ] During AJAX fetch, a loading indicator is displayed. Error state is handled visually if AJAX fails.
- [ ] On edit, the `source`, `release_title` (id), and `track_title` (id) are restored from `old()` or existing `$publishing` correctly.
