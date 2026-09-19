<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\File as FileFacade;

class ClearFileCache implements ShouldQueue
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
        // Clear filemtime cache
        clearstatcache();

        // Get files
        $tempdir = storage_path('app' . DIRECTORY_SEPARATOR . 'cache');
        if(FileFacade::exists($tempdir)) {
            $files = array_diff(scandir($tempdir), ['.', '..']);
    
            // Filter files older than 1 hour
            $files = array_filter($files, function ($file) use ($tempdir) {
                return filemtime($tempdir . DIRECTORY_SEPARATOR . $file) < time() - 3600;
            });
    
            // Delete files
            foreach ($files as $file) {
                if(FileFacade::exists($tempdir . DIRECTORY_SEPARATOR . $file)) {
                    FileFacade::delete($tempdir . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
    }
}
