# Brainstorm: Form Review Summary & Data Auto-fill

## 1. Goal Overview
The goal is to display a complete summary of all entered data (Song Metadata, Publishing Details, and Songwriters) on the final "Review" step before submission. Additionally, we want to integrate the auto-fill behavior for existing tracks to populate the required metadata fields and fix backend validation errors.

## 2. Approach: Full Alpine.js Migration & Auto-fill

To answer your question: **Yes**, the method to copy the track into existing fields aligns perfectly with updating the AJAX endpoint! By migrating all inputs to Alpine.js state, we can easily auto-fill the fields when a track is selected. This automatically solves the backend validation issue we identified earlier, because the hidden inputs will now contain the correct track data when submitted.

### Data Flow & Component Design
- **State Management**: We will expand `Alpine.store('publishing')` to include all form fields (`title`, `other_title`, `sample`, `sync_license_type`, `performer`, `genre`, `language`, `iswc`, `isrc`, `region`, `note`, `duration_minute`, `duration_second`).
- **Input Binding**: All existing standard HTML inputs will be updated to use `x-model="$store.publishing.[field]"`.
- **AJAX Update**: `ReleasesController@get_tracks` will be updated to return the complete track metadata.
- **Auto-fill Logic**: We will add a method in Alpine `selectTrack()`. When the user selects a track from the dropdown, this method will find the track in the `$store.publishing.tracks` array and copy its data into the respective Alpine state variables.
- **Review Summary UI**: In Step 4 (`#step-4`), we will build a summary grid using `x-text` to display the current state of all Alpine variables reactively.

## 3. Enhancement Opportunities

Would you like to include any of these enhancements in the plan?

| # | Enhancement | Benefit | Priority |
|---|-------------|---------|----------|
| 1 | Lock Auto-filled Fields | When an existing track is selected, disable the auto-filled inputs so the user knows they are derived from the track (but they still get submitted). | Recommended |
| 2 | Summary Formatting | Format empty/missing fields in the summary as `<span class="text-muted">Not provided</span>` for better readability. | Recommended |
| 3 | Duration Calculation | Automatically parse the total seconds from the track data into `duration_minute` and `duration_second`. | Required (if track duration is in seconds) |

*Please specify if you want to include any of these enhancements (e.g., "1 and 2"), and then we can proceed with `/plan`.*
