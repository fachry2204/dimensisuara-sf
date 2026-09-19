# Brainstorm — Songwriter Role Label Uniformity

**Date:** 2026-07-23
**Topic:** Crosscheck & standardize songwriter role option labels (author / composer / author & composer)
**Status:** Brainstorm complete — awaiting `/plan`

---

## Problem

The songwriter role **values** (`author`, `composer`, `author_composer`) are consistent everywhere
(validation rules, DB enum, tests all agree). The **labels shown to users are not** — two competing
styles exist, and the parentheticals are muddled ("Composer (Music/**Songwriter**)" uses the umbrella
term to gloss the music-only role; the lyrics role is called "Author", not the expected "Lyricist").

### Crosscheck (as-found)

| Where | `author` | `composer` | `author_composer` | Style |
|---|---|---|---|---|
| Release single form — `resources/views/partials/single-step1-fields.blade.php:338` | Author (Lyrics) | Composer (Music/Songwriter) | Author & Composer | Full |
| Release album form — `resources/views/partials/album-track-form-fields.blade.php:266` | Author (Lyrics) | Composer (Music/Songwriter) | Author & Composer | Full |
| Customer publishing form — `resources/views/customer/publishing/create.blade.php:341` | Author (Lyrics) | Composer (Music/Songwriter) | Author & Composer | Full |
| Signee accessor — `app/Models/Publishing/Signee.php:94` | Author (Lyrics) | Composer (Music/Songwriter) | Author & Composer | Full |
| Admin publishing form — `resources/views/admin/publishing/create.blade.php:175` | Author | Composer | Author & Composer | Short |
| Release detail display — `resources/views/components/release.blade.php:32` | Author | Composer | Author & Composer | Short |
| Contract PDF — `app/Services/ContractService.php:268` | Author | Composer | Author & Composer | Short |

---

## Decision

**Relabel to one canonical set, applied everywhere. Display text only — no data/logic change.**

| Value (unchanged) | New canonical label |
|---|---|
| `author` | **Lyricist (author)** |
| `composer` | **Composer (melody)** |
| `author_composer` | **Lyricist & Composer** |

Stored values, DB enum (`author`/`composer`/`author_composer`), validation rules, and tests all stay
exactly as-is. Zero data-migration risk.

---

## Scope (this round)

Apply the new labels to touch-points **1–6** via a centralized source of truth (see Enhancement 1).

- [ ] 1. `resources/views/partials/single-step1-fields.blade.php:338-340`
- [ ] 2. `resources/views/partials/album-track-form-fields.blade.php:266-268`
- [ ] 3. `resources/views/customer/publishing/create.blade.php:341-343`
- [ ] 4. `app/Models/Publishing/Signee.php:94-96` (accessor → guest-sign & publishing show pages)
- [ ] 5. `resources/views/admin/publishing/create.blade.php:175-177`
- [ ] 6. `resources/views/components/release.blade.php:32-34`

### Enhancement 1 — Centralize labels (ACCEPTED, excludes contract)

Root cause of the drift is copy-pasted option lists. Introduce a single source of truth:

- New backed string enum `App\Enums\SongwriterRole` with cases
  `Author = 'author'`, `Composer = 'composer'`, `AuthorComposer = 'author_composer'`.
- `label(): string` — returns the translated label (via Enhancement 2 lang keys).
- `options(): array` — `[value => label]` for blade `@foreach` in the select dropdowns.
- Touch-points 1–6 consume the enum instead of hardcoding `<option>`s / inline arrays.
- **Contract PDF (`ContractService.php`) is intentionally NOT wired to the enum this round** — see Deferred.

### Enhancement 2 — Lang files (ACCEPTED)

Move label strings into localization so id/en can diverge later.

- `resources/lang/en/songwriter.php` and `resources/lang/id/songwriter.php`.
- Proposed keys / values:

  | Key | en | id (proposed) |
  |---|---|---|
  | `role.author` | Lyricist (author) | Penulis Lirik (author) |
  | `role.composer` | Composer (melody) | Komposer (melodi) |
  | `role.author_composer` | Lyricist & Composer | Penulis Lirik & Komposer |

  (Indonesian wording to be confirmed at implementation.)

---

## Deferred (do NOT change now — revisit next time)

- **Contract PDF labels — `app/Services/ContractService.php:268-270`.**
  Deferred because it feeds the **legally-generated contract document**. Changing "Author" →
  "Lyricist (author)" etc. in signed contracts needs a deliberate legal/product sign-off.
  When picked up: apply the same canonical set, and consider whether the contract should read the
  enum too (Enhancement 1) or keep its own reviewed mapping.
- Enhancement 3 (label regression test) — not taken this round; optional later.

---

## Out of scope (different domains — not the publishing role)

- `resources/lang/id/validation.php:278` (`composer => Komposer`) — aggregator/track field attribute.
- Aggregator DDEX contributor `type: 'composer'` in aggregator create views.

---

## Affected use cases

```
MODIFIED (display only): release single create/edit, release album create/edit,
  publishing request create (customer), publishing request create/edit (admin),
  publishing signee / guest-sign display, release detail view
NEW: SongwriterRole enum (App\Enums), songwriter lang files (en + id)
DEFERRED: contract PDF label rendering
REMOVED: (none)
```

---

> Brainstorm complete. Run `/plan` when ready.
