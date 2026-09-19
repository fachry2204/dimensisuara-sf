# Release Audio — Range Streaming & Download Split — Implementation Plan

**Date:** 2026-05-20
**Brainstorm:** [docs/brainstorms/2026-05-20-release-audio-range-streaming.md](../brainstorms/2026-05-20-release-audio-range-streaming.md)
**Scope:** Option A (`BinaryFileResponse` + play/download split) + enhancements #1 (hash verification) and #2 (`current_user()` null-guard).

## Context

`<audio>` and download `<a>` in [resources/views/components/release.blade.php](../../resources/views/components/release.blade.php) both call into [FileController::access()](../../app/Http/Controllers/FileController.php), which serves files with `Storage::disk()->response()`. That response is a Symfony `StreamedResponse` that does **not** honor HTTP `Range:` requests, so:

- 200 MB audio files cause Chrome `STATUS_ACCESS_VIOLATION` (renderer crash on oversized chunked media).
- `<audio>` never reaches a playable state (cannot read mp4 `moov` metadata without a partial GET).
- The download `<a download>` attribute is the only thing forcing save behavior — server returns inline.

The fix replaces the response for the `local` disk with `BinaryFileResponse` (via `response()->file()` / `->download()`), which natively handles `Range` and emits `206 Partial Content`. A `?dl=1` query flag lets the server authoritatively send `Content-Disposition: attachment` for download buttons. The existing-but-unverified `hash` query param starts being verified, and `current_user()` is null-guarded so an expired session no longer 500s.

## Structure Check

The change is small and contained. The controller already keeps HTTP concerns + auth gating only (no business logic, no DB queries), which is appropriate for a file-serving endpoint. **No Phase 1 restructuring required.**

Future improvement noted but **out of scope:** moving the gate logic into a `FilePolicy` class. Existing pattern uses inline `Gate::allows('admin')` plus ownership checks, which is consistent across the codebase; not blocking.

## Phase 1 — Backend

### Task 1.1 — Rewrite `FileController::access()` and `signee()`

**File:** [app/Http/Controllers/FileController.php](../../app/Http/Controllers/FileController.php)

**Change:** Replace the full file with the version below. Adds (1) hash verification using `hash_equals`, (2) null-guarded `current_user()`, (3) Range-aware `BinaryFileResponse` for local disk, (4) `?dl=1` driving `Content-Disposition`. Keeps `Storage::response()` as a defensive fallback for any non-`local` disk (e.g. `google`).

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use App\Models\File;

class FileController extends Controller
{
    public function access(File $file, Request $request)
    {
        $this->ensureAuthorized($file, $request);

        return $this->serve($file, $request);
    }

    public function signee($token, File $file, Request $request)
    {
        if (! hash_equals(sha1($file->id . '|' . $file->updated_at), (string) $request->hash)) {
            abort(403, 'Unauthorized');
        }

        $signee = $request->signee;

        if (! $signee || ! optional($signee->publishing)->profileid
            || $signee->publishing->profileid !== $file->profileid) {
            abort(403, 'Unauthorized');
        }

        return $this->serve($file, $request);
    }

    protected function ensureAuthorized(File $file, Request $request): void
    {
        if (! hash_equals(sha1($file->id . '|' . $file->updated_at), (string) $request->hash)) {
            abort(403, 'Unauthorized');
        }

        $user = current_user();
        $isOwner = $user && $user->profile && (int) $user->profile->id === (int) $file->profileid;

        if (! (Gate::allows('admin') || $isOwner)) {
            abort(403, 'Unauthorized');
        }
    }

