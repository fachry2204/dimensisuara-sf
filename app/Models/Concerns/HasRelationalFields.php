<?php

namespace App\Models\Concerns;

use App\Database\RelationalFields;
use Illuminate\Support\Facades\DB;

trait HasRelationalFields
{
    protected array $pendingRelationalFields = [];
    protected ?\Illuminate\Support\Collection $loadedRelationalRows = null;

    public function initializeHasRelationalFields(): void
    {
        $this->hidden[] = 'relationalFieldRows';
    }

    public function relationalFieldRows(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        $map = RelationalFields::MAP[$this->getTable()];
        $row = new \App\Database\RelationalValue;
        $row->setTable($map['table']);
        $row->setConnection($this->getConnectionName());
        return $this->newHasMany($row->newQuery(), $this, $map['table'].'.owner_id', $this->getKeyName());
    }

    protected function isGuardableColumn($key)
    {
        if (isset(RelationalFields::MAP[$this->getTable()]['fields'][$key]) && RelationalFields::available($this->getTable())) return true;
        return parent::isGuardableColumn($key);
    }

    public function setAttribute($key, $value)
    {
        $format = RelationalFields::MAP[$this->getTable()]['fields'][$key] ?? null;
        if ($format !== null && RelationalFields::available($this->getTable())) {
            $this->pendingRelationalFields[$key] = RelationalFields::decodeInput($value, $format);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $format = RelationalFields::MAP[$this->getTable()]['fields'][$key] ?? null;
        if ($format === null || !RelationalFields::available($this->getTable())) return parent::getAttribute($key);
        if (array_key_exists($key, $this->pendingRelationalFields)) return RelationalFields::output($this->pendingRelationalFields[$key], $format);
        if (!$this->exists) return RelationalFields::output(null, $format);
        $rows = $this->relationLoaded('relationalFieldRows') ? $this->getRelation('relationalFieldRows') : ($this->loadedRelationalRows ??= $this->relationalFieldRows()->get());
        // During the backfill, the old column remains readable until verified and dropped.
        if (!$rows->contains('field', $key) && array_key_exists($key, $this->attributes)) return parent::getAttribute($key);
        return RelationalFields::output(RelationalFields::read($this->getTable(), $this->getKey(), $key, $rows), $format);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        if (!RelationalFields::available($this->getTable())) return $attributes;
        foreach (RelationalFields::MAP[$this->getTable()]['fields'] as $field=>$format) {
            if (!in_array($field, $this->getHidden(), true)) $attributes[$field] = $this->getAttribute($field);
        }
        return $attributes;
    }

    public function save(array $options = [])
    {
        if (!$this->pendingRelationalFields) return parent::save($options);
        return DB::transaction(function () use ($options) {
            $saved = parent::save($options);
            if ($saved) {
                foreach ($this->pendingRelationalFields as $field=>$value) RelationalFields::write($this->getTable(), $this->getKey(), $field, $value);
                $this->pendingRelationalFields = [];
                $this->loadedRelationalRows = null;
                $this->unsetRelation('relationalFieldRows');
            }
            return $saved;
        });
    }

    public function refresh()
    {
        $this->loadedRelationalRows = null;
        $this->pendingRelationalFields = [];
        return parent::refresh();
    }
}
