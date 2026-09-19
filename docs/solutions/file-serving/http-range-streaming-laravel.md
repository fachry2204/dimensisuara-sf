# Large Audio/Video Files Crash Chrome / Hang on `<audio>` / `<video>`

## Problem

Serving large media files (e.g., 200 MB audio) through a Laravel controller using `Storage::disk(...)->response($path)` causes:

- **Chrome `STATUS_ACCESS_VIOLATION`** — renderer-process crash in the media stack.
- **`<audio>` / `<video>` never reaches playable state** — infinite loading spinner.
- The HTML `<a download>` attribute is the only thing forcing a save, and it's silently ignored on cross-origin URLs.

### Symptoms

- Works fine for small files (under ~5 MB).
- Affects MP4/M4A/MP3 with metadata atoms placed at the end of the file (`moov` after `mdat`).
- Seeking the audio scrubber does nothing or restarts the entire download.
- Network tab shows status `200 OK` with `Transfer-Encoding: chunked` and no `Accept-Ranges` / `Content-Range`.

### Root Cause

`Storage::disk(...)->response()` returns a Symfony `StreamedResponse`, which **does not honor HTTP `Range:` requests**. The browser opens with `Range: bytes=0-` expecting `206 Partial Content`, but the server replies `200 OK` and streams the whole file as a single chunked response. Two things go wrong:

1. The `<audio>` element can't read codec metadata until the entire file is buffered (for mp4 containers with trailing `moov`).
2. Chrome's media stack mishandles oversized chunked media streams and crashes the renderer with `STATUS_ACCESS_VIOLATION`.

## Solution

For files on the **local disk**, swap to `BinaryFileResponse` (via `response()->file()`), which natively emits `206 Partial Content` with `Accept-Ranges: bytes` and `Content-Range: bytes <start>-<end>/<total>`.

```php
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

$disk = Storage::disk($file->disk);
if (! $disk->exists($file->identifier)) {
    abort(404);
}

$disposition = $request->boolean('dl')
    ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
    : ResponseHeaderBag::DISPOSITION_INLINE;

if ($file->disk === 'local') {
    return response()
        ->file($disk->path($file->identifier), [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ])
        ->setContentDisposition($disposition, $file->original_name);
}

// Cloud disks (s3, gcs) keep the StreamedResponse fallback; some support
// presigned URLs via $disk->temporaryUrl() which is even better.
return $disk->response($file->identifier, $file->original_name, [], $disposition);
```

### Why this fixes both symptoms

| Symptom | Reason it goes away |
|---------|---------------------|
| Chrome `STATUS_ACCESS_VIOLATION` | Response is now `206 Partial Content` with a fixed `Content-Length` per chunk — Chrome's media stack stops receiving an oversized chunked stream. |
| Infinite `<audio>` loading | Browser issues a small initial `Range:` request to read codec metadata and starts playback before downloading the full file. |
| Seek bar doesn't work | Each seek issues a fresh `Range: bytes=<offset>-` request that returns `206`. |
| `<a download>` attribute ignored cross-origin | Add `?dl=1` (or whatever flag) → server sets `Content-Disposition: attachment`, save behavior is authoritative. |

## Watch out for

- **Path traversal.** `Storage::disk('local')->path($identifier)` does NOT normalize `..` segments. Reject malformed identifiers before any filesystem call:
  ```php
  if (str_contains($identifier, '..') || str_starts_with($identifier, '/') || str_starts_with($identifier, '\\')) {
      abort(404);
  }
  ```
- **Cloud disks.** `BinaryFileResponse` requires a real filesystem path; don't use it for S3/GCS. Use `temporaryUrl()` and `redirect()` instead, or fall back to `StreamedResponse`.
- **PHP worker held during stream.** Even with Range support, `BinaryFileResponse` still pipes bytes through PHP-FPM. For high concurrency, layer `X-Accel-Redirect` (Nginx) or `X-Sendfile` (Apache mod_xsendfile) on top — auth in PHP, bytes by the web server.
- **`Content-Type` from DB, not guessed.** Set `Content-Type` from a trusted column. Don't let `BinaryFileResponse` guess it from the file extension if the original_name is user-controlled — that's a minor XSS vector for unusual extensions.
- **Browser cache.** `BinaryFileResponse` sets `Cache-Control: private, must-revalidate` by default — generally fine for authed files. Override if you need different cache semantics.

## Related Files

- [app/Http/Controllers/FileController.php](../../../app/Http/Controllers/FileController.php) — the controller that applies this pattern.
- [app/Models/File.php](../../../app/Models/File.php) — the file model whose `getUrlAttribute()` builds the route.

## Reference

- Symfony `BinaryFileResponse` docs: https://symfony.com/doc/current/components/http_foundation.html#serving-files
- HTTP `Range` requests (RFC 7233): https://datatracker.ietf.org/doc/html/rfc7233
