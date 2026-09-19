# PHP max_input_vars Silently Truncates Form Data

## Problem

When submitting forms with many nested inputs (e.g., `tracks[0][title]`, `tracks[0][primaryartists][0][name]`, etc.), PHP's `max_input_vars` limit (default: 1000) silently drops fields beyond the threshold. No error is thrown — the data just vanishes from `$_POST`.

### Symptoms
- Intermittent data loss (depends on how many tracks/artists the user adds)
- Some fields are null/missing after save
- Cannot reproduce with small datasets
- No error messages shown

### Root Cause
Each track generates ~25+ form inputs. An album with 5 tracks × 3 artists each easily exceeds 1000 input vars. PHP silently truncates the rest.

## Solution

Replace per-field hidden `<input>` elements with a single JSON blob:

### Frontend
```js
// Before creating FormData, serialize tracks as JSON
document.getElementById('tracks-json-input').value = JSON.stringify(
    Alpine.store('release').tracks.map((track, index) => ({...track, order: index}))
);
const formData = new FormData(form);
```

### Backend (Laravel)
```php
// Prefer JSON blob, fallback to form array for backward compat
$tracks = [];
if (isset($data['tracks_json']) && is_string($data['tracks_json'])) {
    if (strlen($data['tracks_json']) > 10 * 1024 * 1024) {
        throw new \InvalidArgumentException('Tracks data too large.');
    }
    $decoded = json_decode($data['tracks_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \InvalidArgumentException('Invalid tracks data: ' . json_last_error_msg());
    }
    if (is_array($decoded)) {
        $tracks = $decoded;
    }
} elseif (isset($data['tracks']) && is_array($data['tracks'])) {
    $tracks = $data['tracks'];
}
```

## Key Takeaway

Never use dozens of hidden form inputs for complex nested data. Serialize as JSON — it's one field instead of hundreds, immune to `max_input_vars`, and reads directly from Alpine/React state without DOM rendering timing issues.
