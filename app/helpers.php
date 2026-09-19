<?php

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

if(!function_exists('current_user')) {
    function current_user() {
        return session()->has('impersonate')
            ? \App\Models\User\User::find(session('impersonate'))
            : auth()->user();
    }
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