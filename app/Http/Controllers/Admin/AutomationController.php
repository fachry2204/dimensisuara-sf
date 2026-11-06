<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $automationTab = $request->input('tab', 'jobs');
        if (in_array($automationTab, ['email', 'whatsapp'], true)) {
            $channel = $automationTab;
            $channelLabel = $channel === 'email' ? 'Email' : 'WhatsApp';
            $notificationsReady = \Illuminate\Support\Facades\Schema::hasTable('status_notification_deliveries');
            $history = $notificationsReady
                ? \App\Models\StatusNotificationDelivery::select(['id','event','channel','user_id','status','attempts','error','sent_at','updated_at'])
                    ->where('channel', $channel)->latest('id')->paginate(30)->withQueryString()
                : null;
            $notificationEvents = app(\App\Services\StatusNotificationTemplates::class)->events();
            return view('admin.automation.notification-history', compact('automationTab', 'channel', 'channelLabel', 'notificationsReady', 'history', 'notificationEvents'));
        }
        $fails      = DB::table('failed_jobs')->get();
        $batches    = DB::table('job_batches')->get();
        $jobs       = DB::table('jobs')->get();

        foreach ($fails as $index => $job) {
            \App\Database\RelationalFields::hydratePayload('failed_jobs',$job);
            try {
                $payload = json_decode($job->payload, 1);
                $command = unserialize($payload['data']['command']);
                if (property_exists($command, 'batchId') && $command->batchId) {
                    unset($fails[$index]);
                    $batch = DB::table('job_batches')->find($command->batchId);
                    if (!$batch) {
                        DB::table('failed_jobs')->where('id', $job->id)->delete();
                    }
                }
            }
            catch (\Throwable $th) {
                Log::error($th->getMessage(), ['stack' => $th->getTraceAsString(), 'job' => $job]);
                unset($fails[$index]);
                DB::table('failed_jobs')->where('id', $job->id)->delete();
            }
        }

        foreach ($jobs as $index => $job) {
            \App\Database\RelationalFields::hydratePayload('jobs',$job);
            try {
                $payload = json_decode($job->payload, 1);
                $command = unserialize($payload['data']['command']);
                if (property_exists($command, 'batchId') && $command->batchId) {
                    unset($jobs[$index]);
                    $batch = DB::table('job_batches')->find($command->batchId);
                    if (!$batch) {
                        DB::table('failed_jobs')->where('id', $job->id)->delete();
                    }
                }
            }
            catch (\Throwable $th) {
                Log::error($th->getMessage(), ['stack' => $th->getTraceAsString(), 'job' => $job]);
                unset($jobs[$index]);
                DB::table('jobs')->where('id', $job->id)->delete();
            }
        }

        $fails = $fails->values();
        $jobs = $jobs->values();
        $automationTab = 'jobs';
        return view('admin.automation.index', compact('jobs', 'batches', 'fails', 'automationTab'));
    }

    public function retry($id)
    {
        Log::info('Retrying job ' . $id);
        Artisan::call('queue:retry', ['id' => $id]);
        return redirect()->back()->with('success', 'Job retried, please wait...');
    }

    public function retry_batch($id)
    {
        // Mark batch as not cancelled
        DB::table('job_batches')->where('id', $id)->update([
            'cancelled_at' => null
        ]);

        Log::info('Retrying batch ' . $id);
        Artisan::call('queue:retry-batch', ['id' => $id]);
        return redirect()->back()->with('success', 'Batch retried, please wait...');
    }
}
