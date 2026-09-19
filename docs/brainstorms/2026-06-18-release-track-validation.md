# Brainstorm: Release & Track Validation Refactoring & WAV/FLAC Support

## 1. Goal Description
The objective is to fix release and track validation bugs, enable support for both WAV and FLAC files, resolve Android MIME type upload issues, and format array validation error messages with proper indices in Indonesian (Bahasa) and English.

## 2. Options and Choices

### WAV/FLAC & Android Compatibility
* **Option A (Chosen):** Update the frontend `accept` attribute to support standard/non-standard MIME types and file extensions:
  `accept="audio/wav,audio/x-wav,audio/wave,audio/x-pn-wav,audio/flac,audio/x-flac,.wav,.flac"`
  On the backend, merge and validate both `wav` and `flac` formats via getID3 parser checks. Dynamically set the file extension in `FileService::store` for tracks based on original file extension.

### Validation Refactoring
* **Option A (Chosen):**
  - **Duplicate `songwriter` rule:** Remove the duplicate/broken singular `songwriter` rule from `TrackRequest`.
  - **Array of Objects:** Recursively cast all relation arrays (`primaryartists`, `featuredartists`, `songwriters`, `contributors`, `productions`) from objects to arrays before validator checks.
  - **IPL validation:** Relocate `ipl` presence validation to the manual `after` hook of `ReleaseService::submit()`.

### Validation Message Customization (i18n)
- Custom translation files will map wildcard attributes (e.g. `primaryartists.*.name` -> `Nama Artis Utama ke-:position` / `Primary Artist #:position Name`).
- Enhance `ValidationErrorException` to intercept MessageBag error keys and dynamically replace dot-notation array validation index keys with formatted human-readable labels.

## 3. Implementation Details
The proposed changes are documented in the [implementation plan](file:///C:/Users/Amakura/.gemini/antigravity-ide/brain/b3637f32-d095-4f29-9592-5048c1f1638f/implementation_plan.md).

> "Brainstorm complete. Run `/plan` when ready."
