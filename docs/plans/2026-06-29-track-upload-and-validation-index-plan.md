# Plan: Track Upload Input Clearing & Track Validation Index Correction

This plan details the changes to clear the file input after a track is successfully uploaded (allowing re-uploading the same file) and to ensure that track validation messages reflect the correct track order.

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: save draft release, submit release for validation
- NEW: (none)
- REMOVED: (none)

---

## Proposed Changes

### Phase 1: Clear File Input After Upload

#### [MODIFY] [track-form-scripts.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/partials/track-form-scripts.blade.php)
Clear the input file value in the `finally` block of `window.uploadAudioFile` so the browser triggers the `@change` event if the user uploads the same file again.

```javascript
            } catch (error) {
                document.getElementById(audioId).src = '';
                document.getElementById(inputId).value = '';
                trackObj[nameKey] = '';
                trackObj[contentKey] = '';
                trackObj[tempKey] = '';

                Lobibox.notify('error', {
                    pauseDelayOnHover: true,
                    rounded: true,
                    position: 'top right',
                    icon: 'bi bi-exclamation-triangle',
                    msg: error
                });
            } finally {
                const fileInput = document.getElementById(inputId);
                if (fileInput) {
                    fileInput.value = '';
                }
                Alpine.store('tray').overlayActive = false;
            }
```

---

### Phase 2: Translation Attribute Keys Configuration

#### [MODIFY] [validation.php (Indonesian)](file:///d:/Project/cms-soundfresh-id/resources/lang/id/validation.php)
Remove duplicate array keys under attributes and map separate keys for album vs track attributes to avoid collisions. Also add translation for `songwriters`.

```php
        'album_title'       => 'Judul album/single',
        'album_titlelang'   => 'Bahasa judul',
        'recordlabel'       => 'Label rekaman',
        'album_version'     => 'Versi album/single',
        'album_genre'       => 'Genre album/single',
        'album_subgenre'    => 'Sub-Genre album/single',
```
And under the "tracks" section:
```php
        'track_title'           => 'Judul lagu',
        'track_titlelang'       => 'Bahasa judul lagu',
        'track_version'         => 'Versi',
        'track_genre'           => 'Genre',
        'track_subgenre'        => 'Sub-Genre',
        'songwriters'           => 'Penulis lagu',
```

#### [MODIFY] [validation.php (English)](file:///d:/Project/cms-soundfresh-id/resources/lang/en/validation.php)
Add specific keys matching the ID locale mapping to ensure consistency.

```php
        'album_title'           => 'Title',
        'album_titlelang'       => 'Title Language',
        'album_version'         => 'Version',
        'album_genre'           => 'Genre',
        'album_subgenre'        => 'Sub-Genre',
        'track_title'           => 'Title',
        'track_titlelang'       => 'Title Language',
        'track_version'         => 'Version',
        'track_genre'           => 'Genre',
        'track_subgenre'        => 'Sub-Genre',
        'songwriters'           => 'Songwriters',
```

---

### Phase 3: Form Requests Attributes Definition

#### [MODIFY] [AlbumRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/AlbumRequest.php)
Implement standard `attributes()` method returning specific translations to prevent overlaps.

```php
    public function attributes(): array
    {
        return [
            'title' => __('validation.attributes.album_title'),
            'titlelang' => __('validation.attributes.album_titlelang'),
            'version' => __('validation.attributes.album_version'),
            'genre' => __('validation.attributes.album_genre'),
            'subgenre' => __('validation.attributes.album_subgenre'),
        ];
    }
```

#### [MODIFY] [TrackRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/TrackRequest.php)
Implement standard `attributes()` method returning specific translations to prevent overlaps.

```php
    public function attributes(): array
    {
        return [
            'title' => __('validation.attributes.track_title'),
            'titlelang' => __('validation.attributes.track_titlelang'),
            'version' => __('validation.attributes.track_version'),
            'genre' => __('validation.attributes.track_genre'),
            'subgenre' => __('validation.attributes.track_subgenre'),
            'songwriters' => __('validation.attributes.songwriters'),
        ];
    }
```

---

### Phase 4: Release Service Integration & Sorting

#### [MODIFY] [ReleaseService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Releases/ReleaseService.php)
* Map fallback sequential index when syncing track order in `store()`.
* Sort the tracks explicitly in `submit()` by pivot `track_order` and pass custom request attributes and messages to `Validator::make()`.

---

## Acceptance Criteria
- [ ] Users can upload identical audio files sequentially on different tracks without the browser ignoring the select action.
- [ ] Submitting a release with errors on track 2 displays the modal validation header for track 2.
- [ ] Validation errors on release title and track title use the correct respective translated terms (e.g. "Judul album/single" vs "Judul lagu" in ID).
