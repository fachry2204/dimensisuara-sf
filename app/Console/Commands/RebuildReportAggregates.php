<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Illuminate\Console\Command;

class RebuildReportAggregates extends Command
{
    protected $signature = 'reports:rebuild-aggregates';
    protected $description = 'Rebuild derived report summaries from retained published report rows';

    public function handle(): int
    {
        try {
            ReportService::aggregate();
            $this->info('Ringkasan laporan berhasil dibangun ulang. Data laporan sumber tidak diubah.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Ringkasan gagal diperbarui; transaksi dibatalkan. Periksa log aplikasi.');
            return self::FAILURE;
        }
    }
}
