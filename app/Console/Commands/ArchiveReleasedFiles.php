<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveReleasedFile;
use App\Services\ReleasedFilesDrive;
use App\Services\SystemSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        $count = 0;
        $service->eligible()->select('filestorage.*')
            ->leftJoin('drive_transfers','filestorage.id','=','drive_transfers.file_id')
            ->orderByRaw('COALESCE(drive_transfers.updated_at, filestorage.created_at) ASC')->limit(500)->get()
            ->each(function ($file) use (&$count) { ArchiveReleasedFile::dispatch($file->id); $count++; });
        foreach (DB::table('drive_transfers')->where('status','cleanup_pending')->pluck('file_id') as $id) {
            ArchiveReleasedFile::dispatch($id); $count++;
        }
        $this->info($count.' file dijadwalkan.');
        return self::SUCCESS;
    }
}
