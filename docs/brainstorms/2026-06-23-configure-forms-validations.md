# Brainstorm: Configure Forms & Validations (2026-06-23)

## Requirements

1. **Cover Art Format**: Restrict format to jpeg/jpg only. Emphasize in frontend instructions.
2. **Audio Bitrate (Bit Depth)**: Restrict to 16 or 24 bit. Display supported bit depth in instructions.
3. **Audio Format**: Restrict to wav/flac (currently backend allows both, frontend helper text should be updated from WAV-only to WAV or FLAC).
4. **Publishing Region**: Remove region fields from publishing views and validation, but do not drop the database column yet.
5. **IPL Requirements**: IPL is required on all versions except "Original" (Version ID 1).
6. **Enhancement 1**: Robust Client-side Fetch Error Handler to capture non-JSON 500 crashes during track merging.

---

## Chosen Approach: Option A + Enhancement 1

### Key Components

- **Frontend Releases View**: [create.blade.php](file:///d:/Project/cms-soundfresh-id/resources/views/customer/releases/create.blade.php)
  - Emphasizes jpeg/jpg validation rules in instructions.
  - Updates track guidelines to specify WAV/FLAC and 16/24 bit.
  - Enables IPL upload field for all versions except original (`version != 1`).
  - Catches non-JSON 500 errors during track merging securely.

- **Backend Validation & Upload**: [ReleaseService.php](file:///d:/Project/cms-soundfresh-id/app/Services/Releases/ReleaseService.php)
  - Adds bit depth checks (16/24) during chunk merge via getID3 metadata (`$fileInfo['audio']['bits_per_sample']`).
  - Clears IPL file model if version is set back to Original (`1`).
  - Requires IPL upload for any release where `version != 1` during submission validation.

- **Publishing Views & Request**:
  - [StorePublishingRequest.php](file:///d:/Project/cms-soundfresh-id/app/Http/Requests/Publishing/StorePublishingRequest.php): Removes `region` validation.
  - [PublishingController.php](file:///d:/Project/cms-soundfresh-id/app/Http/Controllers/PublishingController.php): Removes `region` parameter parsing.
  - [create.blade.php (Publishing)](file:///d:/Project/cms-soundfresh-id/resources/views/customer/publishing/create.blade.php) and [show.blade.php (Publishing)](file:///d:/Project/cms-soundfresh-id/resources/views/customer/publishing/show.blade.php): Removes input fields and table displays for region.
