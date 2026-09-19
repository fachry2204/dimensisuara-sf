# Brainstorm: Track Form Refactoring (Single & Album Separation)

## Phase 1: UNDERSTAND
- **Topic**: Refactoring the combined `track-form-fields.blade.php` layout into separate layouts for Single and Album releases.
- **Problem**: The unified form couples layout logic (dynamic type checks) and scope context. For Single releases, Step 2 is redundant and step 1 has poor logical ordering (e.g., Primary Artists and Featured Artists are split between step 1 and step 2 components). Additionally, there is a concern regarding the AlpineJS store `$store.release` being inaccessible inside the modal which is mounted outside of the main Alpine component.
- **Goal**:
  1. Remove Step 2 for Single releases, shifting all track-level fields inline to Step 1.
  2. Implement separate step 1 structures for Album and Single.
  3. Create independent form partials for Album Track Modal (no `$store.release` dependency) and Single Track fields.
  4. Ensure WAV/FLAC audio duration reading is robust and handles fallback parsed headers.

---

## Phase 2: PROPOSE

| Option | Approach | Pros | Cons | Best Practice? |
|:---|:---|:---|:---|:---|
| **A (Chosen)** | **Complete Separation of Partials**<br>Split the component into:<br>1. `partials/album-step1-fields.blade.php`<br>2. `partials/single-step1-fields.blade.php`<br>3. `partials/album-track-form-fields.blade.php`<br>4. `partials/single-track-form-fields.blade.php` | • Decoupled scopes: Album track modal relies *only* on `$store.tray.trackTray`. No `$store.release` dependency.<br>• Perfect layout order: Featured Artists immediately follow Primary Artists for Single Step 1.<br>• Step 2 is cleanly skipped/hidden for Single. | • Multiple files to maintain instead of one. | ✅ Yes (Clean SRP separation) |

---

## Phase 3: DESIGN

### Component Structure & Layouts

1. **`partials/album-step1-fields.blade.php` [NEW]**
   - Renders only Album release-level fields: Title, Title Language, Cover Art, Record Label, Version, IPL, Genre, Subgenre, Primary Artists.
   - Tied to `$store.release`.

2. **`partials/single-step1-fields.blade.php` [NEW]**
   - Combines release-level fields and single-track fields.
   - Removes Step 2 from the Stepper:
     - Step 1: Single & Track Details
     - Step 2: Release Details
     - Step 3: Review
   - Inline layout structure:
     - Cover Art, Title, Title Language, Record Label, Version, IPL, Genre, Subgenre
     - Primary Artists
     - `@include('partials.single-track-form-fields')` (dry track-level fields)

3. **`partials/album-track-form-fields.blade.php` [NEW]**
   - Refactored from the old unified track partial.
   - Tied strictly to the modal tray (`$scope` = `$store.tray.trackTray`).
   - Completely removes any reference to `$store.release` or `$store.release.type === 'album'`, making it robust inside the modal scope.

4. **`partials/single-track-form-fields.blade.php` [NEW]**
   - Dry partial containing only track-level fields for a single track:
     - Audio files (Full Song, TikTok Ref)
     - Featured Artists
     - Contributors & Productions
     - Songwriters
     - Instrumental/Lyrics
     - Additional Track Info (ISRC, writer percentage, ISWC)
   - Scope: `$store.release.tracks[0]` (for customer) or `$store.tray.trackTray` (for admin view).

---

## Phase 4: ENHANCEMENTS & DURATION VERIFICATION

### 1. Robust WAV/FLAC Header Duration Parsing
To check/fix issues where `audioPlayer.duration` returns `Infinity` or `NaN` in Chrome for local blobs:
- We will parse raw binary headers of WAV/FLAC files during the client-side format validation.
- **For WAV**: Calculate from sample rate, channels, bit depth, and data chunk size.
- **For FLAC**: Calculate from `total_samples` and `sample_rate` inside the `STREAMINFO` block.
- **Fallback Logic**: If `audioPlayer.duration` fails or is non-finite, use the parsed header duration.

### 2. Seconds Formatting Fix
In `humanizeDuration`, ensure seconds are floored/rounded to prevent decimal outputs if a float duration is stored:
```javascript
let seconds = Math.floor(duration % 60).toString().padStart(2, '0');
```