    protected function serve(File $file, Request $request)
    {
        // Defense-in-depth: reject malformed identifiers before they reach
        // the filesystem layer. The local-disk path() method does not strip
        // traversal segments, so a corrupted DB row could otherwise resolve
        // outside storage/app.
        $identifier = (string) $file->identifier;
        if ($identifier === ''
            || str_contains($identifier, '..')
            || str_starts_with($identifier, '/')
            || str_starts_with($identifier, '\\')) {
            abort(404, 'File not found');
        }

        $disk = Storage::disk($file->disk);

        if (! $disk->exists($identifier)) {
            abort(404, 'File not found');
        }

        $disposition = $request->boolean('dl')
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        if ($file->disk === 'local') {
            return response()
                ->file($disk->path($identifier), [
                    'Content-Type' => $file->mime_type ?: 'application/octet-stream',
                ])
                ->setContentDisposition($disposition, $file->original_name);
        }

        // Defensive fallback for non-local disks (e.g. 'google').
        return $disk->response($identifier, $file->original_name, [], $disposition);
    }
}
```

**Why each piece:**

- `hash_equals(...)` — constant-time comparison; prevents both timing oracles and trivial URL-tampering. The hash is generated in [App\Models\File::getUrlAttribute()](../../app/Models/File.php#L64-L71) as `sha1($id . '|' . $updated_at)` so no model changes are needed.
- `current_user()` may return `null` when the session expires mid-playback (see [app/helpers.php:7](../../app/helpers.php#L7)). The chained null check stops it from throwing 500.
- Identifier traversal guard rejects `..`, leading slashes/backslashes, and empty strings before any filesystem call. `Storage::disk('local')->path()` does not normalize traversal segments, so this prevents a corrupted DB row from resolving outside `storage/app/`.
- `response()->file($absolutePath, $headers)` returns `Symfony\Component\HttpFoundation\BinaryFileResponse`, which sets `Accept-Ranges: bytes` and replies `206 Partial Content` to `Range:` requests automatically.
- `setContentDisposition($disposition, $file->original_name)` makes the download/play split authoritative on the server side.
- The `'local'` branch is gated explicitly because `BinaryFileResponse` requires a real filesystem path; cloud disks fall back to the existing `StreamedResponse` path.

**Verification:**
```bash
php -l app/Http/Controllers/FileController.php
# Expected: No syntax errors detected
```

```bash
php artisan route:list --path=file
# Expected: file.access and file.signee routes still present, unchanged signatures
```

---

## Phase 2 — Frontend

### Task 2.1 — Append `&dl=1` to track download anchors

**File:** [resources/views/components/release.blade.php](../../resources/views/components/release.blade.php)

`$track->full_url` and `$track->trimmed_url` already carry `?hash=…` (built in `File::getUrlAttribute()`), so the download flag must be appended with `&`, not `?`.

**Edit 1 — line 177 (full track download):**

Before:
```blade
<a href="{{ $track->full_url }}" download="release-{{ $release->id }}-track-{{ $track->id }}" class="btn btn-sm btn-download btn-outline-primary m-1" onclick="event.stopImmediatePropagation();"><i class="bx bx-download"></i> Track</a>
```

After:
```blade
<a href="{{ $track->full_url }}&dl=1" download="release-{{ $release->id }}-track-{{ $track->id }}" class="btn btn-sm btn-download btn-outline-primary m-1" onclick="event.stopImmediatePropagation();"><i class="bx bx-download"></i> Track</a>
```

**Edit 2 — line 185 (trimmed track download):**

Before:
```blade
<a href="{{ $track->trimmed_url }}" download="release-{{ $release->id }}-track-{{ $track->id }}-trimmed" class="btn btn-sm btn-download btn-outline-primary m-1" onclick="event.stopImmediatePropagation();"><i class="bx bx-download"></i> Trimmed</a>
```

After:
```blade
<a href="{{ $track->trimmed_url }}&dl=1" download="release-{{ $release->id }}-track-{{ $track->id }}-trimmed" class="btn btn-sm btn-download btn-outline-primary m-1" onclick="event.stopImmediatePropagation();"><i class="bx bx-download"></i> Trimmed</a>
```

**Untouched on purpose:**
- `<audio src="{{ $track->full_url }}">` at L176 — no `&dl=1` because audio needs inline disposition.
- `<audio src="{{ $track->trimmed_url }}">` at L184 — same.
- Album cover anchor at L10 — small file, separate concern, out of scope.

**Verification:**
```bash
php artisan view:clear
```

Then load the release page in a browser and confirm:
- DevTools → Elements → the two download `<a>` `href`s end in `&dl=1`.
- The two `<audio>` `src`s do **not** contain `dl=1`.

---

## Phase 3 — Testing and Verification

No automated test suite is being added (no existing controller tests for `FileController`; adding one would require fixturing the `File` model, disk, hash, and auth — out of scope and noted as future work). Verification is manual against a representative file.

### Task 3.1 — Generate a 200 MB dummy audio file

On Windows (PowerShell):
```powershell
fsutil file createnew dummy.mp3 209715200
```

Place it in `storage/app/` and create or repoint a `filestorage` row at it (or use any existing track that already has a `full` file). Note the `File` row's `id` and `disk` (`local`).

### Task 3.2 — Verify Range support on playback

1. Log in as the file's owner (or admin).
2. Open the release page; expand the track accordion so the `<audio>` element is visible.
3. DevTools → Network → click play.
4. Inspect the audio request:
   - **Status:** `206 Partial Content`
   - **Response headers include:** `Accept-Ranges: bytes`, `Content-Range: bytes 0-…/209715200`, `Content-Disposition: inline; filename="…"`, correct `Content-Type`.
5. Drag the seek bar to the middle.
   - **Expected:** a new request appears with `Range: bytes=<midpoint>-` and a fresh `206` response. Playback resumes within seconds.
6. Repeat on a fresh Chrome window — no `STATUS_ACCESS_VIOLATION`, no infinite buffering.

### Task 3.3 — Verify download split

1. Click the "Track" download button.
2. Inspect the request:
   - URL ends in `&dl=1`.
   - Response header: `Content-Disposition: attachment; filename="<original_name>"`.
   - Browser saves the file rather than streaming inline.
3. Repeat for the "Trimmed" button.

### Task 3.4 — Verify hash and auth enforcement

1. Copy the audio URL, change one character of the `hash` query value, paste in a new tab.
   - **Expected:** `403 Unauthorized`.
2. Log out (or open in an incognito window without auth) and hit the original URL.
   - **Expected:** `403 Unauthorized` (gate check fails because `current_user()` is null).
3. Log in as a different non-admin user whose profile does **not** own the file, hit the original URL.
   - **Expected:** `403 Unauthorized`.
4. Log in as the owning profile, hit the URL.
   - **Expected:** `206 Partial Content`, audio streams.
5. Log in as an admin (different user), hit the URL.
   - **Expected:** `206 Partial Content`, audio streams.

### Task 3.5 — Verify no 500 when session is gone mid-stream

1. In one browser tab, start playing audio.
2. In another tab, log out.
3. Drag the audio seek bar in the first tab to force a new `Range` request.
   - **Expected:** `403 Unauthorized` (clean abort). **Not:** HTTP 500.

### Task 3.6 — Regression check on other File consumers

The same controller serves more than just track audio. Quickly confirm nothing else breaks:

```bash
# Find every place that uses the file.access URL (i.e. ->url on a File model)
```

Search the codebase for other usages of `$something->url` resolved from `App\Models\File`. Spot-check that:
- Album cover image still loads on the release page ([release.blade.php:6](../../resources/views/components/release.blade.php#L6)).
- Album cover download still works ([release.blade.php:10](../../resources/views/components/release.blade.php#L10)).

These use the same controller and benefit from Range support automatically; no functional change expected.

---

## Files Touched (Summary)

| File | Change |
|------|--------|
| [app/Http/Controllers/FileController.php](../../app/Http/Controllers/FileController.php) | Rewrite — adds hash verification, null-guard, `BinaryFileResponse` for local, disposition split. |
| [resources/views/components/release.blade.php](../../resources/views/components/release.blade.php) | Append `&dl=1` to two download anchors (L177, L185). |

No DB migrations, no model changes, no route changes, no new dependencies.

## Rollback

`git revert <commit>` restores the previous behavior. No DB or external state changes. View cache: `php artisan view:clear`.

## Deferred Risks (tracked for future work)

The `/gate` review surfaced three additional findings that are **not** addressed by this plan. They are documented here so they don't get lost.

### DR-1 — No rate limiting on `/file/access/{file}` (OWASP A04)

- **Risk:** Endpoint now serves up to 200 MB per request with no per-user / per-IP cap. A single authenticated session can loop the URL to exhaust outbound bandwidth, and there is no throttle on repeated bad-hash probes (which could be used to discover valid `(file_id, updated_at)` pairs over time).
- **Why deferred:** Existing site has no other rate-limited file endpoints; introducing throttling here without a site-wide policy is inconsistent. Should be designed alongside a broader rate-limit strategy.
- **Suggested future fix:** Add `throttle:60,1` (or a custom limiter that caps total bytes per minute per user) to the `file.access` and `file.signee` routes in [routes/web/public.php](../../routes/web/public.php#L40). Verify normal playback (which issues a handful of `Range:` requests per session) stays well under the limit.

### DR-2 — No security event logging on access denials (OWASP A09, ISO A.12 / A.16)

- **Risk:** Every `abort(403)` and `abort(404)` in `FileController` is silent. There is no audit trail for repeated tamper attempts, probe activity, or expired-session misuse. Without logs, an active enumeration or abuse incident is invisible until bandwidth/uptime alarms fire.
- **Why deferred:** Requires a project-wide decision on log channel, retention, PII redaction policy, and whether to alert (Slack/email) on threshold breaches. Out of scope for a controller-level fix.
- **Suggested future fix:** Emit `Log::warning('file.access denied', ['file_id' => ..., 'reason' => 'bad_hash|gate|missing', 'user_id' => ..., 'ip' => request()->ip()])` on each `abort()` branch. Configure a dedicated `security` log channel with appropriate retention.

### DR-3 — SHA-1 for tamper hash, no secret component (OWASP A02, ISO A.10)

- **Risk:** `File::getUrlAttribute()` builds `sha1($id . '|' . $updated_at)` — SHA-1 is deprecated for cryptographic use, and the input is fully public (`id` is sequential; `updated_at` is observable). Anyone who can guess or observe these can forge a valid hash. The session/profile gate is still the real authorization layer, so this is **defense-in-depth only**, but the hash provides no actual cryptographic guarantee today.
- **Why deferred:** Changing the hash invalidates any URLs currently in users' clipboards / open tabs / email links, and touches both `File` model accessors (`getUrlAttribute`, `getUrlAsSigneeAttribute`) and both controller methods. Worth doing as its own focused change.
- **Suggested future fix:** Replace with `hash_hmac('sha256', $file->id . '|' . $file->updated_at, config('app.key'))` in [App\Models\File](../../app/Models/File.php#L64-L81) and the matching `hash_equals(...)` calls in `FileController`. Coordinate the deploy so old hashes are invalidated cleanly.

### DR-4 — No automated test coverage for `FileController` (Lens 1 Gap, Lens 4 Testing)

- **Risk:** `FileController` now contains 6 distinct failure paths (bad hash, missing/expired user, wrong profile, traversal identifier, missing file on disk, cloud-disk fallback) and 2 disposition variants (`inline` vs `attachment`). It also carries security-load-bearing behavior (auth gate, tamper detection, path-traversal guard, MIME-sniffing protection). Without tests, regressions on any of these branches are silent until reported by users — and the symptoms (403 vs 404 vs XSS via mime sniffing) are hard to distinguish in logs.
- **Why deferred:** Adding `Storage::fake()`-based feature tests requires a `File`-model fixture, an authenticated user factory wired to a `Profile`, and a way to invoke the route with valid/invalid hashes. None of these test helpers exist in this repo today (existing `tests/Feature` files are framework scaffolding). Building that fixture infrastructure is a larger investment than this fix.
- **Suggested future fix:** Add `tests/Feature/FileAccessTest.php` covering at minimum:
  - `403` on missing/tampered `hash`
  - `403` on logged-out request
  - `403` on logged-in non-owner non-admin
  - `404` on traversal identifier (`..`, leading `/`, leading `\\`, empty)
  - `404` on missing file with valid auth
  - `200`/`206` on owner with `Accept-Ranges: bytes`, correct `Content-Type`, and `X-Content-Type-Options: nosniff`
  - `Content-Disposition: attachment` when `?dl=1` is passed, `inline` otherwise

  Use `Storage::fake($file->disk)` to avoid touching real storage. Reuse fixtures across the `signee()` route once the helper infrastructure exists.

## Acceptance Criteria

- [ ] `<audio>` requests for 200 MB tracks return `206 Partial Content` with `Accept-Ranges: bytes` and `Content-Range: bytes 0-…/<size>`.
- [ ] Audio playback starts within ~2 seconds of pressing play, before the full file is buffered.
- [ ] Seeking in the `<audio>` scrubber issues a fresh `Range:` request that returns `206` and resumes playback.
- [ ] Chrome no longer crashes with `STATUS_ACCESS_VIOLATION` on these files.
- [ ] Clicking "Track" or "Trimmed" download button saves the file with the original filename and `Content-Disposition: attachment` on the response.
- [ ] Tampering with the `hash` query string returns `403`.
- [ ] Logged-out access returns `403` (no 500).
- [ ] Logged-in non-owner non-admin returns `403`.
- [ ] Logged-in owner profile or admin streams successfully.
- [ ] Album cover image and download on the release page still work (regression check).
- [ ] `php -l app/Http/Controllers/FileController.php` reports no syntax errors.
- [ ] `php artisan route:list --path=file` shows `file.access` and `file.signee` unchanged.
- [ ] Identifier traversal guard rejects any `..`, leading `/`, leading `\`, or empty identifier with `404` before touching the disk (covered by code review; not feasible to exercise end-to-end without DB tampering).

---

> Plan ready. Run `/gate` to review before coding.
