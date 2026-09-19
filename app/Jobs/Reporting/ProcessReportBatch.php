<?php

namespace App\Jobs\Reporting;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\ReportService;
use App\Models\Reporting\ReportImport;

class ProcessReportBatch implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ReportImport $report
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Process report import
            ReportService::detect($this->report);
        }
        catch (\Throwable $th) {
            // Set report status to failed
            $this->report->status = 'failed';
            $this->report->save();

            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            throw $th;
        }
    }
}
