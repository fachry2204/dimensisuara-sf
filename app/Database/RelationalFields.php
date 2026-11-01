<?php

namespace App\Database;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Typed relational rows for ordered credits and variable document fields; no JSON/blob payloads. */
final class RelationalFields
{
    public const MAP = [
        'jobs' => ['table'=>'queue_job_values', 'encrypted'=>true, 'fields'=>['payload'=>'json-string']],
        'failed_jobs' => ['table'=>'failed_job_values', 'encrypted'=>true, 'fields'=>['payload'=>'json-string']],
        'job_batches' => ['table'=>'batch_failure_values', 'string_key'=>true, 'fields'=>['failed_job_ids'=>'json-string']],
        'releases' => ['table'=>'release_artist_values', 'fields'=>['primaryartists'=>'objects']],
        'tracks' => ['table'=>'track_credit_values', 'fields'=>['primaryartists'=>'objects','featuredartists'=>'objects','contributors'=>'objects','productions'=>'objects','songwriters'=>'array']],
        'contracts' => ['table'=>'contract_field_values', 'fields'=>['fields'=>'json-string']],
        'logs' => ['table'=>'activity_log_context_values', 'fields'=>['context'=>'json-string']],
        'publishings' => ['table'=>'publishing_writer_values', 'fields'=>['songwriters'=>'json-string']],
        'publishing_signees' => ['table'=>'signee_publisher_values', 'fields'=>['publishers'=>'json-string']],
        'broadcasts' => ['table'=>'broadcast_recipient_values', 'fields'=>['excluded_user_ids'=>'array']],
        'announcements' => ['table'=>'announcement_content_values', 'fields'=>['content'=>'array']],
        'legacy_import_records' => ['table'=>'legacy_source_values', 'encrypted'=>true, 'fields'=>['payload'=>'serialized']],
        'legacy_import_runs' => ['table'=>'legacy_import_stat_values', 'fields'=>['stats'=>'array']],
        'status_notification_deliveries' => ['table'=>'notification_payload_values', 'encrypted'=>true, 'fields'=>['payload'=>'array']],
        'notifications' => ['table'=>'user_notification_values', 'string_key'=>true, 'fields'=>['data'=>'array']],
        'system_settings' => ['table'=>'system_setting_values', 'string_key'=>true, 'key'=>'key', 'encrypted'=>true, 'fields'=>['value'=>'native']],
    ];

    private static ?\WeakMap $schemaCache = null;

    public static function resetSchemaCache(): void { self::$schemaCache = new \WeakMap; }

    public static function available(string $owner): bool
    {
        if (!isset(self::MAP[$owner])) return false;
        self::$schemaCache ??= new \WeakMap;
        $pdo = DB::connection()->getPdo();
        $known = self::$schemaCache[$pdo] ?? [];
        if (!array_key_exists($owner,$known)) {
            $known[$owner] = Schema::hasTable(self::MAP[$owner]['table']);
            self::$schemaCache[$pdo] = $known;
        }
        return $known[$owner];
    }

