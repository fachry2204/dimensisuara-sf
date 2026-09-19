<?php

namespace App\Jobs\Reporting;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\ReportService;
use App\Models\Reporting\ReportRow;

class ProcessCalculationPerBatch implements ShouldQueue
{
    use Queueable, Batchable;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $rowIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $rows = ReportRow::whereIn('id', $this->rowIds)->get();

            // Process report import
            ReportService::calculatePerBatch($rows);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            throw $th;
        }
    }
}
