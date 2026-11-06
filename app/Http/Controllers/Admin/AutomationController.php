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
        if ($automationTab === 'drive') return view('admin.automation.drive-status',app(\App\Services\DriveUploadMonitor::class)->dashboard($request)+compact('automationTab'));
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

        $records = app(\App\Services\AutomationRecords::class);
        $present = function ($rows, $table) use ($records) {
            return $rows->map(function ($job) use ($table, $records) {
                $descriptor = $records->descriptor($table, $job);
                // Batch members are represented by their batch while that batch exists.
                if ($descriptor['batch_id'] && DB::table('job_batches')->where('id', $descriptor['batch_id'])->exists()) return null;
                return (object) [
                    'id'=>$job->id, 'uuid'=>$job->uuid ?? null, 'name'=>$descriptor['name'],
                    'created_at'=>$job->created_at ?? null, 'failed_at'=>$job->failed_at ?? null,
                    'can_delete'=>$table !== 'jobs' || ($job->reserved_at ?? null) === null,
                ];
            })->filter()->values();
        };
        $fails = $present($fails, 'failed_jobs');
        $jobs = $present($jobs, 'jobs');
        $automationTab = 'jobs';
        return view('admin.automation.index', compact('jobs', 'batches', 'fails', 'automationTab'));
    }

    public function retry($id)
    {
        Log::info('Retrying job ' . $id);
        Artisan::call('queue:retry', ['id' => $id]);
        return redirect()->back()->with('success', 'Job retried, please wait...');
    }

    public function startDrive(\App\Services\SystemSettings $settings)
    {
        if (!$settings->get('drive_enabled') || $settings->get('drive_connection_status') !== 'ok') return redirect()->route('admin.automation.index',['tab'=>'drive'])->withErrors(['drive'=>'Selesaikan Tes Koneksi dan aktifkan Google Drive terlebih dahulu.']);
        $result=Artisan::call('drive:archive-released');
        return $result === 0
            ? redirect()->route('admin.automation.index',['tab'=>'drive'])->with('success','Pemindaian file Published dijadwalkan. Worker memindahkan file lokal dan menata arsip lama secara bertahap.')
            : redirect()->route('admin.automation.index',['tab'=>'drive'])->withErrors(['drive'=>'Pemindaian belum berhasil. Periksa migrasi penataan folder dan antrean worker.']);
    }

    public function destroyNotification(Request $request, string $channel, int $delivery, \App\Services\AutomationRecords $records)
    {
        try { $records->forgetNotification($channel, $delivery); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() !== 409 || $request->expectsJson()) throw $exception;
            return redirect()->route('admin.automation.index', ['tab'=>$channel])->withErrors(['automation'=>$exception->getMessage()]);
        }
        return redirect()->route('admin.automation.index', ['tab'=>$channel])->with('success', 'Riwayat notifikasi dihapus. Pesan yang sudah diterima penyedia tidak ditarik kembali.');
    }

    public function destroyJob(Request $request, string $kind, string $id, \App\Services\AutomationRecords $records)
    {
        try { $records->forgetJob($kind, $id); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            if ($exception->getStatusCode() !== 409 || $request->expectsJson()) throw $exception;
            return redirect()->route('admin.automation.index', ['tab'=>'jobs'])->withErrors(['automation'=>$exception->getMessage()]);
        }
        return redirect()->route('admin.automation.index', ['tab'=>'jobs'])->with('success', 'Data Job Monitor dihapus. Job yang masih menunggu dalam data tersebut dibatalkan.');
    }

    public function destroyAllJobs(string $kind, \App\Services\AutomationRecords $records)
    {
        $result = $records->forgetAllJobs($kind);
        $label = ['job'=>'Pending Jobs', 'failed-job'=>'Failed Jobs', 'batch'=>'Job Batches'][$kind];
        $message = "{$result['deleted']} data {$label} dihapus.";
        if ($result['skipped']) $message .= " {$result['skipped']} data dilewati karena sedang diproses, termasuk anggota batch, atau sudah tidak tersedia.";
        return redirect()->route('admin.automation.index', ['tab'=>'jobs'])->with('success', $message);
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
