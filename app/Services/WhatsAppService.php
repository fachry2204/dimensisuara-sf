<?php

namespace App\Services;

use Illuminate\Notifications\Notification;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WhatsAppService {

    /**
     * Method for custom notification driver
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $phone = null;
        
        // Get phone number from anonymous user
        if(!empty($notifiable->phone)) {
            $phone = $notifiable->phone;
        }

        // Get phone number from profile
        if(!empty($notifiable->profile)) {
            if($notifiable->profile->phone) {
                $phone = $notifiable->profile->phone;
            }
        }

        if($phone) {
            $this->singleSend($phone, $notification->toWhatsAppService($notifiable));
        }
    }
    
    public static function singleSend($number, $message)
    {
        $number = preg_replace('/[\s()+.-]/', '', (string) $number);
        // Modify number if it starts with 08
        if (substr($number, 0, 2) === '08') {
            $number = '628' . substr($number, 2);
        }

        if (!preg_match('/^[1-9][0-9]{7,14}$/', $number)) throw new \InvalidArgumentException('Nomor WhatsApp tidak valid.');
        if (!filled(config('system-settings.whatsapp.token'))) throw new \RuntimeException('Token Fonnte belum diatur di Setting System.');

        // Fonnte identifies the sending device from the Authorization token.
        $payload = [
            "target"    => $number,
            "countryCode" => '0',
            "message"   => $message
        ];

        // Hit API
        $decode = SELF::hit('/send', $payload);

        // Log sent message
        DB::table('notify_whatsapp')->insert([
            'gwid'      => isset($decode['id'][0]) ? substr((string) $decode['id'][0], 0, 100) : null,
            'deviceid'  => config('system-settings.whatsapp.number') ?: 'fonnte',
            'phone'     => $number,
            'message'   => $message,
            'added_on'  => now()
        ]);

        return true;
    }

    private static function hit($endpoint, $payload)
    {
        $response = Http::asForm()->acceptJson()->withHeaders([
            'Authorization' => config('system-settings.whatsapp.token'),
        ])->connectTimeout(10)->timeout(30)->withoutRedirecting()->post('https://api.fonnte.com'.$endpoint, $payload);
        $decode = $response->json();
        if ($response->successful() && is_array($decode) && ($decode['status'] ?? false) === true) return $decode;
        // Do not expose the token, recipient, message or raw provider response in logs.
        throw new \RuntimeException('Fonnte menolak pengiriman WhatsApp (HTTP '.$response->status().'). Periksa token, koneksi device dan kuota Fonnte.');
    }
}
