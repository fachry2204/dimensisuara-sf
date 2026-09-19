# Delete Draft/Rejected Publishing Request

**Date:** 2026-07-20
**Status:** Brainstorm complete — awaiting `/plan`
**Chosen option:** A (service-layer status guard) + enhancements 1, 2, 3

---

## Problem

Customers cannot delete a publishing request from the UI, unlike release requests
which support deletion in `draft` and `rejected` status.

## Key finding: the feature is ~80% built, and currently a live hole

| Layer | State before this work |
|---|---|
| Route | Exists — `routes/web/customer.php:121` `DELETE publishing/{id}` |
| Controller | Exists — `PublishingController::destroy()` (165-197) |
| Service | Exists — `PublishingService::delete()` (212-219) |
| Confirm modal + JS | Exists — `customer/publishing/index.blade.php:48`, `:80` |
| Delete button | Commented out, labelled `Delete (temp)` — `index.blade.php:153` |
| **Status guard** | **Missing entirely** |

**P1.** The route is registered and reachable. `destroy()` filters on `profileid`
only; `PublishingService::delete()` has no status check — unlike every other method
in that service (`cancel` requires `pending`:184, `publish` requires `draft`:98,
`adminApprove` requires `review`:360). The button was hidden but the endpoint shipped.

Any authenticated customer can `DELETE /publishing/{id}` on their own **`completed`**
request today. It hard-deletes the row plus CASCADEs every signee — including accepted
signatures with `accepted_at` timestamps — on a request with a countersigned contract.
The contract row survives via `morphOne` and becomes an orphan pointing at a dead
`owner_id`.

`ReleasesController.php:92-93` does it correctly: `whereIn('status', ['draft','rejected'])`
in the lookup, so wrong-status IDs 404.

`PublishingPolicy::delete()` returns hardcoded `false` and is never called.

## Decisions

| Question | Decision |
|---|---|
| Deletable statuses | `draft` + `rejected` only — match release deletion |
| Signee CASCADE on delete | Accepted. Hard delete. No `SoftDeletes`. Audit trail lives in `logs` via `DatabaseLogger` |
| UI surface | Index row only (this *is* the release pattern — release has no detail-page delete) |
| Auto-purge (`delete_by` + job) | No. Manual delete only |

Publishing statuses are richer than releases: `draft, pending, rejected, expired,
cancelled, review, completed` (`2025_10_01_163656_publishing_add_statuses.php:18`).
`cancelled` and `expired` remain undeletable by this decision.

### Resolved concern: no orphaned signing tokens

With the list narrowed to `draft` + `rejected`, deletion cannot invalidate a live
signing link:

- On reject, `signeeReject:333-335` already flips every remaining `pending` signee
  to `cancelled`.
- `draft` requests never had notifications sent — that happens in `publish()`.

## Options considered

| Option | Approach | Verdict |
|---|---|---|
| **A** | Guard in `PublishingService::delete()` via `InvalidRequestStatusException` | **Chosen.** Matches the module's own convention (5 methods do this). Protects every caller — future admin path, console command, job — not just this HTTP route. Service is single source of truth for the state machine |
| B | Guard in the controller lookup (`whereIn`), copying `ReleasesController` | Leaves the service unguarded one layer down. Adopted as *enhancement 1* on top of A, not instead of it |
| C | Guard in `PublishingPolicy::delete()` | Rejected. Conflates authorization (who) with state machine (when). Splits state rules into a third file |

---

## Design

### Data flow

```
Customer clicks Delete (index row, draft|rejected only)
  -> deletePublishing(event) reads data-url, sets form action, shows modal
  -> POST + @method('DELETE') -> DELETE /publishing/{id}
  -> [auth, verified, can:customer, CustomerVerified]
  -> PublishingController::destroy($id)
      -> Publishing::where('profileid', ...)->whereIn('status', ['draft','rejected'])
             ->findOrFail($id)                      // ownership + status -> 404
      -> PublishingService::delete($publishing)
          -> status guard -> InvalidRequestStatusException if not draft|rejected
          -> DB::transaction { $publishing->delete() }
              -> signees CASCADE (FK)
              -> documents SET NULL (archival, preserved)
              -> contracts untouched (morphOne, no FK)
              -> release_id / track_id are the FK's own side; parents unaffected
      -> Log::info + redirect to publishing.index with success
```

### Changes by file

**No migration. No new route. No new model, policy, or notification.**

| # | File | Change |
|---|---|---|
| 1 | `app/Services/Publishing/PublishingService.php:212-219` | Add status guard; type-hint `Publishing $publishing` (currently untyped, unlike `cancel()`) |
| 2 | `app/Http/Controllers/PublishingController.php:165-197` | Add `whereIn` to lookup (enh. 1); catch `InvalidRequestStatusException` separately from `\Throwable` |
| 3 | `resources/views/customer/publishing/index.blade.php:153-154` | Uncomment button, gate on status, drop `(temp)` label |
| 4 | `resources/views/customer/publishing/index.blade.php:57-58` | Rewrite modal copy — currently says *"This feature is only temporary and can be changed later"* |
| 5 | `resources/views/customer/releases/index.blade.php:409` | **Enh. 2** — fix missing closing quote on `onclick` |
| 6 | `app/Services/Publishing/PublishingService.php:184` | **Enh. 3** — remove null-status short-circuit in `cancel()` |

