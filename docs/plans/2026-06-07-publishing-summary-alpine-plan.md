# Implementation Plan: Form Review Summary & Data Auto-fill

## Phase 1: Restructure
N/A

## Phase 2: Database Layer
N/A

## Phase 3: Backend Logic

### [MODIFY] `app/Http/Controllers/ReleasesController.php`
- Update `get_tracks` method to return the complete track metadata needed for auto-filling the publishing form.
```php
public function get_tracks($id)
{
    try {
        $release = \App\Models\Release::where('profileid', current_user()->profile->id)->findOrFail($id);
        
        $tracks = $release->tracks->map(function ($track) {
            $primaryartists = json_decode($track->attributes['primaryartists'] ?? '[]', true);
            $performer = is_array($primaryartists) ? implode(', ', array_column($primaryartists, 'name')) : '';
            
            // Duration calculation
            $duration = (int) $track->duration;
            $duration_minute = floor($duration / 60);
            $duration_second = $duration % 60;

            return [
                'id' => $track->id,
                'title' => $track->title,
                'performer' => $performer,
                'genre' => $track->genre,
                'language' => $track->titlelang,
                'isrc' => $track->isrc,
                'duration_minute' => $duration_minute,
                'duration_second' => $duration_second,
                'sample' => $track->trimmed_url ?? $track->full_url ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'tracks' => $tracks
        ], 200);
    }
    catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
        // ... existing error handling
    }
}
```

## Phase 4: Frontend

### [MODIFY] `resources/views/customer/publishing/create.blade.php`

#### 1. Update Alpine Store State
Expand `Alpine.store('publishing')` to include all form fields.
```javascript
// Inside Alpine.store('publishing')
                title: '{{ old('title', $publishing->title ?? '') }}',
                other_title: '{{ old('other_title', $publishing->other_title ?? '') }}',
                sample: '{{ old('sample', $publishing->sample ?? '') }}',
                performer: '{{ old('performer', $publishing->performer ?? '') }}',
                duration_minute: '{{ old('duration_minute', $publishing->duration_minute ?? '') }}',
                duration_second: '{{ old('duration_second', $publishing->duration_second ?? '') }}',
                genre: '{{ old('genre', $publishing->genre ?? '') }}',
                language: '{{ old('language', $publishing->language ?? 'id') }}',
                iswc: '{{ old('iswc', $publishing->iswc ?? '') }}',
                isrc: '{{ old('isrc', $publishing->isrc ?? '') }}',
                sync_license_type: '{{ old('sync_license_type', $publishing->sync_license_type ?? 'exclusive') }}',
                region: '{{ old('region', $publishing->region ?? 'worldwide') }}',
                note: `{{ old('note', $publishing->note ?? '') }}`,
                is_autofilled: false, // Tracks if inputs are currently locked by auto-fill
```

#### 2. Bind HTML Inputs to Alpine
Update all existing standard HTML inputs to use `x-model="$store.publishing.[field]"`.
Also bind `:readonly="$store.publishing.is_autofilled"` to lock auto-filled fields.
*(Example for Title, apply to all)*:
```html
<input type="text" class="form-control" id="title" name="title" x-model="$store.publishing.title" :readonly="$store.publishing.is_autofilled">
```

#### 3. Implement Auto-fill Logic
Add a `selectTrack()` method that is triggered when the `track_id` dropdown changes (`@change="$store.publishing.methods.selectTrack()"`).
```javascript
// Inside methods
selectTrack() {
    const trackId = parseInt(Alpine.store('publishing').track_id);
    if (!trackId) {
        Alpine.store('publishing').is_autofilled = false;
        return;
    }
    
    const track = Alpine.store('publishing').tracks.find(t => t.id === trackId);
    if (track) {
        Alpine.store('publishing').title = track.title || '';
        Alpine.store('publishing').performer = track.performer || '';
        Alpine.store('publishing').genre = track.genre || '';
        Alpine.store('publishing').language = track.language || 'id';
        Alpine.store('publishing').isrc = track.isrc || '';
        Alpine.store('publishing').duration_minute = track.duration_minute || 0;
        Alpine.store('publishing').duration_second = track.duration_second || 0;
        Alpine.store('publishing').sample = track.sample || '';
        Alpine.store('publishing').is_autofilled = true;
    }
}
```

#### 4. Add Review Summary UI
In `<div id="step-4">`, just above the terms and conditions checkbox, add the summary grid:
```html
<h4 class="mb-3">Review Summary</h4>
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <h5>Song Metadata</h5>
        <table class="table table-sm table-borderless">
            <tr><th style="width: 150px">Title</th><td><span x-text="$store.publishing.title || 'Not provided'" :class="{'text-muted': !$store.publishing.title}"></span></td></tr>
            <tr><th>Performer</th><td><span x-text="$store.publishing.performer || 'Not provided'" :class="{'text-muted': !$store.publishing.performer}"></span></td></tr>
            <tr><th>Duration</th><td><span x-text="`${$store.publishing.duration_minute}:${$store.publishing.duration_second.toString().padStart(2, '0')}`"></span></td></tr>
            <tr><th>Genre</th><td><span x-text="$store.publishing.genre || 'Not provided'" :class="{'text-muted': !$store.publishing.genre}"></span></td></tr>
            <tr><th>ISRC</th><td><span x-text="$store.publishing.isrc || 'Not provided'" :class="{'text-muted': !$store.publishing.isrc}"></span></td></tr>
        </table>
    </div>
    <div class="col-md-6 mb-3">
        <h5>Publishing Details</h5>
        <table class="table table-sm table-borderless">
            <tr><th style="width: 150px">Sync License</th><td><span x-text="$store.publishing.sync_license_type" class="text-capitalize"></span></td></tr>
            <tr><th>Region</th><td><span x-text="$store.publishing.region || 'Not provided'" :class="{'text-muted': !$store.publishing.region}"></span></td></tr>
            <tr><th>Note</th><td><span x-text="$store.publishing.note || 'Not provided'" :class="{'text-muted': !$store.publishing.note}"></span></td></tr>
        </table>
    </div>
</div>
```

## Acceptance Criteria
- [ ] AJAX endpoint returns complete track metadata including calculated duration.
- [ ] Selecting a track auto-fills the hidden metadata fields and marks them as locked (readonly).
- [ ] The "Review" step visually displays a formatted summary of all input fields (Song Metadata, Publishing Details).
- [ ] Form submission successfully saves the track metadata.
