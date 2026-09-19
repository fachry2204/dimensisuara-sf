<?php

namespace App\Services;

use Illuminate\Notifications\Notification;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\User\Profile;

class WhatsAppService {

    /**
     * Method for custom notification driver
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $phone = null;
        
        // Get phone number from anonymous user
        if($notifiable->phone) {
            $phone = $notifiable->phone;
        }

        // Get phone number from profile
        if($notifiable->profile) {            
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
        // Modify number if it starts with 08
        if (substr($number, 0, 2) === '08') {
            $number = '628' . substr($number, 2);
        }

        // Define payload
        $payload = [
            "api_key"   => env('WHATSAPP_API_TOKEN'),
            "sender"    => env('WHATSAPP_API_NUMBER'),
            "number"    => $number,
            "message"   => $message
        ];

        // Hit API
        $decode = SELF::hit('/send-message', $payload);

        // Determine status
        if($decode['status'] === false) {
            Log::error('Failed to send message to WhatsApp Gateway: ' . $decode['msg'], ['phone' => $number, 'message' => $message]);
            return false;
        }

        // Log sent message
        DB::table('notify_whatsapp')->insert([
            'gwid'      => null,
            'deviceid'  => env('WHATSAPP_API_NUMBER'),
            'phone'     => $number,
            'message'   => $message,
            'added_on'  => now()
        ]);

        return true;
    }

    private static function hit($endpoint, $payload)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('WHATSAPP_API_URL') . $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_close($ch);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $decode = json_decode($result, 1);
        if($decode) {
            if($decode['status'] === true) {
                return $decode;
            }
            throw new \Exception("Failed to send message to WhatsApp Gateway: {$decode['msg']}");
        }
        else {
            throw new \Exception("Unable to decode response from WhatsApp Gateway: ({$httpCode}) {$result}");
        }
    }
}
