<?php

namespace App\Console\Commands;

use App\Services\LocalReleaseThumbnails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RestoreDriveThumbnails extends Command
{
    protected $signature='drive:restore-thumbnails {--limit=500 : Jumlah file per putaran}';
    protected $description='Queue Google Drive thumbnails for verified local restoration';

    public function handle(LocalReleaseThumbnails $thumbnails): int
    {
        if (!Schema::hasTable('drive_transfers') || !Schema::hasColumn('drive_transfers','layout_version')) {
            $this->error('Riwayat Drive belum tersedia.');return self::FAILURE;
        }
        $limit=filter_var($this->option('limit'),FILTER_VALIDATE_INT);
        if (!$limit || $limit<1 || $limit>500) { $this->error('Limit harus 1 sampai 500.');return self::FAILURE; }
        $this->info($thumbnails->queue($limit).' thumbnail dijadwalkan untuk dikembalikan ke server.');
        return self::SUCCESS;
    }
}
