# Plan — Songwriter Role Label Uniformity

**Date:** 2026-07-23
**Brainstorm:** `docs/brainstorms/2026-07-23-songwriter-role-label-uniformity.md`
**Status:** Plan ready — awaiting `/gate`

---

## Use Case Impact

```
AFFECTED USE CASES:
- MODIFIED (display text only):
    - release single create/edit (songwriter role select)
    - release album create/edit (songwriter role select)
    - publishing request create (customer — split-sheet role select)
    - publishing request create/edit (admin — role select)
    - publishing signee / guest-sign display (role name accessor)
    - release detail view (songwriter role display)
- NEW:
    - App\Enums\SongwriterRole (single source of truth)
    - resources/lang/{en,id}/songwriter.php
- REMOVED: (none)
- DEFERRED (intentional, not in this plan):
    - contract PDF role labels (app/Services/ContractService.php:267-271)
```

> No `docs/use-cases.md` registry exists in this repo — use cases above are enumerated from the
> affected surfaces directly.

---

## Structure Check

No monolithic-component or business-logic-in-controller problems here. The **only** structural smell
is the exact thing this plan fixes: the role-label list is **copy-pasted into 7 places** and has already
drifted (two styles, muddled parentheticals). The fix is centralization (a backed enum + lang), which
is the core work — no separate restructuring phase is required.

### Canonical labels (values unchanged)

| Value | en | id |
|---|---|---|
| `author` | Lyricist (author) | Penulis Lirik (author) |
| `composer` | Composer (melody) | Komposer (melodi) |
| `author_composer` | Lyricist & Composer | Penulis Lirik & Komposer |

Stored values, DB enum, `TrackRequest`/`StorePublishingRequest` validation (`in:author,composer,author_composer`),
and existing tests are **untouched**. Display text only.

---

## Phase 1 — Localization source (lang files)

### Task 1.1 — Create `resources/lang/en/songwriter.php`

**Pattern:** Laravel localization array file (mirrors existing `resources/lang/en/validation.php`).

```php
<?php

return [
    'roles' => [
        'author'          => 'Lyricist (author)',
        'composer'        => 'Composer (melody)',
        'author_composer' => 'Lyricist & Composer',
    ],
];
```

### Task 1.2 — Create `resources/lang/id/songwriter.php`

```php
<?php

return [
    'roles' => [
        'author'          => 'Penulis Lirik (author)',
        'composer'        => 'Komposer (melodi)',
        'author_composer' => 'Penulis Lirik & Komposer',
    ],
];
```

**Verify:**
`php artisan tinker --execute="echo __('songwriter.roles.composer');"` → `Composer (melody)`

---

## Phase 2 — The enum (single source of truth)

### Task 2.1 — Create `app/Enums/SongwriterRole.php`

**Pattern:** PHP 8.2 backed enum (Laravel 11). `label()` reads from lang; `options()` returns
`value => label` for `<select>` loops.

```php
<?php

namespace App\Enums;

enum SongwriterRole: string
{
    case Author = 'author';
    case Composer = 'composer';
    case AuthorComposer = 'author_composer';

    public function label(): string
    {
        return __('songwriter.roles.' . $this->value);
    }

    /**
     * value => label, for rendering <select> option lists.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }
        return $options;
    }
}
```

**Verify:**
`php artisan tinker --execute="print_r(App\Enums\SongwriterRole::options());"`
→ `['author' => 'Lyricist (author)', 'composer' => 'Composer (melody)', 'author_composer' => 'Lyricist & Composer']`

---

## Phase 3 — Wire the 6 touch-points to the enum

Each task touches ONE file. All replace hardcoded label strings with the enum.

### Task 3.1 — `app/Models/Publishing/Signee.php:91-100` (accessor)

Replace the inline `$roles` map. Use `tryFrom` + fallback for defensiveness (DB enum guarantees
validity, but a stray value must not fatal a display page).

```php
public function getRoleNameAttribute(): string
{
    return \App\Enums\SongwriterRole::tryFrom($this->role)?->label() ?? $this->role;
}
```

**Verify:** guest-sign page & `customer/publishing/show` render "Lyricist (author)" etc. for a signee.

### Task 3.2 — `resources/views/components/release.blade.php:31-35` + `:354` (fixes #G1)

Two edits in this file:

