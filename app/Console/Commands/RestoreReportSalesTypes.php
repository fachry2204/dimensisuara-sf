<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Reporting\ReportImport;
use App\Services\ReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RestoreReportSalesTypes extends Command
{
    protected $signature = 'reports:restore-sales-types {report? : ID laporan; kosong untuk seluruh laporan SoundOn selesai} {--apply : Simpan hasil dan perbarui ringkasan}';
    protected $description = 'Pulihkan Sales Type dan Sales Sub Type kosong dari file sumber laporan; default hanya pemeriksaan';

    public function handle(): int
    {
        if (!Schema::hasColumn('report_rows', 'sales_sub_type')) {
            $this->error('Kolom sales_sub_type belum ada. Jalankan migrasi penambahan Sales Sub Type terlebih dahulu.');
            return self::FAILURE;
        }
        $reports = ReportImport::query()->whereIn('status', ['completed', 'pending_rate']);
        if ($this->argument('report') !== null) {
            if (!ctype_digit((string) $this->argument('report'))) {
                $this->error('ID laporan harus berupa angka.');
                return self::FAILURE;
            }
            $reports->whereKey($this->argument('report'));
        } else $reports->where('source', 'soundon');
        $this->info($this->option('apply') ? 'Mode simpan: jenis kosong diisi dari sumber yang cocok.' : 'Mode pemeriksaan: belum ada perubahan database. Tambahkan --apply untuk menyimpan.');
        $failed = false;
        $found = false;
        foreach ($reports->orderBy('id')->cursor() as $report) {
            $found = true;
            try {
                $file = File::where('owner_type', $report->getMorphClass())->where('owner_id', $report->id)->sole();
                $root = realpath(Storage::disk('local')->path(''));
                $path = realpath(Storage::disk('local')->path(str_replace('\\', '/', $file->identifier)));
                if ($file->disk !== 'local' || !$root || !$path || !str_starts_with($path, $root.DIRECTORY_SEPARATOR) || !is_readable($path)) {
                    throw new \RuntimeException('File sumber lokal tidak tersedia atau tidak terbaca.');
                }
                $source = ReportService::readUsageSource($path, $report->source, $report->sheet);
                $result = ReportService::restoreUsageTypes($report->id, $source, (bool) $this->option('apply'));
                $this->line('Laporan #'.$report->id.': '.$result['rows'].' baris cocok; '.$result['changes'].' baris '.($this->option('apply') ? 'diperbarui' : 'dapat dipulihkan').'.');
                if ($result['missing_types'] || $result['missing_subtypes']) {
                    $this->warn('File sumber: '.$result['missing_types'].' baris tanpa Sales Type; '.$result['missing_subtypes'].' baris tanpa Sales Sub Type. Nilai tersebut tidak ditebak.');
                }
            } catch (\Throwable $exception) {
                $failed = true;
                $this->error('Laporan #'.$report->id.': '.$exception->getMessage());
            }
        }
        if (!$found) {
            $this->error('Tidak ada laporan selesai yang cocok.');
            return self::FAILURE;
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
