<?php

use App\Database\RelationalFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        RelationalFields::resetSchemaCache();
        // Older installations created numeric IDs, but Laravel notifications use UUIDs.
        if (Schema::hasTable('notifications')) {
            $id = collect(Schema::getColumns('notifications'))->firstWhere('name','id');
            if ($id && in_array($id['type_name'],['bigint','int','integer','mediumint','smallint'],true)) {
                Schema::table('notifications', fn (Blueprint $table) => $table->string('id',191)->change());
            }
        }
        foreach (RelationalFields::MAP as $owner=>$map) {
            if (!Schema::hasTable($owner) || Schema::hasTable($map['table'])) continue;
            $parentColumn = collect(Schema::getColumns($owner))->firstWhere('name',$map['key'] ?? 'id');
            Schema::create($map['table'], function (Blueprint $table) use ($owner, $map, $parentColumn) {
                $table->id();
                if (in_array(strtolower($parentColumn['type_name']),['bigint','int','integer','mediumint','smallint','tinyint'],true)) {
                    $unsigned = str_contains(strtolower($parentColumn['type']),'unsigned');
                    if (strtolower($parentColumn['type_name']) === 'bigint') $table->bigInteger('owner_id',false,$unsigned);
                    else $table->integer('owner_id',false,$unsigned);
                } else {
                    preg_match('/\((\d+)\)/',$parentColumn['type'],$length);
                    $column = $table->string('owner_id',(int) ($length[1] ?? 191));
                    if ($parentColumn['collation']) $column->charset(explode('_',$parentColumn['collation'])[0])->collation($parentColumn['collation']);
                }
                $table->string('field', 80);
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->text('key_name')->nullable();
                $table->string('key_type', 10)->default('string');
                $table->string('value_type', 10);
                $table->longText('scalar_value')->nullable();
                $table->index(['owner_id','field']);
                $table->foreign('owner_id')->references($map['key'] ?? 'id')->on($owner)->cascadeOnDelete();
                $table->foreign('parent_id')->references('id')->on($map['table'])->cascadeOnDelete();
            });
        }

        // Recover from MySQL DDL interruption: CREATE TABLE may succeed before its FK statements.
        foreach (RelationalFields::MAP as $owner=>$map) {
            if (!Schema::hasTable($map['table'])) continue;
            $foreign = Schema::getForeignKeys($map['table']);
            if (!collect($foreign)->contains(fn ($key) => $key['columns'] === ['owner_id'])) {
                Schema::table($map['table'], fn (Blueprint $table) => $table->foreign('owner_id')->references($map['key'] ?? 'id')->on($owner)->cascadeOnDelete());
            }
            if (!collect($foreign)->contains(fn ($key) => $key['columns'] === ['parent_id'])) {
                Schema::table($map['table'], fn (Blueprint $table) => $table->foreign('parent_id')->references('id')->on($map['table'])->cascadeOnDelete());
            }
        }

        // All backfills must verify successfully before any old column is removed.
        DB::transaction(function () {
            foreach (RelationalFields::MAP as $owner=>$map) {
                if (!Schema::hasTable($owner)) continue;
                $key = $map['key'] ?? 'id';
                foreach ($map['fields'] as $field=>$format) {
                    if (!Schema::hasColumn($owner, $field)) continue;
                    DB::table($owner)->orderBy($key)->chunkById(100, function ($rows) use ($owner, $map, $key, $field, $format) {
                        foreach ($rows as $row) {
                            $raw = $row->$field;
                            if ($owner === 'system_settings' || $format === 'serialized') {
                                $value = $raw === null ? null : unserialize(Crypt::decryptString($raw), ['allowed_classes'=>false]);
                            } else {
                                if (($map['encrypted'] ?? false) && $raw !== null) $raw = Crypt::decryptString($raw);
                                $value = RelationalFields::decodeInput($raw, $format);
                            }
                            RelationalFields::write($owner, $row->$key, $field, $value);
                            if (serialize($value) !== serialize(RelationalFields::read($owner, $row->$key, $field))) {
                                throw new RuntimeException('Relational backfill verification failed: '.$owner.'.'.$field);
                            }
                        }
                    }, $key);
                }
            }
        });

        foreach (RelationalFields::MAP as $owner=>$map) {
            if (!Schema::hasTable($owner)) continue;
            $columns = array_filter(array_keys($map['fields']), fn ($field) => Schema::hasColumn($owner, $field));
            if ($columns) Schema::table($owner, fn (Blueprint $table) => $table->dropColumn(array_values($columns)));
        }
        RelationalFields::resetSchemaCache();
    }

    public function down(): void
    {
        RelationalFields::resetSchemaCache();
        // Explicit rollback restores exact values before deleting the relational tables.
        foreach (array_reverse(RelationalFields::MAP, true) as $owner=>$map) {
            if (!Schema::hasTable($map['table'])) continue;
            $key = $map['key'] ?? 'id';
            foreach ($map['fields'] as $field=>$format) {
                if (!Schema::hasColumn($owner, $field)) Schema::table($owner, fn (Blueprint $table) => $table->longText($field)->nullable());
                DB::table($owner)->orderBy($key)->chunkById(100, function ($rows) use ($owner, $map, $key, $field, $format) {
                    foreach ($rows as $row) {
                        $value = RelationalFields::read($owner, $row->$key, $field);
                        $raw = ($owner === 'system_settings' || $format === 'serialized') ? Crypt::encryptString(serialize($value)) : ($value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                        if (($map['encrypted'] ?? false) && $owner !== 'system_settings' && $format !== 'serialized' && $raw !== null) $raw = Crypt::encryptString($raw);
                        DB::table($owner)->where($key, $row->$key)->update([$field=>$raw]);
                    }
                }, $key);
            }
            Schema::drop($map['table']);
        }
        RelationalFields::resetSchemaCache();
    }
};
