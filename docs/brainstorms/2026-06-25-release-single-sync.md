# Brainstorm: Single Release Layout & Form Synchronization

## Requirements

1. **Unique Form for Single Release**:
   - Single release type can only have 1 track.
   - Eliminate duplicate inputs between the Release and the Track (Title, Title Language, Version, Genre, Subgenre, Primary Artists).
   - Show track-specific fields inline on Step 2 (Customer stepper) / Tracks Details card (Admin form) rather than inside a modal.

2. **Form Synchronization (Customer vs Admin)**:
   - Ensure the Admin release create/edit form matches all Customer release/track form fields.
   - Replace legacy admin text inputs (`composer`/`songwriter`) with the dynamic `songwriters` array `{name, role}`.
   - Add `writer_percentage` and `iswc` fields to the admin track form.
   - Sync the IPL version validation logic (`version != 1`).
   - Dynamically toggle the Admin tracks details layout (inline Single track vs Album modal/table) in real-time when the Admin selects the Release Type.

---

## Design Designations

### 1. Reusable Track Partial
We will implement **Option 1**: A reusable Blade component/partial at `resources/views/partials/track-form-fields.blade.php`.
This component accepts the Alpine.js binding target dynamically as `{{ $scope }}` and an ID suffix `{{ $id_suffix }}` to prevent HTML element collisions:
```html
@include('partials.track-form-fields', ['scope' => '$store.release.tracks[0]', 'id_suffix' => '-inline'])
```

For Album releases, duplicate fields (Title, Language, Version, Genre, Subgenre, Primary Artists) are rendered; for Single releases, these are hidden from the track section and auto-populated behind the scenes.

### 2. State & Sync Flow
An Alpine.js `x-effect` block will keep the metadata in sync:
- When `release.type === 'single'`:
  - Ensure `release.tracks` has exactly one item.
  - Automatically copy:
    - `release.tracks[0].title = release.title`
    - `release.tracks[0].titlelang = release.titlelang`
    - `release.tracks[0].version = release.version`
    - `release.tracks[0].genre = release.genre`
    - `release.tracks[0].subgenre = release.subgenre`
    - `release.tracks[0].primaryartists = release.primaryartists`

### 3. Backend Verification & Processing
No database changes are needed since all columns exist. The backend requests:
- The serialized `tracks_json` blob is submitted.
- Track validation rules in `TrackRequest` (e.g. `songwriters.*.name`, `writer_percentage`, `iswc`) are already configured to expect this array structure, meaning the backend will automatically validate the synchronized fields seamlessly without changes.

---

## Affected Use Cases (Consolidated)

- **MODIFIED**:
  - Customer release create/edit form layout & stepper step 2.
  - Admin release create/edit form layout & tracks card.
  - Track edit/add AlpineJS logic on customer & admin forms.
- **NEW**:
  - `resources/views/partials/track-form-fields.blade.php` reusable form component.
