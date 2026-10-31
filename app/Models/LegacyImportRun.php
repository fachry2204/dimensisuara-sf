<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImportRun extends Model
{
    public static function schemaReady(): bool
    {
        foreach (['legacy_import_runs', 'legacy_import_run_records', 'legacy_import_media_results'] as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) return false;
        }
        return true;
    }
    protected $guarded = ['id'];
    protected $casts = ['stats' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
}
