# Plan: Release & Track Validation Refactoring & WAV/FLAC Support

This plan details the changes to allow user input of full and trimmed tracks in WAV and FLAC format, ensure robust Android upload MIME-type support, and format nested array validation error messages with proper indices (using i18n translations for English and Bahasa Indonesia).

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: save draft release, submit release for validation
- NEW: (none)
- REMOVED: (none)

---

## Proposed Changes

### Phase 1: Localization & Message Customization (i18n)

We will configure custom translations for nested array attributes and custom track error rules.

#### [MODIFY] [validation.php (English)](file:///d:/Project/cms-soundfresh-id/resources/lang/en/validation.php)
We will add missing wildcard translations for nested array attributes (`songwriters`, `contributors`, `productions`) inside the `attributes` block.

```diff
         'primaryartists.*.name'   => 'Primary Artist :position Name',
         'primaryartists.*.social'   => 'Primary Artist :position Social Media Link',
         'primaryartists.*.spotify'   => 'Primary Artist :position Spotify Link',
 
         'featuredartists'        => 'Featured Artists',
         'featuredartists.*'      => 'Featured Artists',
         'featuredartists.*.name'   => 'Featured Artist :position Name',
         'featuredartists.*.social'   => 'Featured Artist :position Social Media Link',
         'featuredartists.*.spotify'   => 'Featured Artist :position Spotify Link',
 
+        'songwriters.*.name'       => 'Songwriter #:position Name',
+        'songwriters.*.role'       => 'Songwriter #:position Role',
+
+        'contributors.*.name'      => 'Contributor #:position Name',
+        'contributors.*.role'      => 'Contributor #:position Role',
+
+        'productions.*.name'       => 'Production Team #:position Name',
+        'productions.*.role'       => 'Production Team #:position Role',
+
         'tracknum'              => 'Track Number',
```

#### [MODIFY] [validation.php (Indonesian)](file:///d:/Project/cms-soundfresh-id/resources/lang/id/validation.php)
We will add wildcard translations for nested array attributes in the `attributes` block, and custom required error messages in the `custom` block.

```diff
     'custom' => [
         'attribute-name' => [
             'rule-name' => 'custom-message',
         ],
+        'lyrics'    => [
+            'required_if' => 'Lirik wajib diisi jika lagu bukan instrumental.',
+        ],
+        'lyriclang' => [
+            'required_if' => 'Bahasa lirik wajib diisi jika lagu bukan instrumental.',
+        ],
+        'explicit'  => [
+            'required_if' => 'Status explicit wajib diisi jika lagu bukan instrumental.',
+        ],
+        'ipl' => [
+            'required_if' => 'Izin Penggunaan Lagu (IPL) wajib diunggah untuk lagu cover.',
+        ],
     ],
 
     /*
```
```diff
         'tracknum'          => 'Jumlah lagu',
         'type'              => 'Tipe rilis',
         'cover'             => 'Cover',
         'cover_draft'       => 'Cover draft',
         'status'            => 'Status',
         'tracks'            => 'Tracks',
 
+        'primaryartists.*.name'   => 'Nama Artis Utama ke-:position',
+        'primaryartists.*.social'   => 'Media Sosial Artis Utama ke-:position',
+        'primaryartists.*.spotify'   => 'Link Spotify Artis Utama ke-:position',
+
+        'featuredartists.*.name'   => 'Nama Artis Unggulan ke-:position',
+        'featuredartists.*.social'   => 'Media Sosial Artis Unggulan ke-:position',
+        'featuredartists.*.spotify'   => 'Link Spotify Artis Unggulan ke-:position',
+
+        'songwriters.*.name'       => 'Nama Penulis Lagu ke-:position',
+        'songwriters.*.role'       => 'Peran Penulis Lagu ke-:position',
+
+        'contributors.*.name'      => 'Nama Kontributor ke-:position',
+        'contributors.*.role'      => 'Peran Kontributor ke-:position',
+
+        'productions.*.name'       => 'Nama Tim Produksi ke-:position',
+        'productions.*.role'       => 'Peran Tim Produksi ke-:position',
+
         'order'                 => 'Urutan',
```

---

### Phase 2: Form Requests Validation

We will clean up the Form Request classes to remove broken constraints and hardcoded file constraints.

#### [MODIFY] [TrackRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/TrackRequest.php)
Remove duplicate singular `songwriter` rules.
*Pattern: Form Request validation per Laravel docs.*

```diff
-            'productions.*.name'        => 'required|string|max:100|min:1',
-            'productions.*.role'        => 'required|integer',
-            'songwriter'                => 'required|array|min:1',
-            'songwriter.*.name'         => 'required|string|max:100|min:1',
-            'songwriter.*.role'         => 'required|in:author,composer,author_composer',
-            'iswc'                      => 'nullable|string|max:100',
+            'productions.*.name'        => 'required|string|max:100|min:1',
+            'productions.*.role'        => 'required|integer',
+            'iswc'                      => 'nullable|string|max:100',
```