1. **Delete** the inline `$roleMap` array block (lines 31-35) entirely — it is no longer needed.
2. **Harden the consumer** at line 354. The current `{{ $roleMap[$songwriter['role']] }}` is an
   unguarded key lookup: an empty (`''`, offered as `-- Role --` in the forms) or legacy/imported role
   throws an *Undefined array key* warning and renders `()`. Render via the enum with a fallback:

```blade
{{-- was: ({{ $roleMap[$songwriter['role']] }}) --}}
({{ \App\Enums\SongwriterRole::tryFrom($songwriter['role'] ?? '')?->label() ?? '—' }})
```

**Verify:** release detail view shows the new labels; a track songwriter with an empty/unknown role
renders `(—)` instead of a PHP warning.

### Task 3.3 — `resources/views/admin/publishing/create.blade.php:174-178` (select)

Replace the three hardcoded `<option>`s with a loop:

```blade
<select class="form-select" x-model="songwriter.role">
    @foreach (\App\Enums\SongwriterRole::options() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

### Task 3.4 — `resources/views/customer/publishing/create.blade.php:339-344` (select)

Keep the surrounding `:name`/Alpine bindings; replace the inner options with the same loop, preserving
the leading blank/placeholder option if present:

```blade
<select class="form-select" :name="`songwriters[${idx}][role]`" x-model="entry.role">
    @foreach (\App\Enums\SongwriterRole::options() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

### Task 3.5 — `resources/views/partials/single-step1-fields.blade.php:337-341` (select)

```blade
<select class="form-select form-select-sm" x-model="writer.role">
    <option value="">-- Role --</option>
    @foreach (\App\Enums\SongwriterRole::options() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

### Task 3.6 — `resources/views/partials/album-track-form-fields.blade.php:264-269` (select)

Identical replacement to 3.5 (this partial mirrors the single form).

**Verify (3.3–3.6):**
`php artisan view:clear` then load each form — every songwriter role dropdown shows exactly:
`Lyricist (author)` · `Composer (melody)` · `Lyricist & Composer`.

---

## Phase 4 — Deferred (explicitly NOT changed)

### `app/Services/ContractService.php:267-271` — leave as-is

The contract PDF `$role` map keeps `Author` / `Composer` / `Author & Composer` until a separate
legal/product sign-off. **Known, accepted consequence:** after this plan, generated contract PDFs will
show the *old* short labels while the rest of the app shows the new canonical ones. Tracked in the
brainstorm "Deferred" section. Do not wire this file to the enum in this plan.

---

## Phase 5 — Testing & Verification

### Task 5.1 — Regression: existing suite still green

Values and validation are unchanged, so `PublishingTest` / `ReleaseOptionsTest` must stay green.

`php artisan test --filter=Publishing`
`php artisan test --filter=ReleaseOptions`
→ expected: no failures.

### Task 5.2 — Enum smoke test (lightweight)

`php artisan tinker --execute="echo App\Enums\SongwriterRole::from('author')->label();"`
→ `Lyricist (author)`

### Task 5.3 — Manual view smoke

`php artisan view:clear` then load: release single create, release album create, customer publishing
create (step 3), admin publishing create, a publishing show, a release detail view. Confirm identical
labels across all six.

> A dedicated label-assertion feature test (brainstorm Enhancement 3) is **deferred** per decision —
> not created in this plan.

### Accepted risk — #U1 (from `/gate`)

No automated regression pins the label text across the 6 surfaces, so the drift this plan fixes could
silently recur. **Accepted as risk** per decision (2026-07-23). Verification relies on Tasks 5.1–5.3
(existing suite + enum smoke + manual view smoke). Revisit alongside brainstorm Enhancement 3.

---

## Acceptance Criteria

- [ ] `App\Enums\SongwriterRole` exists with `label()` + `options()`, reading from lang.
- [ ] `resources/lang/en/songwriter.php` and `resources/lang/id/songwriter.php` exist with the 3 roles.
- [ ] All 6 in-scope surfaces (3.1–3.6) render exactly `Lyricist (author)` / `Composer (melody)` / `Lyricist & Composer`.
- [ ] No hardcoded songwriter role label strings remain in the 6 in-scope files (grep clean).
- [ ] #G1 fixed: `release.blade.php:354` uses the enum with a fallback; empty/unknown role renders `(—)`, no PHP warning.
- [ ] Stored values, DB enum, and validation rules are unchanged; `Publishing` + `ReleaseOptions` tests pass.
- [ ] `ContractService.php` is untouched (deferred), and this is noted as a known temporary divergence.

---

> Plan ready. Run `/gate` to review before coding.
