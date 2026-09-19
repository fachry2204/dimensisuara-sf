<?php

namespace App\Jobs\Publishing;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Publishing\Publishing;

class InvalidateExpiredRequest implements ShouldQueue
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
        $publishings = Publishing::where('status', 'pending')
            ->where('due_date', '<', now()->subDays())
            ->get();

        foreach($publishings as $publishing) {
            $publishing->status = 'expired';
            $publishing->save();

            Log::info('Publishing request ID ' . $publishing->id . ' has been invalidated by System');
        }
    }
}
