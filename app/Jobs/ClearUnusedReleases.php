<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Release;

class ClearUnusedReleases implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $releases = Release::where('delete_by', '<', now())->whereIn('status', ['draft', 'rejected'])->get();
        foreach ($releases as $release) {
            // Capture title and id before deletion
            $releaseTitle = $release->title;
            $releaseId = $release->id;

            try {
                // Delete release
                $release->delete();

                // Log
                Log::info("Release '{$releaseTitle}' deleted.", [
                    'reason'        => 'expired',
                    'model_id'      => $releaseId,
                    'model_type'    => get_class($release),
                ]);
            }
            catch (\Throwable $th) {
                Log::error("Failed to delete release '{$releaseTitle}'.", [
                    'release_title' => $releaseTitle,
                    'model_id'      => $releaseId,
                    'model_type'    => get_class($release),
                    'reason'        => 'expired',
                    'message'       => $th->getMessage(),
                    'stack'         => $th->getTraceAsString()
                ]);
            }
        }
    }
}
