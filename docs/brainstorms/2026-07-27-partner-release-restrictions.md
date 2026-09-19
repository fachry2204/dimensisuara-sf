# Brainstorm — Partner Release Restrictions (Emergency)

**Date:** 2026-07-27
**Topic:** Restrict the **Partner** role in the admin releases area — published-only listing + no file downloads
**Status:** ✅ Implemented (Approach A — permission-based, emergency) on branch `hotfix/partner-release-restrictions`
**Priority:** 🚨 Emergency — favor the simplest, lowest-risk solution

---

## Problem

The **Partner** role is a limited external viewer. Today it can see and download more than it should
inside the admin releases area:

1. **Listing leaks unpublished work.** The admin releases list is gated only by the `view-releases`
   permission (which Partner has). The list itself is served by DataTables AJAX in
   `MainController::get_data` and filters purely on the `{status}` URL segment — for `all` it returns
   everything `!= 'draft'` (i.e. `pending`, `review`, `rejected`, `released`). A Partner can therefore
   see in-progress / rejected releases.
2. **Downloads are wide open.** The admin release detail page renders album-cover and track
   (full + trimmed) download links to every viewer with no role check.

### As-found facts (from code investigation)

| Concern | Location | Note |
|---|---|---|
| Releases route group | `routes/web/admin.php:60,137` | gated by `can:view-releases` only |
| List query (real) | `app/Http/Controllers/Admin/Aggregator/MainController.php:534-608` | `get_data`, filters by `{status}`; `all` → `!= 'draft'` |
| Index counts / tabs | `MainController.php:33-42` → `resources/views/admin/aggregator/index.blade.php` | status tabs + badge counts for every status |
| Release detail (admin) | `MainController::show` (`MainController.php:44-48`) → `admin.aggregator.show` | includes `components.release` with `['type' => 'admin']` |
| Album-cover download | `resources/views/components/release.blade.php:12-13` | plain `<a download>` link |
| Track download (full) | `resources/views/components/release.blade.php:195-199` | plain `<a download>` link |
| Track download (trimmed) | `resources/views/components/release.blade.php:208-212` | plain `<a download>` link |
| Inline audio players | `components/release.blade.php:193, 206` | same URLs as downloads (see Known gap) |
| File-serving endpoint | `app/Http/Controllers/FileController.php:16-89` (`file.access`) | authorizes if `Gate::allows('admin')` **OR** owner — a Partner passes the `admin` gate |

### Key architecture note

This app does **not** use spatie/laravel-permission. Authorization is permission-slug based via
`Gate::before` (`app/Providers/AppServiceProvider.php:32-34`). The **Partner** role is seeded in
`database/seeders/RoleSeeder.php:81-89` with a single permission, `view-releases`. There is **no**
`isPartner()` / `hasRole()` helper and no `role` column — roles attach via the `access_user_roles`
pivot. "Keying off Partner" therefore means a **role-name** check
(`$user->roles()->where('name', 'Partner')->exists()`), which is new to this codebase.

There is also **no status enum**: `"published" === the literal string 'released'`
(status set: `draft, pending, review, rejected, released`).

---

## Decisions (confirmed)

1. **Requirement 2 (no downloads) applies to the Partner role only.** Super Admin / Operator /
   Finance keep their download buttons.
2. **A Partner is blocked from directly opening a non-published release** (defense in depth), not just
   filtered from the list — so URL-editing can't bypass the filter.

---

## Chosen approach — "simplest that holds"

One tiny helper + three thin guards. No data/schema change, no new routes.

### Step 0 — Partner detection helper (single source of truth)

Add to `app/Models/User/User.php`:

```php
public function isPartner(): bool
{
    return $this->roles()->where('name', 'Partner')->exists();
}
```

Rationale: role-name checks are new here, so centralize the one string `'Partner'` and the
published constant in ONE place rather than scattering the literal across controller + blade.
(Consider a `const PUBLISHED_STATUS = 'released';` on `Release` while we're at it, to avoid a bare
string — optional, see Enhancements.)

### Step 1 — Req 1a: Partner list shows published only

