<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\File as FileFacade;

class ClearTemporaryFiles implements ShouldQueue
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

        // Check if directory exists
        if(FileFacade::exists(storage_path('app' . DIRECTORY_SEPARATOR . 'temp'))) {
            
            // Get files
            $tempdir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
            $files = array_diff(scandir($tempdir), ['.', '..']);
    
            // Filter files older than 6 hours
            $files = array_filter($files, function ($file) use ($tempdir) {
                return filemtime($tempdir . DIRECTORY_SEPARATOR . $file) < time() - 21600;
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
