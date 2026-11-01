<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;

class SystemSettings
{
    private ?array $stored = null;
    private array $defaults;
    private ?string $appliedHash = null;

    public function __construct()
    {
        $this->defaults = [
            'drive_enabled' => false,
            'drive_remote' => 'gdrive',
            'drive_folder' => 'DimensiSuara',
            'drive_binary' => 'rclone',
            'drive_config' => '',
            'drive_connection_status' => null,
            'drive_checked_at' => null,
            'app_name' => 'SoundFresh',
            'logo' => null,
            'favicon' => null,
            'aggregator_names' => ['Believe', 'SoundOn', 'Lokamusik'],
            'aggregator_contract_prefix' => 'PKS-DMSV',
            'primary_color_mode' => 'solid',
            'primary_color' => '#c2410c',
            'primary_gradient_color' => '#9a3412',
            'primary_gradient_angle' => 110,
            'company_name' => 'PT DIGITAL MASIF SUARA VISUAL',
            'company_address' => 'Jl. Ciledug Raya No. 4D, RT 010/RW 002, Kelurahan Petukangan Utara, Kecamatan Pesanggrahan, Kota Jakarta Selatan, DKI Jakarta',
            'company_director' => 'FACHRY HIDAYAT',
            'company_position' => 'Direktur Utama',
            'company_signature' => null,
            'company_contact_name' => 'Yunita Afianti',
            'company_contact_address' => 'Jl. Ciledug Raya No. 4D, RT 010/RW 002, Kelurahan Petukangan Utara, Kecamatan Pesanggrahan, Kota Jakarta Selatan, DKI Jakarta',
            'company_phone' => '087735629645',
            'company_email' => 'yunitasoundfresh@gmail.com',
            'smtp_enabled' => config('mail.default') === 'smtp',
            'smtp_host' => config('mail.mailers.smtp.host') ?: 'smtp.gmail.com',
            'smtp_port' => config('mail.mailers.smtp.port') ?: 587,
            'smtp_encryption' => config('mail.mailers.smtp.encryption') ?: 'tls',
            'smtp_username' => config('mail.mailers.smtp.username'),
            'smtp_password' => config('mail.mailers.smtp.password'),
            'smtp_from_address' => config('mail.from.address'),
            'smtp_from_name' => strcasecmp(config('mail.from.name') ?? '', 'SoundFresh') === 0 ? null : config('mail.from.name'),
            'whatsapp_url' => config('system-settings.whatsapp.url'),
            'whatsapp_token' => config('system-settings.whatsapp.token'),
            'whatsapp_number' => config('system-settings.whatsapp.number'),
            'whatsapp_provider' => 'fonnte',
            'maintenance_enabled' => false,
            'maintenance_reason' => 'Kami sedang melakukan pemeliharaan sistem. Silakan kembali beberapa saat lagi.',
            'maintenance_roles' => [],
            'maintenance_customers' => false,
            'maintenance_guests' => false,
        ];
    }

    public function all(): array
    {
        if ($this->stored === null) {
            $this->stored = [];
            if (Schema::hasTable('system_settings')) {
                if (\App\Database\RelationalFields::available('system_settings')) {
                    foreach (\App\Models\SystemSetting::with('relationalFieldRows')->get() as $row) $this->stored[$row->key] = $row->value;
                } else {
                    foreach (DB::table('system_settings')->get(['key', 'value']) as $row) {
                        $this->stored[$row->key] = unserialize(Crypt::decryptString($row->value), ['allowed_classes' => false]);
                    }
                }
            }
        }

        return array_replace($this->defaults, $this->stored);
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function publicValues(): array
    {
        $values = $this->all();
        $values['has_smtp_password'] = filled($values['smtp_password']);
        $values['has_whatsapp_token'] = filled($this->fonnteToken());
        unset($values['smtp_password'], $values['whatsapp_token']);

        return $values;
    }

    public function aggregatorNames(): array
    {
        return $this->get('aggregator_names');
    }

    public function fonnteToken(): ?string
    {
        $values = $this->all();
        $oldUrl = $this->stored['whatsapp_url'] ?? null;
        if (($this->stored['whatsapp_provider'] ?? null) !== 'fonnte' && $oldUrl && !preg_match('#^https://api\.fonnte\.com/?$#', $oldUrl)) return null;
        return $values['whatsapp_token'];
    }

    public function save(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                if (\App\Database\RelationalFields::available('system_settings')) {
                    \App\Models\SystemSetting::updateOrCreate(['key'=>$key], ['value'=>$value]);
                } else {
                    DB::table('system_settings')->upsert([
                        'key' => $key, 'value' => Crypt::encryptString(serialize($value)),
                        'created_at' => now(), 'updated_at' => now(),
                    ], ['key'], ['value', 'updated_at']);
                }
            }
        });
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->stored = null;
        $this->apply();
    }

    public function apply(): void
    {
        $values = $this->all();
        config([
            'app.name' => $values['app_name'],
            'mail.from.name' => $values['smtp_from_name'] ?: $values['app_name'],
        ]);
        if (array_key_exists('smtp_enabled', $this->stored)) {
            config(['mail.default' => $values['smtp_enabled'] ? 'smtp' : 'log']);
        }
        if (array_key_exists('smtp_host', $this->stored)) {
            config([
                'mail.mailers.smtp.url' => null,
                'mail.mailers.smtp.host' => $values['smtp_host'],
                'mail.mailers.smtp.port' => (int) $values['smtp_port'],
                'mail.mailers.smtp.encryption' => $values['smtp_encryption'],
                'mail.mailers.smtp.scheme' => $values['smtp_encryption'] === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.username' => $values['smtp_username'],
                'mail.mailers.smtp.password' => $values['smtp_password'],
                'mail.from.address' => $values['smtp_from_address'],
                'mail.from.name' => $values['smtp_from_name'] ?: $values['app_name'],
            ]);
        }
        config([
            'system-settings.whatsapp.url' => 'https://api.fonnte.com',
            'system-settings.whatsapp.provider' => $values['whatsapp_provider'],
            'system-settings.whatsapp.token' => $this->fonnteToken(),
            'system-settings.whatsapp.number' => $values['whatsapp_number'],
        ]);
        $hash = hash('sha256', serialize($values));
        if ($this->appliedHash !== null && $this->appliedHash !== $hash) {
            Mail::purge('smtp');
        }
        $this->appliedHash = $hash;
    }

    public function logoUrl(string $fallback = 'build/images/logo-soundfresh-full-white.webp'): string
    {
        $stored = $this->get('logo');
        $assets = app(BrandingAssets::class);
        return $assets->path($stored) ? $assets->url('logo', $stored) : asset($fallback);
    }

    public function faviconUrl(): string
    {
        $stored = $this->get('favicon');
        $assets = app(BrandingAssets::class);
        return $assets->path($stored) ? $assets->url('favicon', $stored) : asset('favicon.ico');
    }
}
