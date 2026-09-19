# Implementation Plan - Cover Validation and Save Draft Optimization

Align cover MIME validation to strictly allow JPEG format on both frontend and backend. Clear file inputs on successful draft save to prevent redundant uploads, while dynamically displaying the current IPL (Music Usage License) indicator via Alpine.js state.

## Use Case Impact

AFFECTED USE CASES:
- MODIFIED: Save Release Draft (Customer & Admin Aggregator), Submit Release (Customer), Edit Release (Customer & Admin Aggregator)
- NEW: (none)
- REMOVED: (none)

---

## Proposed Changes

### Backend Logic

#### [MODIFY] [ReleasesController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/ReleasesController.php)
Include `ipl_url` and `ipl_name` in the success JSON responses of both `store()` and `update()` methods.

```diff
     public function store(Request $request)
     {
         ...
             // Return
             return response()->json([
                 'success' => true,
                 'id' => $result->id,
                 'tracks' => $result->tracks->pluck('id')->all(),
+                'ipl_url' => $result->ipl_url,
+                'ipl_name' => $result->ipl ? $result->ipl->original_name : null,
             ], 200);
         ...
     }

     public function update($id, Request $request)
     {
         ...
             return response()->json([
                 'success' => true,
                 'id' => $result->id,
                 'tracks' => $result->tracks->pluck('id')->all(),
+                'ipl_url' => $result->ipl_url,
+                'ipl_name' => $result->ipl ? $result->ipl->original_name : null,
             ], 200);
         ...
     }
```

---

### Frontend Components

#### [MODIFY] [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php)
- Change `allowedMimeTypes` in `uploadCover()` to strictly check `['image/jpeg']`.
- Initialize `iplUrl` and `iplName` in the Alpine store.
- Update the HTML to dynamically render the "Current IPL" link using Alpine properties.
- Clear the file inputs `#cover-input` and `#ipl` after successful AJAX save draft/submission.

```diff
@@ -1077,6 +1077,8 @@
                 title: "{{ old('title', $release->title) ?? '' }}",
                 recordlabel: "{{ old('recordlabel', $release->recordlabel) ?? '' }}",
                 upc: "{{ old('upc', $release->upc) ?? '' }}",
                 releaseon: "{{ old('releaseon', $release->releaseon) ?? '' }}",
+                iplUrl: "{{ $release->ipl_url ?? '' }}",
+                iplName: "{{ $release->ipl ? $release->ipl->original_name : '' }}",
 
                 trackSubGenres: [],
                 methods: {
@@ -1133,5 +1135,5 @@
                                 // Check MIME type
                                 new Promise((resolve, reject) => {
-                                    var allowedMimeTypes = ['image/jpeg', 'image/png'];
+                                    var allowedMimeTypes = ['image/jpeg'];
                                     if (!allowedMimeTypes.includes(file.type)) {
                                         reject('Please upload a file with JPG or JPEG format for album cover.');
@@ -1181,2 +1183,3 @@
                         Alpine.store('release').coverImage = "{{URL::asset('build/images/album-placeholder.webp')}}";
                         document.getElementById('cover-input').value = '';
                     },
@@ -1291,4 +1294,6 @@
                                     // Unset cover input
                                     Alpine.store('release').cover = "";
+                                    document.getElementById('cover-input').value = '';
+                                    document.getElementById('ipl').value = '';
+                                    if (data.ipl_url) {
+                                        Alpine.store('release').iplUrl = data.ipl_url;
+                                        Alpine.store('release').iplName = data.ipl_name;
+                                    }
                                 },
                                 (data) => {
@@ -1365,4 +1370,6 @@
                                     // Unset cover input
                                     Alpine.store('release').cover = "";
+                                    document.getElementById('cover-input').value = '';
+                                    document.getElementById('ipl').value = '';
+                                    if (data.ipl_url) {
+                                        Alpine.store('release').iplUrl = data.ipl_url;
+                                        Alpine.store('release').iplName = data.ipl_name;
+                                    }
                                 },
```

And update the Blade HTML block for IPL to support Alpine.js dynamic data:
```diff
@@ -250,9 +250,9 @@
                             <fieldset class="mb-4 mt-3 ps-4" :disabled="$store.release.version != 2">
                                 <label for="ipl" class="form-label">Music Usage License (Izin Penggunaan Lagu - IPL)</label>
                                 <input type="file" class="form-control" name="ipl" id="ipl" accept="application/pdf">
-                                @if($release->ipl)
-                                    <div>
-                                        <small class="text-muted">Current IPL: <a href="{{ $release->ipl_url }}"
-                                                target="_blank">{{ $release->ipl_url }}</a></small>
-                                    </div>
-                                @endif
+                                <div x-show="$store.release.iplUrl" class="mt-2">
+                                    <small class="text-muted">Current IPL: <a :href="$store.release.iplUrl"
+                                            target="_blank" x-text="$store.release.iplName"></a></small>
+                                </div>
                             </fieldset>
```

#### [MODIFY] [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/admin/aggregator/create.blade.php)
- Change `allowedMimeTypes` in `uploadCover()` to strictly check `['image/jpeg']`.

```diff
@@ -997,5 +997,5 @@
                                 // Check MIME type
                                 new Promise((resolve, reject) => {
-                                    var allowedMimeTypes = ['image/png', 'image/jpeg'];
+                                    var allowedMimeTypes = ['image/jpeg'];
                                     if (!allowedMimeTypes.includes(file.type)) {
                                         reject('Please upload a file with PNG or JPEG format for album cover.');
```

---

## Verification Plan

### Manual Verification
- **Cover MIME Check:** Try to upload a PNG file as the cover on both customer release create page and admin aggregator page. Expect an instant validation failure alert showing "Please upload a file with JPG or JPEG format...".
- **Dynamic IPL & File Input Clear Check:**
  - Create or edit a release with `version = 2` (Cover Version).
  - Upload a JPG cover and select a PDF IPL.
  - Click "Save Draft".
  - Verify that:
    1. The files are uploaded successfully.
    2. The cover image preview remains.
    3. The file input elements `#cover-input` and `#ipl` are cleared (i.e. if you inspect them, they have no selected files).
    4. The "Current IPL" link dynamically appears with the name of the PDF you just uploaded.
  - Click "Save Draft" again without changing anything. Verify that the request is sent without uploading any files, and saves successfully without exceptions.