In `MainController::get_data`, after `$query = Release::query();`, force the status for Partners
regardless of the requested `{status}` segment:

```php
if (auth()->user()->isPartner()) {
    $query->where('releases.status', 'released');
}
```

This is authoritative even if a Partner hand-crafts an AJAX URL for another status.

### Step 1b — hide the non-published status tabs for Partner

In `admin/aggregator/index.blade.php`, wrap the pending/review/rejected/draft status tabs (and their
badge counts) in `@unless(auth()->user()->isPartner()) … @endunless`, leaving only "Released" (and
optionally "All", which for a Partner is equivalent). Prevents a confusing dead tab and stops the
count badges from leaking how many unpublished releases exist.

### Step 2 — Req 2: hide download buttons for Partner (view flag, not a fork)

Pass a capability flag into the shared partial from the admin show view
(`resources/views/admin/aggregator/show.blade.php:64`):

```blade
@include('components.release', ['type' => 'admin', 'canDownload' => ! auth()->user()->isPartner()])
```

Then guard the three download links in `components/release.blade.php` (`:12-13`, `:195-199`,
`:208-212`) with a default-true flag so **all other includes of this shared partial are unaffected**:

```blade
@if ($canDownload ?? true)
    {{-- existing <a download> link --}}
@endif
```

Why the flag beats a separate component: `components/release.blade.php` is a **shared** partial used
beyond the admin show page; forking it into a `release-partner.blade.php` duplicates ~200 lines and
guarantees future drift. A `?? true` flag is a ~6-line change and keeps one source of truth.

### Step 3 — Req (defense in depth): block Partner from unpublished detail page

In `MainController::show`, right after loading `$release`:

```php
if (auth()->user()->isPartner() && $release->status !== 'released') {
    abort(404); // 404 (not 403) so we don't confirm the release exists
}
```

---

## Known gap (call out — do NOT silently ship as "fully disabled")

Steps 1–3 remove the **UI affordances**, but the file-serving endpoint
`FileController::access` still authorizes a Partner (they pass the `admin` gate,
`FileController.php:44`). A technical Partner could still:

- hit a `file.access` signed URL directly, or
- pull the audio stream from the inline `<audio>` players (`components/release.blade.php:193,206`),
  whose `src` is the same URL as the download link.

**Recommendation (low effort, high value):** add a server-side guard so the fix can't be trivially
bypassed. Two options:

