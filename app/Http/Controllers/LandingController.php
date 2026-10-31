<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    private function catalogue()
    {
        return Release::query()->where('status', 'released')
            ->whereRaw('COALESCE(finalreleaseddate, releaseddate) <= ?', [today()->toDateString()]);
    }

    private function artwork(Release $release): ?string
    {
        foreach ([$release->thumbnail, $release->cover] as $file) {
            if (!$file || $file->disk !== 'local' || !$file->identifier) continue;
            $root = realpath(Storage::disk('local')->path(''));
            $path = realpath(Storage::disk('local')->path($file->identifier));
            if (!$root || !$path || !str_starts_with($path, $root.DIRECTORY_SEPARATOR) || !is_file($path)) continue;
            if (in_array(@getimagesize($path)['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)) return $path;
        }
        return null;
    }

    public function index(SystemSettings $settings)
    {
        $releases = $this->catalogue()->with(['cover', 'thumbnail'])
            ->orderByRaw('COALESCE(finalreleaseddate, releaseddate) DESC')->orderByDesc('id')->limit(6)->get();
        foreach ($releases as $release) {
            $release->landing_artwork = $this->artwork($release) ? route('landing.cover', $release->id) : null;
            $release->landing_artist = collect($release->primaryartists)->pluck('name')->filter()->implode(', ');
        }
        $phone = preg_replace('/\D+/', '', $settings->get('whatsapp_number') ?: $settings->get('company_phone'));
        if (str_starts_with($phone, '0')) $phone = '62'.substr($phone, 1);
        return view('landing', [
            'releases' => $releases,
            'email' => $settings->get('company_email'),
            'address' => $settings->get('company_address'),
            'whatsapp' => $phone ? 'https://wa.me/'.$phone : null,
        ]);
    }

    public function cover(int $release)
    {
        $item = $this->catalogue()->with(['cover', 'thumbnail'])->findOrFail($release);
        $path = $this->artwork($item);
        abort_unless($path, 404);
        return response()->file($path, ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
