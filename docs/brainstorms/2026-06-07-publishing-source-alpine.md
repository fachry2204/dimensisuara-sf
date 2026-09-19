# Brainstorm: Link Alpine.js to Publishing Source Selection

## 1. Goal Overview
The goal is to enhance the frontend reactivity of the song publishing registration form (`create.blade.php`) by linking the "source" selection (`existing` vs `new`) to Alpine.js. This includes dynamically toggling form fields, fetching tracks for a selected release via AJAX, and ensuring edit state works seamlessly.

## 2. Approach: Frontend UI State Focus + AJAX Tracks Endpoint

Based on the agreed approach, we will focus exclusively on the frontend UI state for handling the form fields, and create a new AJAX endpoint to fetch tracks dynamically.

### Data Flow & Component Design
- **State Management**: We will extend the existing `Alpine.store('publishing')` to manage `source`, `release_id`, and `track_id`.
- **Field Toggling**: `x-show` directives will be added to the fieldsets (`source_existing_field` and `source_new_field`) to display them conditionally based on the `source` state.
- **Dynamic Track Fetching**: We will create a new route and controller method (e.g., `PublishingController@getTracks` or `ReleasesController@get_tracks`) to return a release's tracks via JSON.
- **Frontend Interactivity**: When a user selects a release, Alpine.js will trigger a `fetch()` request to this new endpoint and populate the track dropdown.
- **Edit State Restoration**: On edit mode, Alpine's `init()` function will pre-populate the `source`, `release_id`, and `track_id` using Blade's `old()` or existing `$publishing` data, and automatically fetch the tracks to ensure the UI is fully restored.

### Technical Steps
1.  **AJAX Endpoint**:
    -   Add route: `GET /publishing/_ajax/release/{id}/tracks`
    -   Add method `getTracks($id)` to return the tracks for a given release.
2.  **Alpine.js Store Enhancement**:
    -   Add properties: `source`, `release_id`, `track_id`, `tracks` (array).
    -   Add method: `fetchTracks()`.
3.  **Blade Template Updates**:
    -   Bind radio buttons: `x-model="$store.publishing.source"`.
    -   Bind fieldsets: `x-show="$store.publishing.source === 'existing'"` etc.
    -   Bind selects: `x-model="$store.publishing.release_id"`, `@change="$store.publishing.fetchTracks()"`.
    -   Populate track options using `<template x-for="track in $store.publishing.tracks">`.

## 3. Enhancement Opportunities

Would you like to include any of these enhancements?

| # | Enhancement | Benefit | Priority |
|---|-------------|---------|----------|
| 1 | Loading Indicator | Shows a loading spinner on the track dropdown while AJAX request is pending | Recommended |
| 2 | Error Handling | Shows a toast or error message if the AJAX fetch fails | Recommended |
| 3 | Auto-fill Data | Automatically copy metadata (title, performer, ISRC) from the selected track to hidden fields if needed by validation | Optional |

*Please specify if you want to include any of these enhancements (e.g., "1 and 2"), and then we can proceed with the `/plan`.*
