# Release Audio — Range Streaming & Download Split

**Date:** 2026-05-20
**Component:** [resources/views/components/release.blade.php](../../resources/views/components/release.blade.php)
**Controller:** [app/Http/Controllers/FileController.php](../../app/Http/Controllers/FileController.php)

## Problem

The `<audio>` and download `<a>` in the release component both point at the
same Laravel route, which serves files via `Storage::disk()->response()`. For
audio files up to **200 MB** stored on the local filesystem, this causes:

- **Chrome `STATUS_ACCESS_VIOLATION`** on playback (renderer crash in the
  media stack).
- **Infinite loading** on the `<audio>` element — never reaches a playable
  state.

### Root cause

`Storage::response()` returns a Symfony `StreamedResponse` that does not honor
HTTP `Range:` requests. The `<audio>` element relies on `206 Partial Content`
to read codec metadata (e.g. mp4 `moov` atom) and to seek. Without it, the
browser must buffer the entire 200 MB before playback can start, and the
resulting oversized chunked stream is what crashes Chrome.

### Environment constraints

- Storage disk: **local** (Laravel `local` disk, files under `storage/app/`).
- Web server: **Plesk** with **Nginx in proxy mode** in front of Apache + PHP-FPM.
- PHP: `memory_limit=1000M`, `max_execution_time=300`.
- Production-only — no staging; the developer **cannot test prod
  filesystem/web-server changes locally**.
- Auth requirement: every byte must remain gated; no unauthenticated access.

## Decision

**Option A — `BinaryFileResponse` with play/download split**, plus
enhancements **#1 (hash verification)** and **#2 (null-guard for
`current_user()`)**.

Rejected alternatives:

- **Option B (`X-Accel-Redirect` via Plesk Nginx directives).** Correct
  long-term answer for high concurrency, but ~30 minutes of irreducibly
  production-only work (vhost edit, perms check, smoke test). Deferred until
  PHP-FPM worker exhaustion is actually observed.
- **Option C (public signed URL to static path).** Conflicts with auth
  requirement — TTL-based access is not equivalent to per-user gating.

## Design

### Data flow

```
<audio src="…/file/access/{id}?hash=…">          ← inline playback
<a    href="…/file/access/{id}?hash=…&dl=1">     ← attachment download
        │
        ▼
    GET /file/access/{file}
        │
    FileController::access($file, $request)
        │
        ├─ hash check: hash_equals(sha1($id.'|'.updated_at), $request->hash) ── fail → 403
        ├─ auth gate: Gate::allows('admin')
        │              || (current_user()?->profile?->id === $file->profileid) ── fail → 403
        ├─ Storage::disk($file->disk)->exists()?                              ── no   → 404
        │
        ├─ $disposition = $request->boolean('dl') ? ATTACHMENT : INLINE
        │
        └─ disk === 'local'
              ├─ yes → response()->file($disk->path($file->identifier), [
              │           'Content-Type' => $file->mime_type ?: 'application/octet-stream',
              │       ])->setContentDisposition($disposition, $file->original_name)
              │       // BinaryFileResponse — emits 206 Partial Content, Accept-Ranges, Content-Range
              │
              └─ no  → $disk->response($file->identifier, $file->original_name, [], $disposition)
                      // Defensive fallback for non-local disks (e.g. 'google')
```

The same change applies to `FileController::signee()` for parity.

### Files to change

| File | Change |
|------|--------|
| [app/Http/Controllers/FileController.php](../../app/Http/Controllers/FileController.php) | Rewrite `access()` and `signee()`: add hash verification, null-guard `current_user()`, return `BinaryFileResponse` for local disk with disposition driven by `?dl=1`. |
| [resources/views/components/release.blade.php](../../resources/views/components/release.blade.php) | Append `?dl=1` to the two track download anchors (L177, L185). Keep the HTML `download="…"` attribute as filename hint. |

The album-cover anchor at L10 is small and out of scope for this brainstorm.

### Controller — target shape

```php
public function access(File $file, Request $request)
{
    if (! hash_equals(sha1($file->id . '|' . $file->updated_at), (string) $request->hash)) {
        abort(403, 'Unauthorized');
    }

    $user = current_user();
    $isOwner = $user?->profile && $user->profile->id === $file->profileid;

    if (! (Gate::allows('admin') || $isOwner)) {
        abort(403, 'Unauthorized');
    }

    $disk = Storage::disk($file->disk);

    if (! $disk->exists($file->identifier)) {
        abort(404, 'File not found');
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

    return $disk->response($file->identifier, $file->original_name, [], $disposition);
}
```

### Blade — target shape

```blade
<a href="{{ $track->full_url }}&dl=1"
   download="release-{{ $release->id }}-track-{{ $track->id }}"
   class="btn btn-sm btn-download btn-outline-primary m-1"
   onclick="event.stopImmediatePropagation();">
    <i class="bx bx-download"></i> Track
</a>
```

Note: since `$track->full_url` already carries a `?hash=…` query string, use
`&dl=1` (not `?dl=1`). Same for the trimmed button.

## Symptoms resolved

| Symptom | Resolution |
|---------|------------|
| Chrome `STATUS_ACCESS_VIOLATION` | `BinaryFileResponse` sends `206 Partial Content` with proper `Content-Length`/`Content-Range` per chunk — Chrome's media stack stops receiving an oversized chunked stream. |
| Infinite `<audio>` loading | Browser can issue a small initial `Range:` request to read codec metadata and start playback before downloading the full 200 MB. |
| Download attribute occasionally ignored | Server now authoritatively sets `Content-Disposition: attachment` when `?dl=1` is present. |

## Local verification

1. Create a 200 MB dummy mp3: `fsutil file createnew dummy.mp3 209715200`
   (PowerShell on Windows).
2. Point a `Track`'s `File` row at it (or upload through the normal flow).
3. Open the release in browser, DevTools → Network:
   - Audio request: status `206`, headers `Accept-Ranges: bytes`,
     `Content-Range: bytes 0-…/209715200`.
   - Drag the scrubber: fresh `206` for the seeked range.
   - Click Download: URL has `&dl=1`, response
     `Content-Disposition: attachment; filename="…"`, full file saves.
4. Logged-out / wrong-profile user: `403`.
5. Tampered hash query: `403`.

## Out of scope (deferred)

- **Option B (`X-Accel-Redirect`)** — revisit only if PHP-FPM worker
  exhaustion is observed under concurrent listeners.
- Album-cover download anchor at component L10.
- `Cache-Control: private, max-age=…` on file responses (minor UX win on
  repeat seeks).

## Status

> Brainstorm complete. Run `/plan` when ready.