### 1. Service guard

```php
public function delete(Publishing $publishing)
{
    // Check status
    if (!in_array($publishing->status, ['draft', 'rejected'])) {
        throw new InvalidRequestStatusException($publishing->status, ['draft', 'rejected']);
    }

    DB::transaction(function () use ($publishing) {
        // Signees will be automatically deleted by CASCADE FK constraint
        // Documents (statement/nuon) are preserved as archival records
        $publishing->delete();
    });
}
```

Deliberate deviation: `cancel()` at `:184` writes
`if ($publishing->status && $publishing->status !== 'pending')`. The leading
`$publishing->status &&` means a **null status short-circuits past the guard**.
Do not copy that pattern here — a null-status row would otherwise be deletable at
any point in its life. Enhancement 3 fixes it at the source.

### 2. Controller

```php
$publishing = Publishing::where('profileid', current_user()->profile->id)
    ->whereIn('status', ['draft', 'rejected'])   // enh. 1 — defense in depth
    ->findOrFail($id);
```

Split the catch so an expected guard rejection does not log at `Log::error` with a
full stack trace and pollute the `logs` table:

```php
} catch (InvalidRequestStatusException $th) {
    return redirect()->back()->withErrors($th->getMessage());
} catch (\Throwable $th) {
    // ...existing error logging...
}
```

Dead code to drop while in there: lines 177-180 test `$result instanceof Model`, but
`delete()` returns `void` — the branch can never fire. The same dead pattern exists in
`ReleasesController::destroy` (not in scope).

### 3. Frontend

```js
let actions = `<a href="${url}" class="btn btn-outline-primary btn-sm">${btn}</a>`;
if (row.status == 'draft' || row.status == 'rejected') {
    actions += `<a href="javascript:void(0)" class="btn btn-outline-danger btn-sm ms-2" `
             + `data-url="${deleteUrl}" onclick="deletePublishing(event)">Delete</a>`;
}
return actions;
```

`href="javascript:void(0)"` rather than `href="#"` — the release version uses `#` with
no `preventDefault()`, which scroll-jumps to top before the modal opens.

**Do not** copy `releases/index.blade.php:409` as a template; its `onclick` is missing
a closing quote, so the HTML parser swallows `>Delete</a>` into the attribute value.
That is why the release Rejected tab's delete button is currently unusable. Enhancement 2
fixes it. The working reference is `:500` (Draft tab).

Status gating is client-side here (single DataTable, unlike releases' four tabs) —
cosmetic only. The service guard is the real enforcement.

### API shape

| | |
|---|---|
| **Endpoint** | `DELETE /publishing/{id}` — unchanged, `customer.php:121` |
| **Auth** | `auth`, `verified`, `can:customer`, `CustomerVerified` + inline `profileid` ownership |
| **Request** | `_token`, `_method=DELETE`. No body |
| **200/302 OK** | `redirect()->route('publishing.index')` + flash `success` |
| **404** | ID not found, not owned by the caller, or status not in `draft, rejected` |
| **302 + errors** | Status guard tripped at the service layer (defense-in-depth path) |

---

## Affected use cases

```
AFFECTED USE CASES (consolidated):
- MODIFIED: customer delete publishing request (unguarded -> restricted to draft|rejected,
            button surfaced in UI for the first time)
- MODIFIED: customer view publishing list (row actions now status-dependent)
- MODIFIED: customer cancel publishing request (enh. 3 — null-status guard hardening)
- MODIFIED: customer delete release request (enh. 2 — Rejected tab button repaired)
- NEW:      (none)
- REMOVED:  (none)
```

---

## Enhancements

### Included

| # | Enhancement | Rationale |
|---|---|---|
| 1 | `whereIn('status', ['draft','rejected'])` in the controller lookup | Defense in depth — wrong status 404s instead of reaching the service. Exact release parity |
| 2 | Fix broken release Rejected-tab delete button (`releases/index.blade.php:409`) | Delete is currently unreachable from that tab. One missing `"` |
| 3 | Fix null-status short-circuit in `PublishingService::cancel():184` | Same guard bug class as the one being fixed here; leaving it is inconsistent |

### Deferred

| # | Enhancement | Note |
|---|---|---|
| 4 | Implement `PublishingPolicy::delete()` for ownership + `@can` the button | Retires dead code hardcoding `false` |
| 5 | Feature test: 7 statuses x owner/non-owner | No existing test covers `destroy` |
| 6 | Remove dead `$result instanceof Model` branches | Both controllers |
| 7 | Delete unrouted `publishing.update` route (`customer.php:120`) | Currently 500s on `BadMethodCallException` — no `update()` method exists. Unrelated but adjacent |
