<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImportRun extends Model
{
    use \App\Models\Concerns\HasRelationalFields;

    public function mediaQueueLabel(): string
    {
        return $this->started_at ? ($this->identitiesOnly() ? 'Import dokumen identitas belum selesai' : 'Penyalinan media belum selesai') : 'Menunggu worker impor';
    }

    public function identitiesOnly(): bool
    {
        return str_starts_with((string) $this->status,'identities_');
    }

    public function mediaResults(): \Illuminate\Database\Query\Builder
    {
        $query = \Illuminate\Support\Facades\DB::table('legacy_import_media_results')->where('run_id',$this->id);
        if ($this->identitiesOnly()) $query->whereIn('record_id',\Illuminate\Support\Facades\DB::table('legacy_import_records')->select('id')->where('source','dimensisuara_legacy')->where('entity','users'));
        return $query;
    }

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
