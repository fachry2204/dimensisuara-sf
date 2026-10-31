<?php

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

function app_name(): string
{
    return app(\App\Services\SystemSettings::class)->get('app_name');
}

function app_logo(string $fallback = 'build/images/logo-soundfresh-full-white.webp'): string
{
    return app(\App\Services\SystemSettings::class)->logoUrl($fallback);
}

function app_favicon(): string
{
    return app(\App\Services\SystemSettings::class)->faviconUrl();
}

function company_name(): string
{
    return (string) app(\App\Services\SystemSettings::class)->get('company_name');
}

if(!function_exists('current_user')) {
    function current_user() {
        return auth()->user() instanceof \App\Models\User\Admin && auth()->user()->can('impersonate-users') && session()->has('impersonate')
            ? \App\Models\User\User::find(session('impersonate'))
            : auth()->user();
    }
}

function account_actor(?\App\Models\User\User $user): array
{
    return $user instanceof \App\Models\User\Admin
        ? ['user_id' => null, 'admin_user_id' => $user->id]
        : ['user_id' => $user?->id, 'admin_user_id' => null];
}

if(!function_exists('validateSignature')) {
    function validateSignature($signature)
    {
        // Create ImageManager instance
        $manager = new ImageManager(Driver::class);

        // Build signature image
        $signatureImage = $manager->read($signature);
        if($signatureImage) {

            // Create reference image
            $reference = $manager->create($signatureImage->width(), $signatureImage->height())->fill('ffffff');

            // Check signature image against reference
            $hash1 = $signatureImage->toJpeg()->toDataUri();
            $hash2 = $reference->toJpeg()->toDataUri();

            if($hash1 != $hash2) {
                return true;
            }
        }
        return false;
    }    
}

// if(!function_exists('getMonth')) {
//     function getMonth($month) {
//         return ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$month];
//     }
// }