#### [MODIFY] [AlbumRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/AlbumRequest.php)
Remove the file-upload-bound `ipl` validation from AlbumRequest rules.
*Pattern: Form Request validation per Laravel docs.*

```diff
             'recordlabel'           => 'required|string|max:100',
             'version'               => 'required|integer',
-            'ipl'                   => 'required_if:version,2|file|mimes:pdf',
             'genre'                 => 'required|integer',
```

---

### Phase 3: Backend Business Logic Services

We will update the backend services to allow FLAC, validate IPL status correctly, convert objects to arrays for validation, and dynamically extract uploaded file extensions.

#### [MODIFY] [ReleaseService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Releases/ReleaseService.php)
Allow both `'wav'` and `'flac'` formats, cast nested relationship objects to arrays, and manually check `ipl` presence.
*Pattern: Service Layer validation per custom rules.*

```php
    public function trackChunkMerge($filename, $total_chunks, $hash, $type)
    {
        // Merge chunks
        $filepath = FileService::chunkMerge($filename, $total_chunks, $hash);

        // Validation using getID3
        $getID3 = new \getID3();
        $getID3->setOption(array('encoding' => 'UTF-8'));
        $fileInfo = $getID3->analyze($filepath);

        // Check file format (wav or flac)
        $allowedFormats = ['wav', 'flac'];
        $format = strtolower($fileInfo['fileformat']);
        if (!in_array($format, $allowedFormats)) {
            throw new InvalidFileFormatException($fileInfo['fileformat'], $allowedFormats);
        }

        // Check duration
        if (!isset($fileInfo['playtime_seconds'])) {
            throw new ErrorCheckingMediaDurationException;
        }

        // Check duration if type is trimmed
        if ($type == 'trimmed' && floor($fileInfo['playtime_seconds']) > 60) {
            throw new InvalidMediaDurationException($fileInfo['playtime_seconds'], 60);
        }

        return true;
    }
```
```php
    public function submit(Release $release)
    {
        // Validate Album
        $validator = new AlbumRequest();
        $releaseData = $release->toArray();

        // Convert artist objects to arrays for validation
        if (isset($releaseData['primaryartists']) && is_array($releaseData['primaryartists'])) {
            $releaseData['primaryartists'] = array_map(function ($artist) {
                return (array) $artist;
            }, $releaseData['primaryartists']);
        }

        $validator = Validator::make($releaseData, $validator->rules());

        // Manually validate cover and ipl
        $validator->after(function ($validator) use ($release) {
            if (!$release->cover) {
                $validator->errors()->add('cover', 'Cover art is required');
            }
            if ($release->version == 2 && !$release->ipl) {
                $validator->errors()->add('ipl', 'Music Usage License (IPL) is required for cover versions');
            }
        });

        // Return validation errors if any exist
        if ($validator->fails()) {
            throw new ValidationErrorException('release', $validator->errors());
        }

        // Validate each Tracks
        foreach ($release->tracks as $index => $track) {
            $validator = new TrackRequest();
            $trackData = $track->toArray();

            // Convert all nested object arrays to associative arrays for validation
            foreach (['primaryartists', 'featuredartists', 'contributors', 'productions', 'songwriters'] as $key) {
                if (isset($trackData[$key]) && is_array($trackData[$key])) {
                    $trackData[$key] = array_map(function ($item) {
                        return (array) $item;
                    }, $trackData[$key]);
                }
            }

            $validator = Validator::make($trackData, $validator->rules());

            // Manually validate full and trimmed track
            $validator->after(function ($validator) use ($track) {

                if (!$track->full_exists) {
                    $validator->errors()->add('full_song', 'Full song is required');
                }

                if (!$track->trimmed_exists) {
                    $validator->errors()->add('trimmed_song', 'TikTok ref is required');
                }

            });

            // Return validation errors if any exist
            if ($validator->fails()) {
                throw new ValidationErrorException('track', $validator->errors(), $index + 1);
            }
        }
        // ... (rest of code is unchanged)
```

#### [MODIFY] [FileService.php](file:///d:/Project/cms-soundfresh-id/app/Services/FileService.php)
Dynamically extract file extensions instead of hardcoding `.wav` for track files.

```php
            // Get metadata
            if($newfile instanceof UploadedFile) {
                $path   = $newfile->getRealPath();
                $name   = $newfile->getClientOriginalName();
                $mime   = $newfile->getClientMimeType();
                $ext    = $newfile->getClientOriginalExtension();
                $size   = $newfile->getSize();

                if($owner instanceof Track) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['wav', 'flac'])) {
                        $ext = 'wav';
                    }
                }
            }
```

