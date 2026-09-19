# Implementation Plan — Delete Draft/Rejected Publishing Request

**Date:** 2026-07-20
**Brainstorm:** `docs/brainstorms/2026-07-20-publishing-request-delete.md`
**Chosen option:** A (service-layer status guard) + enhancements 1, 2, 3

---

## Use Case Impact

```
AFFECTED USE CASES:
- MODIFIED: customer delete publishing request (currently unguarded -> restricted to
            draft|rejected; button surfaced in UI for the first time)
- MODIFIED: customer view publishing list (row actions now status-dependent)
- MODIFIED: customer cancel publishing request (enh. 3 — null-status guard hardening)
- MODIFIED: customer delete release request (enh. 2 — Rejected tab button repaired)
- NEW:      (none)
- REMOVED:  (none)
```

`docs/use-cases.md` does not exist in this repo. The list above is derived from route
and controller analysis, not from a registry.

---

## Structure Check

**No Phase 1 restructuring required.** The code in the change path is properly layered:

- Routes -> thin controller -> service -> model
- Business logic lives in `PublishingService`, not the controller
- No `DB::` calls in `PublishingController`
- No HTTP concerns (`request()`, `response()`) inside `PublishingService::delete()`

Structural debt exists nearby but is **out of scope** — restructuring it as part of a
6-line guard fix would be scope creep. Recorded for a future ticket:

| Debt | Location |
|---|---|
| 583-line blade partial | `resources/views/components/publishing.blade.php` |
| 627-line create form | `resources/views/customer/publishing/create.blade.php` |
| `debug_backtrace()` caller-name guard (fragile; silently no-ops in console) | `app/Models/Publishing/Signee.php:38-61` |
| Repeated try/catch/Log boilerplate across 9 controller methods | `app/Http/Controllers/PublishingController.php` |
| Dead route `publishing.update` — 500s, no `update()` method exists | `routes/web/customer.php:120` |
| Dead admin routes `publishing.edit` / `publishing.update` | `routes/web/admin.php:241-242` |

---

## Phase 1 — Restructure

**Not applicable.** See Structure Check above.

## Phase 2 — Database & Model Layer

**No migration.** No schema change, no relationship change. One model change (Task 2.1).

Existing schema already supports this feature correctly:

- `publishing_signees.publishingid` -> `publishings` **CASCADE**
  (`2025_11_21_151534_fix_publishing_foreign_key_constraints.php:24-27`)
- `publishing_documents.publishingid` -> **SET NULL** (deliberate archival preservation,
  `2025_11_21_151357_create_publishing_documents_table.php:16-23`)
- `contracts` polymorphic, no FK — intentionally never deleted (`Publishing.php:54-57`)
- `publishings.release_id` / `track_id` -> `nullOnDelete()`; these are the FK's own side,
  so parent Release/Track rows are unaffected by deleting a publishing

Because there is no migration, there is **no rollback migration** either. Rollback for
this change is a git revert of the modified files.

### Task 2.1 — Delete signee signature files on publishing deletion (gate #I8)

**Pattern:** Model lifecycle hook cleaning up non-FK-tracked resources before a cascade.

**The bug being fixed:** `publishing_signees.publishingid` CASCADEs at the **database**
level. A DB-level cascade deletes signee rows **without firing any Eloquent model events**
— so `Signee`'s `deleting` hook never runs, and the `signature()` `morphOne File` is never
cleaned up. `filestorage` has no FK to its polymorphic owner, and `Publishing::booted()`'s
`deleting` hook is currently an **empty comment block** (`Publishing.php:49-58`). Net
result today: **signature image files orphan on disk on every publishing deletion.**

**File:** `app/Models/Publishing/Publishing.php` (replace the empty hook body at lines 49-58)

