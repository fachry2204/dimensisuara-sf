<?php

namespace App\Console\Commands;

use App\Jobs\RunLegacyImport;
use App\Models\LegacyImportRun;
use App\Services\Legacy\ImportPaths;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportLegacyIdentities extends Command
{
    protected $signature = 'legacy:import-identities {run : ID riwayat impor}
        {--media-root= : Folder utama aplikasi lama}
        {--limit=50 : Batas pengguna per eksekusi}
        {--max-time=45 : Batas waktu antar pengguna, dalam detik}';

    protected $description = 'Lengkapi dokumen identitas secara langsung tanpa antrean atau pemrosesan ulang WAV/cover';

    public function handle(): int
    {
        $runId = filter_var($this->argument('run'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $limit = filter_var($this->option('limit'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>500]]);
        $seconds = filter_var($this->option('max-time'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>600]]);
        if (!$runId || !$limit || !$seconds) {
            $this->error('ID impor, limit (1–500), dan max-time (1–600) harus berupa angka valid.');
            return self::FAILURE;
        }
        if (!LegacyImportRun::schemaReady()) {
            $this->error('Tabel impor belum tersedia. Jalankan migrate terlebih dahulu.');
            return self::FAILURE;
        }
        $run = LegacyImportRun::find($runId);
        if (!$run) {
            $this->error('Riwayat impor tidak ditemukan pada database aplikasi ini.');
            return self::FAILURE;
        }
        $this->line('Aplikasi: '.base_path());
        $this->line('Database: '.DB::connection()->getDatabaseName());
        $mysql = DB::connection()->getDriverName() === 'mysql';
        if ($mysql && !(int) DB::selectOne("SELECT GET_LOCK('dimensi_legacy_import', 0) AS acquired")->acquired) {
            $this->error('Worker impor lain masih memproses data. Tunggu lalu jalankan kembali.');
            return self::FAILURE;
        }
        try {
            $run->refresh();
            if (!in_array($run->status,['imported','media_queued','media_failed','media_issues','complete','identities_queued','identities_failed','identities_issues','identities_complete'],true)) {
                throw new RuntimeException('Database harus selesai diimpor sebelum melengkapi dokumen identitas.');
            }
            $root = app(ImportPaths::class)->mediaRoot($this->option('media-root') ?: ($run->media_root ?? ''));
            $restart = $run->status !== 'identities_queued' || $root !== $run->media_root;
            $changes = ['status'=>'identities_queued','media_root'=>$root,'error'=>null,'finished_at'=>null];
            if ($restart) $changes += ['media_cursor'=>0,'media_processed'=>0,'started_at'=>null];
            $run->update($changes);
        } catch (Throwable $e) {
            $this->error($this->safeError($e));
            return self::FAILURE;
        } finally {
            if ($mysql) DB::select("SELECT RELEASE_LOCK('dimensi_legacy_import')");
        }

        $this->info('Memproses dokumen identitas saja. WAV, cover art, dan IPL dilewati.');
        $deadline = microtime(true) + $seconds;
        for ($count=0; $count<$limit && microtime(true)<$deadline; $count++) {
            $before = (int) $run->media_processed;
            (new RunLegacyImport($run->id,'identities'))->handleInline();
            $run->refresh();
            $this->line('Pengguna diproses: '.$run->media_processed.' | Status: '.$run->status);
            if ($run->status !== 'identities_queued') break;
            if ((int) $run->media_processed === $before) {
                $this->warn('Proses sedang diambil worker lain. Ulangi perintah setelah worker selesai.');
                break;
            }
        }
        $counts = $run->mediaResults()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total','status');
        $this->line('Disalin: '.($counts['copied'] ?? 0).' | Dipertahankan: '.($counts['kept'] ?? 0).' | Kendala: '.($counts['issue'] ?? 0));
        if ($run->status === 'identities_failed') {
            $this->error($run->error ?: 'Proses gagal. Periksa log aplikasi.');
            return self::FAILURE;
        }
        if ($run->status === 'identities_queued') {
            $this->warn('Belum selesai. Jalankan perintah yang sama untuk melanjutkan dari progres terakhir.');
        } elseif ($run->status === 'identities_issues') {
            $this->warn('Selesai dengan kendala. Periksa lokasi file pada daftar hasil impor.');
            foreach ($run->mediaResults()->where('status','issue')->limit(10)->get() as $issue) {
                $this->line('#'.$issue->record_id.' '.$issue->field.': '.$issue->message);
            }
        } elseif ($run->status === 'identities_complete') {
            $this->info('Import dokumen identitas selesai. Muat ulang halaman pengguna.');
            if (($counts['copied'] ?? 0) + ($counts['kept'] ?? 0) === 0) $this->warn('Tidak ada referensi dokumen pengguna yang berhasil ditemukan. Periksa arsip SQL dan pemetaan pengguna.');
        } else {
            $this->error('Tahap impor berubah. Periksa riwayat impor sebelum melanjutkan.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }

    private function safeError(Throwable $e): string
    {
        return $e instanceof RuntimeException && !$e instanceof QueryException
            ? mb_substr($e->getMessage(),0,500) : 'Proses gagal. Periksa konfigurasi database dan log aplikasi.';
    }
}
