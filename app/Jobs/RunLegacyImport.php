<?php

namespace App\Jobs;

use App\Models\LegacyImportRun;
use App\Services\Legacy\DimensiImporter;
use App\Services\Legacy\ImportPaths;
use App\Services\Legacy\LegacyMediaImporter;
use App\Services\Legacy\SqlDumpReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class RunLegacyImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Lock contention releases jobs without treating another import as a failure.
    public int $tries = 1000;
    public int $timeout = 900;
    public bool $failOnTimeout = true;

    public function __construct(public int $runId, public string $phase)
    {
        $this->onConnection('legacy-import')->onQueue('legacy-import');
    }

    public function handle(): void
    {
        $run = LegacyImportRun::findOrFail($this->runId);
        $connection = DB::connection();
        $mysql = $connection->getDriverName() === 'mysql';
        if ($mysql && !(int) $connection->selectOne("SELECT GET_LOCK('dimensi_legacy_import', 0) AS acquired")->acquired) {
            $this->release(15);
            return;
        }
        try {
            $run->refresh();
            $expected = ['trial' => 'trial_queued', 'apply' => 'apply_queued', 'media' => 'media_queued'][$this->phase] ?? null;
            if (!$expected || $run->status !== $expected) return;
            $run->update(['started_at' => now(), 'error' => null]);
            if ($this->phase === 'media') {
                $this->media($run);
                return;
            }
            $path = app(ImportPaths::class)->inside($run->sql_path, config('legacy-import.inbox'));
            if (!hash_equals($run->sql_sha256, hash_file('sha256', $path))) {
                throw new RuntimeException('SQL berubah sejak dipilih. Buat pemeriksaan baru.');
            }
            $data = (new SqlDumpReader)->read($path, DimensiImporter::TABLES);
            if (empty($data['users'])) {
                throw new RuntimeException('Dump tidak memuat INSERT users yang didukung. Gunakan ekspor phpMyAdmin dengan nama kolom.');
            }
            foreach (['users' => ['email', 'password_hash', 'role', 'full_name'], 'releases' => ['user_id', 'title', 'status', 'cover_art'], 'tracks' => ['release_id', 'title', 'audio_file']] as $table => $columns) {
                foreach ($data[$table] as $row) {
                    if (array_diff($columns, array_keys($row))) {
                        throw new RuntimeException('Struktur tabel '.$table.' berbeda dari format Dimensi lama yang didukung. Pemetaan perlu disesuaikan.');
                    }
                }
            }
            $connection->beginTransaction();
            $stats = (new DimensiImporter)->run($data, $run->sql_sha256);
            if ($this->phase === 'apply') {
                // Map this run to the exact input IDs, including rows already imported by CLI
                // or by another dump with identical rows and a different file checksum.
                foreach (['releases', 'tracks'] as $entity) {
                    foreach (array_chunk(array_keys($data[$entity]), 500) as $ids) {
                        $records = DB::table('legacy_import_records')->where('source', 'dimensisuara_legacy')
                            ->where('entity', $entity)->where('status', 'imported')->whereIn('legacy_id', $ids)->pluck('id');
                        foreach ($records as $id) DB::table('legacy_import_run_records')->updateOrInsert(['run_id' => $run->id, 'record_id' => $id]);
                    }
                }
            }
            if ($this->phase === 'apply') $connection->commit();
            else $connection->rollBack();
            if ($this->phase === 'trial' && $run->media_root) {
                foreach (['releases' => ['cover_art', 'cover_url'], 'tracks' => ['audio_file', 'audio_clip', 'ipl_file']] as $table => $fields) {
                    foreach ($data[$table] as $row) {
                        if ($table === 'releases') $row['cover_url'] = empty($row['cover_art']) ? ($row['cover_url'] ?? null) : null;
                        foreach ($fields as $field) {
                            if (empty($row[$field])) continue;
                            try {
                                app(ImportPaths::class)->media($row[$field], $run->media_root);
                                $key = 'media.source_found';
                            } catch (RuntimeException) {
                                $key = 'media.source_missing_or_invalid';
                            }
                            $stats[$key] = ($stats[$key] ?? 0) + 1;
                        }
                    }
                }
            }
            $run->update(['stats' => $stats, 'status' => $this->phase === 'apply' ? 'imported' : 'trial_ready', 'finished_at' => now()]);
        } catch (Throwable $e) {
            if ($connection->transactionLevel()) $connection->rollBack();
            $run->update(['status' => $this->phase.'_failed', 'error' => $e instanceof RuntimeException && !$e instanceof \Illuminate\Database\QueryException ? mb_substr($e->getMessage(), 0, 500) : 'Proses gagal. Periksa format SQL, izin folder, kapasitas disk, dan log server.', 'finished_at' => now()]);
            // SQL exception text can contain personal data; do not expose it in the UI.
        } finally {
            if ($mysql) $connection->select("SELECT RELEASE_LOCK('dimensi_legacy_import')");
        }
    }

    private function media(LegacyImportRun $run): void
    {
        app(ImportPaths::class)->mediaRoot($run->media_root ?? '');
        $record = DB::table('legacy_import_records')->where('source', 'dimensisuara_legacy')
            ->whereIn('id', DB::table('legacy_import_run_records')->where('run_id', $run->id)->select('record_id'))->where('status', 'imported')
            ->whereIn('entity', ['releases', 'tracks'])->where('id', '>', $run->media_cursor)->orderBy('id')->first();
        if (!$record) {
            $hasIssues = DB::table('legacy_import_media_results')->where('run_id', $run->id)->where('status', 'issue')->exists();
            $run->update(['status' => $hasIssues ? 'media_issues' : 'complete', 'finished_at' => now()]);
            return;
        }
        app(LegacyMediaImporter::class)->importRecord($run, $record);
        $run->update(['media_cursor' => $record->id, 'media_processed' => $run->media_processed + 1]);
        self::dispatch($run->id, 'media');
    }

    public function failed(?Throwable $exception): void
    {
        LegacyImportRun::whereKey($this->runId)->where('status', $this->phase.'_queued')->update([
            'status' => $this->phase.'_failed', 'error' => 'Worker berhenti atau melewati batas waktu. Periksa worker sebelum mencoba ulang.', 'finished_at' => now(),
        ]);
    }
}