    public static function decodeInput(mixed $value, string $format): mixed
    {
        if ($format === 'serialized' && is_string($value)) return unserialize(Crypt::decryptString($value), ['allowed_classes'=>false]);
        if ($format !== 'native' && is_string($value)) {
            if ($value === '') return null;
            return json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        }
        if ($format !== 'native' && $format !== 'serialized') {
            // JSON is accepted only as an input/transport format; persist the resulting typed rows.
            return json_decode(json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        }
        return $value;
    }

    public static function hydratePayload(string $owner, object $record): object
    {
        if (self::available($owner)) $record->payload = self::output(self::read($owner, $record->id, 'payload'), 'json-string');
        return $record;
    }

    public static function legacyPayload(object $record): mixed
    {
        return self::available('legacy_import_records')
            ? self::read('legacy_import_records', $record->id, 'payload')
            : unserialize(Crypt::decryptString($record->payload), ['allowed_classes'=>false]);
    }

    public static function output(mixed $value, string $format): mixed
    {
        if ($format === 'json-string') return $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if ($format === 'array') return self::toArrays($value);
        if ($format === 'objects') return self::toObjects($value) ?? [];
        return $value;
    }

    private static function toArrays(mixed $value): mixed
    {
        if (is_object($value)) $value = (array) $value;
        return is_array($value) ? array_map(fn ($v) => self::toArrays($v), $value) : $value;
    }

    private static function toObjects(mixed $value): mixed
    {
        if (is_object($value)) return (object) array_map(fn ($v) => self::toObjects($v), (array) $value);
        if (!is_array($value)) return $value;
        $items = array_map(fn ($v) => self::toObjects($v), $value);
        return array_is_list($value) ? $items : (object) $items;
    }

    public static function write(string $owner, int|string $id, string $field, mixed $value): void
    {
        $map = self::MAP[$owner];
        if (!isset($map['fields'][$field])) throw new RuntimeException('Unknown relational attribute.');
        DB::transaction(function () use ($owner, $id, $field, $value, $map) {
            DB::table($owner)->where($map['key'] ?? 'id', $id)->lockForUpdate()->first();
            DB::table($map['table'])->where('owner_id', $id)->where('field', $field)->delete();
            $nodes = 0;
            self::insertNode($map, $id, $field, $value, null, null, 0, 0, $nodes);
        });
    }

    private static function insertNode(array $map, int|string $ownerId, string $field, mixed $value, ?int $parent, int|string|null $key, int $position, int $depth, int &$nodes): void
    {
        if ($depth > 128 || ++$nodes > 100000) throw new RuntimeException('Relational document is too large or too deeply nested.');
        $type = match (true) {
            $value === null => 'null', is_bool($value) => 'boolean', is_int($value) => 'integer',
            is_float($value) => 'float', is_string($value) => 'string', is_array($value) => 'array',
            is_object($value) => 'object', default => throw new RuntimeException('Unsupported relational value type.'),
        };
        $scalar = match ($type) {
            'null','array','object' => null, 'boolean' => $value ? '1' : '0',
            'float' => sprintf('%.17g', $value), default => (string) $value,
        };
        if ($scalar !== null && ($map['encrypted'] ?? false)) $scalar = Crypt::encryptString($scalar);
        $node = DB::table($map['table'])->insertGetId([
            'owner_id'=>$ownerId, 'field'=>$field, 'parent_id'=>$parent, 'position'=>$position,
            'key_name'=>$key === null ? null : (string) $key, 'key_type'=>is_int($key) ? 'integer' : 'string',
            'value_type'=>$type, 'scalar_value'=>$scalar,
        ]);
        if ($type === 'array' || $type === 'object') {
            $index = 0;
            foreach ((array) $value as $childKey=>$child) self::insertNode($map, $ownerId, $field, $child, $node, $childKey, $index++, $depth + 1, $nodes);
        }
    }

    public static function read(string $owner, int|string $id, string $field, ?\Illuminate\Support\Collection $rows = null): mixed
    {
        $map = self::MAP[$owner];
        $rows ??= DB::table($map['table'])->where('owner_id', $id)->orderBy('position')->orderBy('id')->get();
        $rows = $rows->where('field', $field);
        $root = $rows->firstWhere('parent_id', null);
        if (!$root) return null;
        $children = $rows->groupBy(fn ($row) => $row->parent_id ?? 'root');
        return self::readNode($map, $root, $children, 0);
    }

    private static function readNode(array $map, object $node, $children, int $depth): mixed
    {
        if ($depth > 128) throw new RuntimeException('Invalid relational hierarchy.');
        $scalar = $node->scalar_value;
        if ($scalar !== null && ($map['encrypted'] ?? false)) $scalar = Crypt::decryptString($scalar);
        if (in_array($node->value_type, ['array','object'], true)) {
            $value = [];
            foreach ($children->get($node->id, []) as $child) {
                $key = $child->key_type === 'integer' ? (int) $child->key_name : $child->key_name;
                $value[$key] = self::readNode($map, $child, $children, $depth + 1);
            }
            return $node->value_type === 'object' ? (object) $value : $value;
        }
        return match ($node->value_type) {
            'null'=>null, 'boolean'=>$scalar === '1', 'integer'=>(int) $scalar,
            'float'=>(float) $scalar, 'string'=>$scalar,
            default=>throw new RuntimeException('Invalid relational value type.'),
        };
    }

    /** Used by SQL import and the activity logger, which intentionally bypass Eloquent events. */
    public static function insert(string $owner, array $attributes): int
    {
        if (!self::available($owner)) return DB::table($owner)->insertGetId($attributes);
        return DB::transaction(function () use ($owner, $attributes) {
            $values = [];
            foreach (self::MAP[$owner]['fields'] as $field=>$format) {
                if (array_key_exists($field, $attributes)) {
                    $values[$field] = self::decodeInput($attributes[$field], $format);
                    unset($attributes[$field]);
                }
            }
            $id = DB::table($owner)->insertGetId($attributes);
            foreach ($values as $field=>$value) self::write($owner, $id, $field, $value);
            return $id;
        });
    }
}
