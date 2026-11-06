<?php

namespace App\Console\Commands;

use App\Services\ReleasedFilesDrive;
use App\Services\SystemSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ArchiveReleasedFiles extends Command
{
    protected $signature = 'drive:archive-released';
    protected $description = 'Queue released audio and artwork for verified rclone transfer to Google Drive';
    public function handle(ReleasedFilesDrive $service): int
    {
        if (!app(SystemSettings::class)->get('drive_enabled') || !Schema::hasTable('drive_transfers')) {
            $this->info('Google Drive belum diaktifkan.'); return self::SUCCESS;
        }
        try { $count = $service->queuePublished(); }
        catch (\Throwable) { $this->error('Pemindaian gagal. Periksa migrasi riwayat Drive dan koneksi antrean.'); return self::FAILURE; }
        $this->info($count.' file dijadwalkan.');
        return self::SUCCESS;
    }
}
