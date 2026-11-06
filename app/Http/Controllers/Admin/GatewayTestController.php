<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class GatewayTestController extends Controller
{
    public function send(Request $request, string $channel, SystemSettings $settings)
    {
        $values = $request->validate(['recipient' => $channel === 'email'
            ? ['required', 'email', 'max:255', 'not_regex:/[\r\n]/']
            : ['required', 'string', 'regex:/^(?:\+?[1-9][0-9]{7,14}|08[0-9]{7,12})$/']]);
        $settings->refresh();
        if ($channel === 'email' && !$settings->get('smtp_enabled')) {
            throw ValidationException::withMessages(['recipient' => 'Aktifkan dan simpan SMTP sebelum melakukan tes kirim.']);
        }
        if ($channel === 'whatsapp' && !filled($settings->fonnteToken())) {
            throw ValidationException::withMessages(['recipient' => 'Simpan token Fonnte sebelum melakukan tes kirim.']);
        }
        try {
            $message = 'Tes gateway '.($channel === 'email' ? 'email' : 'WhatsApp').' '.app_name().'. Waktu: '.now()->toIso8601String().'.';
            if ($channel === 'email') {
                config(['mail.mailers.smtp.timeout' => 15]);
                Mail::purge('smtp');
                Mail::mailer('smtp')->raw($message, fn ($mail) => $mail->to($values['recipient'])->subject('Tes Gateway Email — '.app_name()));
            } else {
                WhatsAppService::singleSend($values['recipient'], $message);
            }
        } catch (\Throwable) {
            $settings->save(['gateway_test_'.$channel.'_at' => now()->toIso8601String(), 'gateway_test_'.$channel.'_status' => 'failed']);
            // Provider exceptions may contain credentials and recipients. Never flash raw errors.
            return back()->withErrors(['gateway_test' => 'Tes kirim gagal atau belum dapat dipastikan. Periksa konfigurasi SMTP atau koneksi device/token/kuota Fonnte, dan riwayat penyedia sebelum mencoba ulang.']);
        }
        $settings->save(['gateway_test_'.$channel.'_at' => now()->toIso8601String(), 'gateway_test_'.$channel.'_status' => 'accepted']);
        return back()->with('success', 'Pesan tes diterima oleh '.($channel === 'email' ? 'server SMTP' : 'Fonnte').'. Periksa inbox/spam atau WhatsApp penerima. Ini belum merupakan konfirmasi pesan diterima di perangkat.');
    }
}
