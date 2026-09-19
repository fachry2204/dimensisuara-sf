# Implementation Plan - Release Single Layout & Form Synchronization

## Use Case Impact

AFFECTED USE CASES:
- **MODIFIED**: customer release create/edit (single & album)
- **MODIFIED**: admin release create/edit (single & album)
- **NEW**: Client-side WAV/FLAC audio format and bit depth verification before upload (Enhancement #1)

---

## Phase 1: Restructure Existing Code
⚠️ The current code structure needs cleanup before adding features. Phase 1 of this plan restructures the existing code by extracting track-specific form fields to a shared partial. Building on a broken foundation guarantees future headaches.

### Task 1.1: Create Reusable Track Fields Partial
- **File Path**: [resources/views/partials/track-form-fields.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/partials/track-form-fields.blade.php) [NEW]
- **Description**: Create a reusable partial file containing all track inputs. It dynamically binds to a specified Javascript scope (`{{ $scope }}`) and appends an `{{ $id_suffix }}` to elements (such as file inputs) to prevent collision when both inline and modal forms are present on the same page.
- **Pattern**: Reusable Component Pattern (Blade Partials)
- **Complete Code**: See details inside the workspace implementation plan.

---

## Phase 2: Database Layer
*(No migrations or database changes needed.)*

---

## Phase 3: Backend Logic & Synchronization

### Task 3.1: Synchronize Validation in Form Requests
- **File Path**: [app/Http/Requests/Release/AlbumRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Release/AlbumRequest.php) [MODIFY]
- **Description**: Ensure that `AlbumRequest` validation passes correctly for both customer and admin actions. Since the admin form will submit status like `pending` / `review`, let's make sure the rules allow all valid statuses.
- **Pattern**: Laravel FormRequest validation rules.
- **Code Changes**:
```diff
-            'status' => 'required|string|in:draft,rejected',
+            'status' => 'required|string|in:draft,pending,review,rejected,released',
```

---

## Phase 4: Frontend & UI Components

### Task 4.1: Modify Customer Create Form
- **File Path**: [resources/views/customer/releases/create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php) [MODIFY]
- **Description**:
  1. Add background synchronization state and operations:
     - Keep track subgenres list for each scope dynamically.
     - Perform fast client-side WAV/FLAC bit depth validation (16/24 bit check) inside `uploadTrack`.
  2. Modify Step 2:
     - Render `track-form-fields.blade.php` inline if type is `single` (binding to `tracks[0]`).
     - Hide the modal/tray selectors and the "Add Track" button for singles.
     - Keep standard tray/modal and table listing for albums.
- **Code Changes**:
  Ensure that when a file is selected, the binary structure of WAV or FLAC is scanned client-side to verify it is exactly 16-bit or 24-bit PCM/FLAC before uploading chunks:
```javascript
async function verifyAudioMetadata(file) {
    const allowedFormats = ['wav', 'flac'];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowedFormats.includes(ext)) {
        throw 'File extension must be WAV or FLAC.';
    }
    
    const buffer = await file.slice(0, 44).arrayBuffer();
    const view = new DataView(buffer);
    
    if (ext === 'wav') {
        // Check RIFF & WAVE headers
        const riff = String.fromCharCode(view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3));
        const wave = String.fromCharCode(view.getUint8(8), view.getUint8(9), view.getUint8(10), view.getUint8(11));
        if (riff !== 'RIFF' || wave !== 'WAVE') {
            throw 'Invalid WAV file header.';
        }
        
        // Find fmt chunk offset (usually at 12, but can vary)
        let offset = 12;
        let fmtFound = false;
        let bitDepth = 0;
        
        while (offset < buffer.byteLength - 8) {
            const chunkId = String.fromCharCode(view.getUint8(offset), view.getUint8(offset + 1), view.getUint8(offset + 2), view.getUint8(offset + 3));
            const chunkSize = view.getUint32(offset + 4, true);
            if (chunkId === 'fmt ') {
                fmtFound = true;
                // Offset of bits per sample in the fmt subchunk is offset + 22 bytes
                bitDepth = view.getUint16(offset + 22, true);
                break;
            }
            offset += 8 + chunkSize;
        }
        
        if (!fmtFound) {
            throw 'No fmt chunk found in WAV file.';
        }
        
        if (bitDepth !== 16 && bitDepth !== 24) {
            throw `Unsupported bit depth: ${bitDepth}-bit. Only 16-bit or 24-bit files are allowed.`;
        }
    } else if (ext === 'flac') {
        // Check fLaC magic number
        const magic = String.fromCharCode(view.getUint8(0), view.getUint8(1), view.getUint8(2), view.getUint8(3));
        if (magic !== 'fLaC') {
            throw 'Invalid FLAC file signature.';
        }
        // Streaminfo bits per sample is at file offset 16 (last 1 bit of byte 16) and file offset 17 (first 4 bits of byte 17)
        const byte12 = view.getUint8(16);
        const byte13 = view.getUint8(17);
        const bitDepth = (((byte12 & 0x01) << 4) | (byte13 >> 4)) + 1;
        
        if (bitDepth !== 16 && bitDepth !== 24) {
            throw `Unsupported bit depth: ${bitDepth}-bit. Only 16-bit or 24-bit files are allowed.`;
        }
    }
}
```

### Task 4.2: Modify Admin Create Form
- **File Path**: [resources/views/admin/aggregator/create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/admin/aggregator/create.blade.php) [MODIFY]
- **Description**:
  1. Synchronize track details cards to support inline fields for type `single` (binding to `tracks[0]`).
  2. Implement the dynamic `x-effect` observer that watches the "Release Type" dropdown and switches track UI layouts automatically.
  3. Include the shared Blade partial inside the modal and the inline block.

---

## Phase 5: Testing and Verification

### Automated Tests
```powershell
# Run the Laravel phpunit test suite if tests are configured
php artisan test
```

### Manual Verification
1. Log in as Customer.
2. Access **Create Single**. Verify that Step 2 displays audio file upload inputs and all track fields inline, without any modals.
3. Verify that changing fields like Release Title, Genre, and Primary Artists in Step 1 automatically updates the metadata on the Single track.
4. Attempt to upload a non-WAV/non-FLAC audio file, or a 32-bit WAV file, and verify that the client-side validation flags the file.
5. Save a Single draft and check that it saves successfully without requiring you to duplicate metadata.
6. Access **Create Album**. Verify that Step 2 displays the standard Track List table and "Add Track" button, which correctly opens the modal containing the track fields.
7. Log in as Admin. Create/Edit a release, change Release Type dropdown from Album to Single, and observe the Tracks card dynamically toggle between the inline Single form and the Album track table.