---

### Phase 4: Exceptions & Validation Formatting

#### [MODIFY] [ValidationErrorException.php](file:///d:/Project/cms-soundfresh-id/app/Exceptions/Aggregator/ValidationErrorException.php)
Implement error formatting to replace dotted indices with human-readable numbers, matching custom translations.

```php
<?php

namespace App\Exceptions\Aggregator;

use Exception;

class ValidationErrorException extends Exception
{
    protected $part;
    protected $position;
    protected $validationErrors;

    public function __construct($part, $validationErrors, $position = null)
    {
        parent::__construct('Validation errors');

        $this->part = $part;
        $this->position = $position;
        $this->validationErrors = $validationErrors;
    }

    public function getData()
    {
        $locale = app()->getLocale();
        $formattedErrors = [];

        $errorsArray = method_exists($this->validationErrors, 'toArray') 
            ? $this->validationErrors->toArray() 
            : (array) $this->validationErrors;

        foreach ($errorsArray as $key => $messages) {
            $formattedKey = null;
            if (preg_match('/^([a-z_]+)\.(\d+)\.([a-z_]+)$/', $key, $matches)) {
                $attribute = $matches[1];
                $index = intval($matches[2]) + 1;
                $field = $matches[3];

                $translationKey = "validation.attributes.{$attribute}.*.{$field}";
                $translated = __($translationKey);

                if ($translated !== $translationKey) {
                    $formattedKey = str_replace(':position', $index, $translated);
                } else {
                    $formattedKey = self::formatArrayKey($key, $locale);
                }
            }

            foreach ((array)$messages as $message) {
                if ($formattedKey) {
                    $message = str_replace(
                        [$key, ucwords(str_replace('.', ' ', $key)), str_replace('.', ' ', $key)],
                        [$formattedKey, $formattedKey, $formattedKey],
                        $message
                    );
                    $message = str_replace(':position', $index, $message);
                }
                $formattedErrors[] = $message;
            }
        }

        return $formattedErrors;
    }

    public function getPart()
    {
        return $this->part;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public static function formatArrayKey($key, $locale = 'id')
    {
        if (preg_match('/^([a-z_]+)\.(\d+)\.([a-z_]+)$/', $key, $matches)) {
            $attribute = $matches[1];
            $index = intval($matches[2]) + 1;
            $field = $matches[3];

            if ($locale === 'id') {
                $attrs = [
                    'primaryartists' => 'Artis Utama',
                    'featuredartists' => 'Artis Unggulan',
                    'songwriters' => 'Penulis Lagu',
                    'contributors' => 'Kontributor',
                    'productions' => 'Tim Produksi',
                ];
                $fields = [
                    'name' => 'Nama',
                    'social' => 'Media sosial',
                    'spotify' => 'Link Spotify',
                    'role' => 'Peran',
                ];
                
                $attrName = $attrs[$attribute] ?? $attribute;
                $fieldName = $fields[$field] ?? $field;
                return "{$fieldName} {$attrName} ke-{$index}";
            } else {
                $attrs = [
                    'primaryartists' => 'Primary Artist',
                    'featuredartists' => 'Featured Artist',
                    'songwriters' => 'Songwriter',
                    'contributors' => 'Contributor',
                    'productions' => 'Production Team member',
                ];
                $fields = [
                    'name' => 'name',
                    'social' => 'social media link',
                    'spotify' => 'Spotify link',
                    'role' => 'role',
                ];

                $attrName = $attrs[$attribute] ?? $attribute;
                $fieldName = $fields[$field] ?? $field;
                return "{$attrName} #{$index} {$fieldName}";
            }
        }
        return null;
    }
}
```

---

### Phase 5: Frontend Views

#### [MODIFY] [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php)
Update `<input accept="...">` file inputs for full track and trimmed track to allow WAV and FLAC with Android compatibility.

```diff
                                                 <input type="file" class="form-control" id="file-full"
                                                     style="display: none;" accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"
                                                     @change="$store.tray.methods.uploadTrack('full')">
```
```diff
                                                 <input type="file" class="form-control" id="file-trimmed"
                                                     style="display: none;" accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"
                                                     @change="$store.tray.methods.uploadTrack('trimmed')">
```

---

## Acceptance Criteria
- [ ] Users can upload WAV and FLAC files seamlessly from desktop and Android devices.
- [ ] Stored files preserve their correct extension (.wav or .flac) in local storage.
- [ ] Submit validation does not fail on draft IPL files already stored in the database.
- [ ] Dotted array validation errors (e.g. `primaryartists.0.name`) are translated to indexed sentences (e.g. `Nama Artis Utama ke-1 wajib diisi.`) in both English and Indonesian.