- **A — block cover/track files for Partner in `FileController::ensureAuthorized`** (deny if
  `$user->isPartner()` and the file's morph type is `cover`/`full`/`trimmed`). Most robust.
- **B — also hide the inline `<audio>` players for Partner** behind the same `$canDownload` flag, so
  the raw URL isn't printed into the page at all. Cheaper, still bypassable via a crafted URL.

**Open decision for `/plan`:** ship UI-only (Steps 1–3) as the true emergency minimum, or include the
server-side guard (A) now? See "Needs a call" below.

---

## Edge cases to confirm at plan/implementation

- **Multi-role user.** `isPartner()` returns true if the user holds the Partner role *at all*. A user
  who is both Partner **and** Super Admin would get restricted. Given Partner is provisioned as a
  standalone external role, treat "has Partner role" = restricted. Flag if that combo is real.
- **`all` tab for Partner.** With Step 1 forcing `released`, the "all" and "released" tabs return the
  same rows for a Partner — harmless; Step 1b hides the redundant one.
- **Other includes of `components.release`.** Confirm every other `@include('components.release', …)`
  does not pass `canDownload` (so it defaults to `true`) — verify during plan.

---

## Enhancements (optional, note for `/plan`)

- **E1 — `Release::PUBLISHED_STATUS` constant** instead of the bare string `'released'` in the new
  guards. Small readability win; consistent with avoiding magic strings.
- **E2 — Gate/policy instead of inline `isPartner()` checks.** Cleaner long-term, but heavier than an
  emergency warrants. Deferred.

---

## Affected use cases

```
MODIFIED:
  - admin releases list (Partner → released-only)      MainController::get_data
  - admin releases index tabs/counts (Partner)         admin/aggregator/index.blade.php
  - admin release detail downloads (Partner → hidden)  components/release.blade.php + aggregator/show.blade.php
  - admin release detail access (Partner + unpublished → 404)  MainController::show
NEW:
  - User::isPartner() helper                           app/Models/User/User.php
  - (optional) server-side download guard              FileController::ensureAuthorized  [Needs a call]
UNCHANGED (guarded by default-true flag):
  - all non-admin includes of components/release.blade.php
DATA/SCHEMA: none
REMOVED: none
```

---

## Needs a call before / during `/plan`

1. **Scope of "disable downloads": UI-only vs. server-enforced?** Steps 1–3 (UI) are the true
   emergency minimum; option A (FileController guard) closes the direct-URL / audio-stream bypass.
   Recommendation: include option A — it's small and the whole point is Partners shouldn't obtain
   the files.
2. Confirm the multi-role edge case (Partner + admin) is not a real provisioned combination.

---

---

## Implementation (Approach A — shipped)

Permission-based (not role-name). Two additive permissions gate the behavior; **Partner lacks both**,
every other releases-role (Super Admin, Operator) has both.

| Permission | Grants |
|---|---|
| `view-releases-all` | see releases of any status (absent → published-only) |
| `download-release-files` | download release cover + track audio (absent → no downloads) |

**Files changed:**

- `database/seeders/PermissionSeeder.php` — define the 2 new permissions (fresh installs).
- `database/seeders/RoleSeeder.php` — grant both to Super Admin + Operator (fresh installs).
- `database/migrations/2026_07_27_000000_add_release_scope_permissions.php` — **prod path**:
  idempotently inserts the 2 permissions and grants them to Super Admin + Operator
  (`firstOrCreate` + `syncWithoutDetaching`). Seeders are non-idempotent, so existing DBs use this.
- `MainController::get_data` — `!can('view-releases-all')` → force `status = 'released'` (authoritative,
  ignores the `{status}` AJAX segment).
- `MainController::show` — `!can('view-releases-all')` && not released → `abort(404)` (direct-URL guard).
- `admin/aggregator/index.blade.php` — limited viewers get only the "Published" tab; the DataTables
  init loop is driven by the rendered tabs so it stays in sync.
- `admin/aggregator/show.blade.php` — passes `canDownload => can('download-release-files')` into the partial.
- `components/release.blade.php` — cover download button + the whole track audio/download block guarded
  by `$canDownload ?? true` (default true → customer & other includes untouched).
- `FileController::ensureAuthorized` — server-side block of `full`/`trimmed` audio for non-owners
  lacking `download-release-files` (closes the direct-URL / audio-stream bypass).

**Deploy steps:** `php artisan migrate` (grants the new permissions to existing admin roles).
No cache step required; `view:cache` was validated to compile.

### Accepted limitations (deliberate, for emergency scope)

- **Album cover is UI-only gated, not server-blocked.** The cover *image* on the detail page is served
  through the same `file.access` URL as its download button, so blocking it server-side would break the
  image. Cover art of a **released** title is effectively public, so we hide the download button and
  leave the image viewable. A determined Partner could still fetch the cover via a crafted URL. Audio
  masters (`full`/`trimmed`) ARE server-blocked.
- **Playback is disabled for Partner too** (not just download) — the server guard blocks the audio
  endpoint entirely; there is no stream-vs-download distinction today. If Partners should be able to
  *listen* to published tracks but not download, that needs a follow-up (`?dl=1`-based disposition gate).
- **IPL file** (`type = ipl`) download is left as-is (requirement named only cover + track files).
- **Summary count cards** on the index (Albums/Singles/Tracks) still count all statuses — aggregate
  numbers only, not a list of unpublished releases.
- **Multi-role edge case:** a user holding Partner **plus** an admin role would get full access (they'd
  have the permissions via the other role). Fine as long as Partner is provisioned standalone.

> Long-term follow-up (see conversation): lift these inline `can()` checks into a `Release::scopeVisibleTo()`
> + `ReleasePolicy`, and add a `/partner` portal only if Partner becomes a genuinely external audience.