```php
    protected static function booted()
    {
        static::deleting(function ($publishing) {
            // Delete signee signature files BEFORE the FK cascade removes the signee rows.
            // The cascade deletes signee rows at the DB level and fires no Eloquent events,
            // so the morphOne signature File would otherwise orphan on disk.
            // File::delete() removes both the disk file and the filestorage row.
            foreach ($publishing->signees as $signee) {
                if ($signee->signature) {
                    $signee->signature->delete();
                }
            }

            // Signee ROWS: deleted via CASCADE FK constraint (no action needed here)
            // Documents (statement/nuon): preserved via SET NULL — archival records
            // Contracts: intentionally NOT deleted (historical/legal)
        });
    }
```

Why this and not a `Signee` deleting hook:
- The `Signee` `deleting` hook (`Signee.php:38-61`) never fires under DB cascade anyway,
  and it actively **blocks** direct deletion via a fragile `debug_backtrace()` caller-name
  scan. Routing signature cleanup through the parent's hook sidesteps that guard entirely —
  we delete the *File*, never the *Signee* (the DB handles the signee row).
- This hook fires for **any** `Publishing::delete()`, so the fix also covers the
  console/job deletion paths, not just the customer HTTP route.

**Known limitation (pre-existing, not fixed here):** `File::delete()` (`File.php:43-62`)
is a no-op for `disk == 'google'` and its backup-deletion block is empty. Signatures are
stored on `local` disk (`FileService.php:156` hardcodes it), so local files are removed
correctly. Google-disk / backup orphaning is out of scope — logged in the Structure Check
debt table.

**Verification:**
```bash
php -l app/Models/Publishing/Publishing.php
# plus the Task 5.3 disk spot-check confirms the signature file is gone after delete
```

---

## Phase 3 — Backend Logic

### Task 3.1 — Add status guard to `PublishingService::delete()`

**Pattern:** Service-layer state-machine guard. Matches the module's own convention —
`publish():98`, `cancel():184`, `adminApprove():360`, `adminReject():416` all guard the
same way with `InvalidRequestStatusException`.

**File:** `app/Services/Publishing/PublishingService.php` (replace lines 212-219)

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

Two deliberate changes from the current signature:

1. **Type-hint added** — currently `delete($publishing)` is untyped, unlike every
   sibling method. Type-hinting makes the guard's assumption explicit.
2. **No `$publishing->status &&` short-circuit.** `cancel():184` writes
   `if ($publishing->status && $publishing->status !== 'pending')`. That leading truthy
   check means a **null status silently passes the guard**. Do not replicate it here.
   Task 3.3 fixes it at the source.

`InvalidRequestStatusException` is already imported in this file — verify the `use`
statement is present before editing.

**Verification:**
```bash
php -l app/Services/Publishing/PublishingService.php
# Expected: No syntax errors detected
```

---

### Task 3.2 — Harden the controller lookup and split the catch (enhancement 1)

**Pattern:** Defense in depth — controller enforces ownership + status at the query
boundary (IDOR protection, returns 404); service enforces status for all other callers.
Mirrors `ReleasesController.php:92-93`.

**File:** `app/Http/Controllers/PublishingController.php`

