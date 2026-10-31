<?php

namespace App\Http\Controllers;

use App\Services\BrandingAssets;
use App\Services\SystemSettings;

class BrandingController extends Controller
{
    public function show(string $kind, string $version, SystemSettings $settings, BrandingAssets $assets)
    {
        $stored = $settings->get($kind);
        abort_unless(is_string($stored) && hash_equals($assets->version($stored), $version), 404);
        $path = $assets->path($stored);
        abort_unless($path, 404);
        $mime = mime_content_type($path);
        abort_unless(in_array($mime, ['image/png','image/jpeg','image/webp','image/x-icon','image/vnd.microsoft.icon'], true), 404);
        return response()->file($path, ['Content-Type'=>$mime,'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'public, max-age=86400']);
    }
}
