<?php

namespace App\Jobs;

use App\Services\TrackFileBackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BackupTrackFiles implements ShouldQueue
{
    use Queueable;

    public function handle(TrackFileBackupService $backupService): void
    {
        $backupService->eligibleFiles()->chunkById(100, function ($files) use ($backupService) {
            foreach ($files as $file) {
                try {
                    $backupService->backup($file);
                } catch (\Throwable $th) {
                    $backupService->markFailed($file, $th->getMessage());

                    Log::error('Track file backup job failed for file.', [
                        'file_id' => $file->id,
                        'message' => $th->getMessage(),
                    ]);
                }
            }
        });
    }
}
