# DB-level CASCADE bypasses Eloquent events → orphaned morph files

**Category:** data-integrity
**First hit:** 2026-07-20, publishing request delete feature

## Problem

`publishing_signees.publishingid` has an `onDelete('cascade')` FK to `publishings`.
When a `Publishing` is deleted, the signee rows vanish — good. But each signee has a
`morphOne(File::class, 'owner')` signature, and `filestorage` has **no FK** to its
polymorphic owner (a morph can't have one). The signature File rows — and the physical
files on disk — were being orphaned on every publishing deletion.

## Why it happens

A **database-level** FK CASCADE deletes child rows directly in the engine. It does **not**
load the Eloquent models, so **no model events fire** — `deleting`/`deleted` hooks on the
cascaded model (`Signee`) never run. Any cleanup wired into those hooks is silently skipped.

This is a general trap: relying on a model's `deleting` hook to clean up related non-FK
resources is unsafe if that model is itself removed via DB cascade rather than
`$model->delete()`.

## Fix

Do the cleanup in the **parent's** `deleting` hook, which *does* fire (the parent is
deleted via Eloquent), and iterate to the grandchildren's files there — before the cascade
removes the child rows:

```php
// app/Models/Publishing/Publishing.php
protected static function booted()
{
    static::deleting(function ($publishing) {
        // Cascade deletes signee ROWS at the DB level (no Eloquent events),
        // so their morphOne signature File must be cleaned up here or it orphans.
        foreach ($publishing->signees as $signee) {
            if ($signee->signature) {
                $signee->signature->delete(); // File::delete() removes disk file + row
            }
        }
        // signee rows themselves: left to the FK CASCADE
    });
}
```

## Notes / gotchas

- This deletes the *File*, never the *Signee* — which also sidesteps `Signee`'s fragile
  `debug_backtrace()`-based deletion guard (`Signee.php` `booted()`).
- `File::delete()` (`app/Models/File.php`) only handles `disk == 'local'`; `google` and
  backup paths are no-ops. Signatures are stored on `local` (`FileService.php` hardcodes
  it), so this works — but any future move to Google disk reopens the orphaning.
- Verify with a disk check, not just row counts: note `optional($signee->signature)->identifier`
  before deleting, then confirm the file is gone from `storage/app/...` afterward.
- Same shape exists elsewhere: `Release`/`Track`/`File` use model-override deletes with no
  morph FK — audit any new cascade against this pattern.
