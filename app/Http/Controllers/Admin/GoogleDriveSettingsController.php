<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RcloneDrive;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GoogleDriveSettingsController extends Controller
{
    public function save(Request $request, SystemSettings $settings)
    {
        $values = $request->validate([
            'drive_enabled'=>['required','boolean'],
            'drive_remote'=>['required','string','max:100','regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/D'],
            'drive_folder'=>['required','string','max:500','regex:~^[a-zA-Z0-9 _./-]+$~D',function ($attribute,$value,$fail) { if (str_contains($value,'..') || str_starts_with($value,'/') || trim($value,' /') === '') $fail('Gunakan nama folder relatif Google Drive.'); }],
            'drive_binary'=>['required','string','max:500',function ($attribute,$value,$fail) { if (!in_array(basename(str_replace('\\','/',$value)),['rclone','rclone.exe'],true) || preg_match('/[\x00-\x1f]/',$value)) $fail('Isi rclone atau path lengkap binary rclone.'); }],
            'drive_config'=>['nullable','string','max:1000',function ($attribute,$value,$fail) { if ($value && (!preg_match('~^(?:/|[A-Za-z]:[\\\\/])~',$value) || preg_match('/[\x00-\x1f]/',$value))) $fail('Isi path absolut rclone.conf di VPS.'); }],
        ]);
        $values['drive_enabled'] = $request->boolean('drive_enabled');
        $values['drive_config'] = $values['drive_config'] ?? '';
        // An archived file still needs the old config to read its recorded remote.
        if (Schema::hasTable('drive_transfers') && DB::table('drive_transfers')->whereNotNull('verified_at')->exists()
            && ($values['drive_remote'] !== $settings->get('drive_remote') || $values['drive_config'] !== $settings->get('drive_config'))) {
            throw ValidationException::withMessages(['drive_remote'=>'Remote dan path konfigurasi terkunci setelah ada arsip. Pertahankan koneksi tersebut agar file lama tetap dapat dibaca.']);
        }
        $changed = collect(['drive_remote','drive_folder','drive_binary','drive_config'])->contains(fn ($key) => $values[$key] !== $settings->get($key));
        if ($values['drive_enabled'] && ($changed || $settings->get('drive_connection_status') !== 'ok')) {
            throw ValidationException::withMessages(['drive_enabled'=>'Simpan koneksi dengan otomatis nonaktif, lalu klik Tes Koneksi. Aktifkan pemindahan setelah tes berhasil.']);
        }
        if ($changed) { $values['drive_connection_status'] = null; $values['drive_checked_at'] = null; }
        $settings->save($values);
        return $this->back()->with('success','Setting Google Drive tersimpan.');
    }

    public function test(SystemSettings $settings, RcloneDrive $drive)
    {
        try {
            $drive->testConnection();
            $settings->save(['drive_connection_status'=>'ok','drive_checked_at'=>now()->toIso8601String()]);
            return $this->back()->with('success','Koneksi Google Drive berhasil.');
        } catch (\Throwable $e) {
            $settings->save(['drive_connection_status'=>'failed','drive_checked_at'=>now()->toIso8601String()]);
            return $this->back()->withErrors(['drive'=>'Tes rclone gagal. Pastikan remote bertipe Google Drive, konfigurasi terbaca oleh PHP, dan koneksi Drive tersedia.']);
        }
    }

    public function start(SystemSettings $settings)
    {
        if (!$settings->get('drive_enabled')) throw ValidationException::withMessages(['drive'=>'Aktifkan pemindahan otomatis terlebih dahulu.']);
        Artisan::call('drive:archive-released');
        return $this->back()->with('success','File released dijadwalkan untuk dipindahkan. Periksa progres setelah worker berjalan.');
    }

    private function back() { return redirect()->route('admin.system-settings.index',['tab'=>'google-drive']); }
}