**3.2a — Add the imports** (after line 15, with the other `App\` imports):

```php
use App\Exceptions\Publishing\InvalidRequestStatusException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
```

`InvalidRequestStatusException` is **not currently present** in the file. Without it the
new catch block silently never matches, because PHP resolves the bare class name to
`App\Http\Controllers\InvalidRequestStatusException`. `ModelNotFoundException` is needed
for the #O9 IDOR-attempt logging below.

**3.2b — Replace `destroy()` (lines 165-197):**

```php
    public function destroy($id)
    {
        // Look up scoped to owner AND deletable status. A wrong status or another
        // user's record both 404 here (IDOR protection). We catch the 404 to log the
        // attempt (gate #O9) before re-throwing so the framework returns its 404.
        try {
            $publishing = Publishing::where('profileid', current_user()->profile->id)
                ->whereIn('status', ['draft', 'rejected'])
                ->findOrFail($id);
        } catch (ModelNotFoundException $th) {
            Log::warning('Blocked publishing delete attempt (not found / not owned / wrong status).', [
                'requested_id' => $id,
                'user_id' => current_user()->id,
                'profile_id' => current_user()->profile->id,
            ]);
            throw $th;
        }

        // Enriched context (gate #I16): captured BEFORE deletion so the audit record
        // survives the hard delete + signee CASCADE. Reconstruction is impossible after.
        $context = [
            'model_id' => $publishing->id,
            'model_type' => get_class($publishing),
            'title' => $publishing->title,
            'status' => $publishing->status,
            'signee_count' => $publishing->signees()->count(),
            'signee_emails' => $publishing->signees()->pluck('email')->all(),
        ];

        try {
            $this->service->delete($publishing);

            // Log success
            Log::info('Publishing request deleted.', $context);

            return redirect()->route('publishing.index')->with('success', 'Publishing request deleted.');
        } catch (InvalidRequestStatusException $th) {

            // Expected guard rejection. This is the service-layer defense-in-depth path;
            // reaching it means a request slipped past the controller's whereIn (e.g. a
            // status race). Warn-level, no stack trace — a signal without log-table noise.
            $context['message'] = $th->getMessage();
            Log::warning('Blocked publishing delete: invalid status at service guard.', $context);

            return redirect()->back()->withErrors($th->getMessage());
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to delete publishing request.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }
```

Changes:
- `whereIn` added to the lookup (enhancement 1)
- **#O9** — the `whereIn` means most bad attempts now 404 at the lookup and never reach
  the catch. Wrapping the lookup in a `ModelNotFoundException` catch restores that lost
  security signal (attempted ID + user) at `warning` level, then re-throws so the 404
  response is unchanged. The service-guard rejection also logs at `warning` now instead
  of vanishing silently.
- **#I16** — `$context` is enriched with `title`, `status`, `signee_count`, and
  `signee_emails`, captured before the delete. `DatabaseLogger` already JSON-serialises
  context, so no schema change. **Compliance note:** `signee_emails` is itself PII —
  see `docs/deferred-items.md` DI-1; whether to hash/omit it is an open compliance
  question, deliberately left as-is for now.
- Dead code removed: the old lines 176-180 tested `$result instanceof Model`, but
  `delete()` returns `void`, so that branch could never fire.

**Verification:**
```bash
php -l app/Http/Controllers/PublishingController.php
php artisan route:list --path=publishing
# Expected: DELETE publishing/{id} ... publishing.destroy listed unchanged
```

---

### Task 3.3 — Fix null-status short-circuit in `cancel()` (enhancement 3)

**Pattern:** Same state-machine guard, corrected. Fail-closed rather than fail-open on
an unexpected null.

**File:** `app/Services/Publishing/PublishingService.php:184`

```php
// Before
if ($publishing->status && $publishing->status !== 'pending') {

// After
if ($publishing->status !== 'pending') {
```

Rationale: `publishings.status` was made nullable by
`2025_09_17_110106_edit_publishings_table.php:14-24`. A null-status row currently slips
past the cancel guard entirely. Same bug class as the one Task 3.1 fixes; leaving it
inconsistent is worse than fixing both.

**Blast radius:** `cancel()` is called from `PublishingController::cancel():140`, routed
at `customer.php:119`. Behavior only changes for rows where `status IS NULL` — which
should be zero rows given the `draft` column default. Confirm before shipping:

```bash
php artisan tinker --execute="echo \App\Models\Publishing\Publishing::whereNull('status')->count();"
# Expected: 0
```

If that returns non-zero, stop and investigate — those rows are in an undefined state and
need triage before this guard is tightened.

**Verification:**
```bash
php -l app/Services/Publishing/PublishingService.php
```

**Routes:** No change. `DELETE publishing/{id}` already exists at `customer.php:121`.
**FormRequests:** No change. The request body is empty — only `_token` and `_method`.

---

## Phase 4 — Frontend

### Task 4.1 — Surface the delete button, gated by status

**Pattern:** Status-conditional row action, matching the existing `draft -> Edit`
conditional immediately above it.

**File:** `resources/views/customer/publishing/index.blade.php` (replace lines 152-154)

```js
                            let deleteUrl = '{{ route('publishing.destroy', ':id') }}'.replace(':id', row.id);
                            let actions = `<a href="${url}" class="btn btn-outline-primary btn-sm">${btn}</a>`;
                            if (row.status == 'draft' || row.status == 'rejected') {
                                actions += `<a href="javascript:void(0)" class="btn btn-outline-danger btn-sm ms-2" `
                                         + `data-url="${deleteUrl}" onclick="deletePublishing(event)">Delete</a>`;
                            }
                            return actions;
```

Notes:
- `href="javascript:void(0)"` rather than `href="#"`. The release version uses `#` with
  no `preventDefault()`, which scroll-jumps the page to top before the modal opens.
- The `(temp)` label is dropped.
- `deletePublishing(event)` at line 80 already does the right thing — it reads
  `data-url`, sets the form action, and shows the modal. **No JS change needed.**
- Status gating here is cosmetic only; Tasks 3.1 and 3.2 are the real enforcement.

**Verification:** Log in as a customer with at least one `draft` and one `completed`
publishing request. The Delete button appears on the draft row and is absent from the
completed row.

---

### Task 4.2 — Rewrite the confirmation modal copy

**File:** `resources/views/customer/publishing/index.blade.php` (replace lines 56-59)

```html
                <div class="modal-body">
                    Are you sure you want to delete this publishing request? This action cannot
                    be undone, and all songwriter entries on this request will be removed.
                </div>
```

The current copy reads *"This feature is only temporary and can be changed later"* —
leftover dev text that must not ship. The replacement states the two things a user
needs to know before confirming: it is permanent, and it takes the signees with it.

**Verification:** Open the modal in the browser; confirm the new copy renders and the
Delete Publishing button still submits to the correct route.

**Error rendering (gate #G7) — no change needed.** The shared layout
`resources/views/layouts/app.blade.php:33-49` already renders `$errors->any()` as a
dismissible `alert-danger` and `session('success')` as `alert-success`, globally, above
`@yield('content')`. `customer/publishing/index.blade.php` extends `layouts.app`, so a
blocked delete's `redirect()->back()->withErrors(...)` (Task 3.2) already surfaces to the
user, and the success flash from a good delete already renders on the index. Confirm by
triggering one blocked delete and checking the red banner appears — but no blade edit is
required.

---

### Task 4.3 — Repair the release Rejected-tab delete button (enhancement 2)

**File:** `resources/views/customer/releases/index.blade.php:409`

```html
<!-- Before — missing closing quote on the onclick attribute -->
<a href="#" class="btn btn-sm btn-outline-danger" onclick="deleteRelease(${row.id})>Delete</a></div>`;

<!-- After -->
<a href="javascript:void(0)" class="btn btn-sm btn-outline-danger" onclick="deleteRelease(${row.id})">Delete</a></div>`;
```

The missing `"` causes the HTML parser to swallow `>Delete</a></div>` into the attribute
value, so the button renders with no label and no working handler. Release deletion has
been **unreachable from the Rejected tab**. The working reference is line 500 (Draft tab).

**Verification:** Log in as a customer with a `rejected` release. The Rejected tab shows a
labelled Delete button; clicking it opens the confirmation modal and deletion succeeds.

---

## Phase 5 — Testing and Verification

Per gate #U5, the guard and the enhancement-3 behavioral change now get automated
coverage, run against a dedicated MySQL test database.

### Task 5.0 — Configure MySQL test database (gate #U5)

**Pattern:** Isolated, disposable test schema on the same engine as production +
`RefreshDatabase`. Using MySQL (not SQLite) means the test suite runs the exact same
migration set as production — no `enum()`/`->change()`/ALTER-TABLE dialect gaps.

**Prerequisite:** create an **empty, dedicated** MySQL database for tests, separate from
the dev/prod database. `RefreshDatabase` migrates and truncates this schema on every run —
pointing it at a real database would wipe it.

```sql
CREATE DATABASE `<DB_DATABASE>` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**File:** create `.env.testing` at project root. **Values are placeholders — the user will
supply the real ones. Do not invent credentials.**

```dotenv
APP_ENV=testing
APP_KEY=
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=<DB_HOST>            # e.g. 127.0.0.1
DB_PORT=<DB_PORT>            # e.g. 3306
DB_DATABASE=<DB_DATABASE>    # dedicated TEST schema — NOT the dev/prod db
DB_USERNAME=<DB_USERNAME>
DB_PASSWORD=<DB_PASSWORD>

CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array
BCRYPT_ROUNDS=4
```

Generate the app key into this file specifically (fills the empty `APP_KEY`):
```bash
php artisan key:generate --env=testing
```

`phpunit.xml` currently has the SQLite lines commented out (lines 25-26) — **leave them
commented / remove them.** Do not set `DB_CONNECTION`/`DB_DATABASE` as `<php><env>` entries
in `phpunit.xml`, because those override `.env.testing` and would force every developer to
the same host/schema. Connection config lives solely in `.env.testing`.

**Safety check before first run** — confirm the test connection is NOT the dev database:
```bash
php artisan tinker --env=testing --execute="echo config('database.connections.mysql.database');"
# Expected: the dedicated test schema name, never the dev/prod db
```

**Verification:**
```bash
php artisan migrate:fresh --env=testing
php artisan test --filter=PublishingTest
# Expected: migrations apply cleanly (same engine as prod); existing cases still pass
```

### Task 5.1 — Regression test for the status guard

**File:** `tests/Feature/PublishingTest.php` (append inside the existing class; it already
uses `RefreshDatabase` and provides a `createProfile(User $user)` helper)

```php
    public function test_completed_publishing_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['email' => 'guard@example.com']);
        $profile = $this->createProfile($user);

        $publishing = new Publishing(['title' => 'Guarded Track']);
        $publishing->profileid = $profile->id;
        $publishing->status = 'completed';
        $publishing->save();

        $this->actingAs($user)
            ->delete(route('publishing.destroy', $publishing->id))
            ->assertNotFound();

        $this->assertDatabaseHas('publishings', ['id' => $publishing->id]);
    }

    public function test_draft_publishing_can_be_deleted_by_owner(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $profile = $this->createProfile($user);

        $publishing = new Publishing(['title' => 'Draft Track']);
        $publishing->profileid = $profile->id;
        $publishing->status = 'draft';
        $publishing->save();

        $this->actingAs($user)
            ->delete(route('publishing.destroy', $publishing->id))
            ->assertRedirect(route('publishing.index'));

        $this->assertDatabaseMissing('publishings', ['id' => $publishing->id]);
    }

    public function test_publishing_cannot_be_deleted_by_non_owner(): void
    {
        $owner = User::factory()->create(['email' => 'a@example.com']);
        $ownerProfile = $this->createProfile($owner);

        $attacker = User::factory()->create(['email' => 'b@example.com']);
        $this->createProfile($attacker);

        $publishing = new Publishing(['title' => 'Someone Elses Draft']);
        $publishing->profileid = $ownerProfile->id;
        $publishing->status = 'draft';
        $publishing->save();

        $this->actingAs($attacker)
            ->delete(route('publishing.destroy', $publishing->id))
            ->assertNotFound();

        $this->assertDatabaseHas('publishings', ['id' => $publishing->id]);
    }
```

**Caveat to resolve during implementation:** these tests hit the route, which runs the
`can:customer` and `CustomerVerified` middleware. `CustomerVerified` requires
`profile->status == 'verified'` (`app/Http/Middleware/CustomerVerified.php:19-32`), and
`createProfile()` does not set a status. Either set `$profile->status = 'verified'` in
the test bodies, or call `PublishingService::delete()` directly and assert the exception
instead of going through HTTP. Confirm which by running the tests — do not assume.

### Task 5.1b — Guard tests for `delete()` and `cancel()` at the service layer (gate #U5)

Enhancement 3 changes `cancel()` guard semantics (null status now fails closed). That is a
behavioral change to a production path and must not ship untested. Calling the service
directly also sidesteps the middleware caveat above.

**File:** `tests/Feature/PublishingTest.php` (append to the class)

```php
    public function test_delete_service_rejects_non_deletable_status(): void
    {
        $service = app(PublishingService::class);

        $publishing = new Publishing(['title' => 'Completed']);
        $publishing->status = 'completed';
        $publishing->profileid = 1;
        $publishing->save();

        $this->expectException(\App\Exceptions\Publishing\InvalidRequestStatusException::class);
        $service->delete($publishing);
    }

    public function test_cancel_service_rejects_null_status(): void
    {
        // Enhancement 3: a null status must fail closed, not slip past the guard.
        $service = app(PublishingService::class);

        $publishing = new Publishing(['title' => 'Null Status']);
        $publishing->status = null;
        $publishing->profileid = 1;
        $publishing->save();

        $this->expectException(\App\Exceptions\Publishing\InvalidRequestStatusException::class);
        $service->cancel($publishing);
    }
```

Note: `cancel()` throws `InvalidRequestStatusException` (see `PublishingService.php:185`),
so both tests assert the same exception type. **Correction discovered during
implementation:** `publishings.status` is **NOT NULL** in the current schema — the
`2025_09_17_110106` nullable change was reverted by `2025_10_01_163656`, which redefines
the enum with `default('draft')` and no `nullable()`. So a null-status row is unreachable
via the DB, and the earlier plan claim ("nullable on MySQL") was wrong. The `cancel()`
null test therefore uses an **unsaved** `new Publishing()` instance — the guard runs
before any persistence, so it exercises enhancement 3 directly without needing to insert.

**Enhancement 2 (release Rejected-tab button)** is blade-only and cannot be asserted in
this test stack. It is **manual-verification-only** — covered by Task 4.3's verification
step. Stated here so its lack of automated coverage is intentional, not an omission.

**Verification:**
```bash
php artisan test --filter=PublishingTest
# Expected: all tests pass, including the 3 new ones
```

### Task 5.2 — Manual verification matrix

| Status | Delete button visible? | `DELETE` via curl/Postman | Expected |
|---|---|---|---|
| `draft` | Yes | allowed | Row gone, signees gone, redirect + success flash |
| `rejected` | Yes | allowed | Row gone, signees gone, redirect + success flash |
| `pending` | No | blocked | 404 |
| `review` | No | blocked | 404 |
| `completed` | No | blocked | 404 |
| `cancelled` | No | blocked | 404 |
| `expired` | No | blocked | 404 |
| any, other user's record | N/A | blocked | 404 |

Direct-request rows matter more than the button rows — the button is cosmetic gating.
Test the blocked cases by hand-crafting the `DELETE` with a valid CSRF token.

### Task 5.3 — Post-delete integrity spot-check

After deleting one `rejected` request that had signees and an uploaded document:

Note the signee's signature file path first (before deleting), so you can check the disk:
```bash
php artisan tinker
>>> $p = \App\Models\Publishing\Publishing::find(<id>);
>>> $sigPaths = $p->signees->map(fn($s) => optional($s->signature)->identifier)->filter()->all();
>>> $sigPaths;  // note these paths, then perform the delete via the UI, then re-check
```

After deleting one `rejected` request that had signees (with signatures) and a document:

```bash
php artisan tinker
>>> \App\Models\Publishing\Signee::where('publishingid', <id>)->count();      // expect 0 (CASCADE)
>>> \App\Models\Publishing\Document::where('publishingid', <id>)->count();    // expect 0 (SET NULL)
>>> \App\Models\Publishing\Document::whereNull('publishingid')->count();      // expect >=1 (archival row survives)
>>> \App\Models\Contract::where('owner_id', <id>)->where('owner_type','publishing')->count();  // expect unchanged
```

Then verify each noted signature path is gone from disk (gate #I8, Task 2.1):
```bash
# for each path in $sigPaths — expect "not found"
ls -la storage/app/<signature-identifier>
```

Confirms all four behaviors fired as designed: signee CASCADE, document SET NULL, contract
preservation, and signature-file cleanup via the new `Publishing` deleting hook.

---

## Acceptance Criteria

- [ ] A customer can delete a publishing request in `draft` status from the list page
- [ ] A customer can delete a publishing request in `rejected` status from the list page
- [ ] The Delete button is not rendered for `pending`, `review`, `completed`, `cancelled`, or `expired`
- [ ] A direct `DELETE /publishing/{id}` on a `completed` request returns 404 and the row survives
- [ ] A direct `DELETE /publishing/{id}` on another customer's request returns 404 and the row survives
- [ ] `PublishingService::delete()` throws `InvalidRequestStatusException` when called with any status outside `draft|rejected`
- [ ] Deleting a request removes its `publishing_signees` rows via CASCADE
- [ ] Deleting a request preserves its `publishing_documents` rows with `publishingid` set to NULL
- [ ] Deleting a request leaves any attached `contracts` row untouched
- [ ] Deleting a request removes its signees' signature files from disk (gate #I8, Task 2.1)
- [ ] A blocked delete attempt does not write an `error`-level row with a stack trace to the `logs` table
- [ ] A blocked delete attempt (wrong status / not owned) writes a `warning`-level log with the attempted id and user (gate #O9)
- [ ] A successful delete writes `Log::info('Publishing request deleted.')` with enriched context: `title`, `status`, `signee_count`, `signee_emails` (gate #I16)
- [ ] The confirmation modal no longer contains the text "This feature is only temporary"
- [ ] A blocked delete surfaces an error banner to the user via the shared layout (gate #G7 — no blade change, verify only)
- [ ] The release Rejected tab shows a labelled, working Delete button
- [ ] `.env.testing` exists (real values supplied by user) and the test suite runs against a dedicated MySQL test schema, not the dev/prod database (gate #U5)
- [ ] `php artisan test --filter=PublishingTest` passes, including the new `delete()` and `cancel()` guard tests
- [x] ~~`status IS NULL` pre-condition~~ — moot: `publishings.status` is NOT NULL in the current schema, so null rows cannot exist. Enhancement 3 now guards the in-memory case only.

---

## Files Changed

| File | Tasks |
|---|---|
| `app/Models/Publishing/Publishing.php` | 2.1 (signature-file cleanup hook) |
| `app/Services/Publishing/PublishingService.php` | 3.1, 3.3 |
| `app/Http/Controllers/PublishingController.php` | 3.2 |
| `resources/views/customer/publishing/index.blade.php` | 4.1, 4.2 |
| `resources/views/customer/releases/index.blade.php` | 4.3 |
| `.env.testing` (new, placeholder values — user supplies real) | 5.0 |
| `tests/Feature/PublishingTest.php` | 5.1, 5.1b |

No migration. No new route. No new policy, notification, or FormRequest.
`resources/views/layouts/app.blade.php` is **not** changed — gate #G7 (error rendering)
is already satisfied there.

## Accepted Risks (from /gate)

| # | Finding | Decision |
|---|---|---|
| #A5 | Status list `['draft','rejected']` duplicated across service, controller, blade | Accepted for now — no shared constant/scope introduced |
| #G4 | No `lockForUpdate()` on the status check (delete-vs-publish race) | Accepted — race conditions aren't handled anywhere in this codebase yet |
| #I12 | Hard delete, no soft-delete/undo/recovery path | Accepted (brainstorm decision) — recovery requires a DB restore |
| #O4 | No rate limiting on the DELETE endpoint | Accepted — scoped to caller's own records, low abuse potential |
| #G10 | `publishing.index` list unpaginated (pre-existing) | Accepted — out of scope, separate ticket |

## Deferred Items (documented)

| # | Finding | Where recorded |
|---|---|---|
| #I8 (policy) | PDP Law classification + retention position for signee PII; audit-log-PII question | `docs/deferred-items.md` DI-1 |
| Enh. 4 | Implement `PublishingPolicy::delete()` + `@can` the button | brainstorm doc, Enhancements → Deferred |
| Enh. 6 | Remove dead `$result instanceof Model` branch in `ReleasesController` | brainstorm doc |
| Enh. 7 | Delete dead `publishing.update` route | brainstorm doc |
