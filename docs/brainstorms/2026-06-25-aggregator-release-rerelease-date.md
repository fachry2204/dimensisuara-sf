# Brainstorm: Aggregator Release Request Revision - TikTok & YouTube Music Re-Release Date

This brainstorm details the design for adding an optional TikTok & YouTube Music re-release date under the release detail section of the aggregator release request.

## Option Selected
**Option A: Direct Date Column & JSON Property Enhancement**
* Add a nullable `tiktok_ytmusic_rerelease_date` column to the `releases` table.
* Skip the contributor share percentage field for now.
* Add validation rules and frontend inputs for the combined re-release date.

## Database Schema Changes
* Add nullable date column `tiktok_ytmusic_rerelease_date` to `releases` table.

## Key Components & Responsibilities

### Backend
* **Release Model**: Whitelist `tiktok_ytmusic_rerelease_date` in `$fillable`.
* **Form Request**: Add validation rule in `AlbumRequest` for `tiktok_ytmusic_rerelease_date` (`nullable|date`). Include commented-out code placeholder for future offset validation (e.g. at least 14 days or relative to planned release date).
* **Release Service**: Map the new date attribute in the bulk-assignment list within `store()`.
* **Controllers**: Whitelist the field in `ReleasesController` and `MainController` requests.

### Frontend
* **Customer Release Form (`create.blade.php`)**:
  * Add a date input field under the "Planned Release Date" field in Step 3.
  * Bind the input to `$store.release.tiktok_ytmusic_rerelease_date`.
* **Admin Aggregator Release Form (`create.blade.php`)**:
  * Add the same date input field.
* **Release Detail Component (`components/release.blade.php`)**:
  * Render a row displaying "TikTok & YouTube Music Re-Release Date" if populated.
