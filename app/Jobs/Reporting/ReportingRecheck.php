<?php

namespace App\Jobs\Reporting;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\ReportService;

class ReportingRecheck implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;

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
        try {
            // Process report import
            $count = ReportService::recheck();
            if($count > 0) {
                Log::info("Report recheck success: " . $count . " rows updated");
            }
        }
        catch (\Throwable $th) {
            Log::error("Report recheck error: " . $th->getMessage(), ['stack' => $th->getTraceAsString()]);
            throw $th;
        }
    }
}
