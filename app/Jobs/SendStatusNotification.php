<?php

namespace App\Jobs;

use App\Mail\StatusUpdateMail;
use App\Models\StatusNotificationDelivery;
use App\Services\StatusNotificationTemplates;
use App\Services\SystemSettings;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendStatusNotification implements ShouldQueue
{
    use Queueable;
    public int $tries = 1;
    public int $timeout = 60;
    public bool $failOnTimeout = true;

    public function __construct(public int $deliveryId)
    {
        $this->onConnection('status-notifications')->onQueue('default')->afterCommit();
    }

    public function handle(): void
    {
        $delivery = DB::transaction(function () {
            $item = StatusNotificationDelivery::lockForUpdate()->find($this->deliveryId);
            if (!$item || $item->status !== 'pending') return null;
            $item->update(['status'=>'processing','attempts'=>$item->attempts+1,'error'=>null]);
            return $item;
        });
        if (!$delivery) return;
        $settings = app(SystemSettings::class);
        $configuration = app(StatusNotificationTemplates::class)->configuration($delivery->channel);
        if (!$configuration['enabled'] || !($configuration['templates'][$delivery->event]['enabled'] ?? false)) {
            $delivery->update(['status'=>'skipped','error'=>'Notifikasi dinonaktifkan sebelum dikirim.']); return;
        }
        $payload = $delivery->payload;
        if (!filled($payload['recipient'] ?? null)) {
            $delivery->update(['status'=>'skipped','error'=>'Alamat email atau nomor WhatsApp penerima belum tersedia.']); return;
        }
        if ($delivery->channel === 'email' && !$settings->get('smtp_enabled')) {
            $delivery->update(['status'=>'skipped','error'=>'SMTP belum diaktifkan pada Pengaturan Umum.']); return;
        }
        if ($delivery->channel === 'whatsapp' && !filled($settings->fonnteToken())) {
            $delivery->update(['status'=>'skipped','error'=>'Token Fonnte belum diatur pada Pengaturan Umum.']); return;
        }
        try {
            if ($delivery->channel === 'email') Mail::mailer('smtp')->to($payload['recipient'])->send(new StatusUpdateMail($payload['message']));
            elseif ($delivery->channel === 'whatsapp') WhatsAppService::singleSend($payload['recipient'],$payload['message']['whatsapp']);
            else throw new \RuntimeException('Unknown notification channel.');
            $delivery->update(['status'=>'sent','sent_at'=>now(),'error'=>null]);
        } catch (\Throwable) {
            // Providers may accept a message before a timeout. Never automatically retry
            // an ambiguous send, and never expose credentials/recipient in exception text.
            $delivery->update(['status'=>'failed','error'=>'Pengiriman belum dapat dipastikan. Periksa koneksi SMTP/Fonnte dan riwayat penyedia sebelum mengirim ulang.']);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        StatusNotificationDelivery::whereKey($this->deliveryId)->whereIn('status',['pending','processing'])->update([
            'status'=>'failed','error'=>'Worker terhenti. Periksa riwayat penyedia untuk memastikan pesan belum terkirim sebelum mencoba ulang.',
        ]);
    }
}
