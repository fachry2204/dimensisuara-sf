# Eloquent Morph & Pivot Gotchas

Lessons from implementing the WD Cloud FTP track backup feature. These bite when writing feature tests or querying the `filestorage` / `release_track` tables.

## 1. `File::create()` fails on morph columns

`filestorage` uses `morphs('owner')`, which creates `owner_id` and `owner_type` as `NOT NULL` with no defaults. Calling `File::create([...])` inserts immediately, before `owner()->associate()` can set those columns, so MySQL rejects the insert.

Use the same pattern as `FileService::store()`: build the model, associate, then save.

```php
$file = new File([...]);
$file->owner()->associate($track);
$file->profile()->associate($profile);
$file->save();
```

## 2. BelongsToMany `orderBy('pivot_*')` is wrong

`Track::releases()` ordered by `pivot_track_order`, but the pivot column is `track_order` (`withPivot('track_order')`). The generated SQL failed with `Unknown column 'pivot_track_order'`.

Order by the real pivot column:

```php
->orderBy('track_order')
```

`Release::tracks()` already did this correctly; only `Track::releases()` was wrong.

## 3. `filestorage.identifier` is `NOT NULL`

The original `filestorage` migration declared `identifier` as non-nullable text. Setting it to `null` after a backup (to mark the local copy as gone) violated the constraint.

Add a nullable migration before nulling it:

```php
$table->text('identifier')->nullable()->change();
```

## 4. `Profile.userid` is not fillable

`App\Models\User\Profile::$fillable` does not include `userid` (the FK to `users`), so `Profile::create(['userid' => ...])` silently drops it and the insert fails with `Field 'userid' doesn't have a default value`.

Set it explicitly after constructing the model:

```php
$profile = new Profile([...]);
$profile->userid = $user->id;
$profile->save();
```
