<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\Storage;

class LandingController extends Controller
{
    private const CATALOGUE_DATE = "COALESCE(NULLIF(finalreleaseddate, ''), NULLIF(releaseon, ''), NULLIF(releaseddate, ''))";

    private function catalogue()
    {
        // New releases use releaseon; releaseddate is the original date of re-releases.
        return Release::query()->where('status', 'released')
            ->select('releases.*')->selectRaw(self::CATALOGUE_DATE.' AS public_release_date')
            ->withCasts(['public_release_date'=>'date'])
            ->whereRaw(self::CATALOGUE_DATE.' <= ?', [today()->toDateString()])
            ->orderByRaw(self::CATALOGUE_DATE.' DESC')->orderByDesc('id');
    }

    private function artwork(Release $release): string|\App\Models\File|null
    {
        foreach ([$release->thumbnail, $release->cover] as $file) {
            if ($file && $file->disk === 'rclone' && in_array($file->mime_type, ['image/jpeg','image/png','image/webp'],true)) return $file;
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
            ->limit(6)->get();
        $this->prepareReleases($releases);
        $phone = preg_replace('/\D+/', '', $settings->get('whatsapp_number') ?: $settings->get('company_phone'));
        if (str_starts_with($phone, '0')) $phone = '62'.substr($phone, 1);
        return view('landing', [
            'releases' => $releases,
            'email' => $settings->get('company_email'),
            'address' => $settings->get('company_address'),
            'whatsapp' => $phone ? 'https://wa.me/'.$phone : null,
        ]);
    }

    public function releases(\Illuminate\Http\Request $request)
    {
        $input = $request->validate(['q'=>['nullable', 'string', 'max:120']]);
        $search = trim(preg_replace('/\s+/u', ' ', $input['q'] ?? ''));
        $query = $this->catalogue()->with(['cover','thumbnail']);
        // Match each keyword across title and artists, in any order, across all pages.
        foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($match) use ($like) {
                $match->whereRaw("LOWER(releases.title) LIKE ? ESCAPE '!'", [$like]);
                if (\App\Database\RelationalFields::available('releases')) {
                    $match->orWhereExists(function ($artists) use ($like) {
                        $artists->selectRaw('1')->from('release_artist_values')
                            ->whereColumn('owner_id', 'releases.id')->where('field', 'primaryartists')
                            ->where('key_name', 'name')->where('value_type', 'string')
                            ->whereRaw("LOWER(scalar_value) LIKE ? ESCAPE '!'", [$like]);
                    });
                } else {
                    $match->orWhereRaw("LOWER(releases.primaryartists) LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        $releases = $query->paginate(24)->appends($search !== '' ? ['q'=>$search] : []);
        $this->prepareReleases($releases);
        return view('catalogue',compact('releases', 'search'));
    }

    private function prepareReleases(iterable $releases): void
    {
        foreach ($releases as $release) {
            $release->landing_artwork = $this->artwork($release) ? route('landing.cover', $release->id) : null;
            $release->landing_artist = collect($release->primaryartists)->pluck('name')->filter()->implode(', ');
        }
    }

    public function cover(int $release, \Illuminate\Http\Request $request)
    {
        $item = $this->catalogue()->with(['cover', 'thumbnail'])->findOrFail($release);
        $path = $this->artwork($item);
        abort_unless($path, 404);
        if ($path instanceof \App\Models\File) return app(\App\Services\RcloneDrive::class)->response($path,$request,true);
        return response()->file($path, ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
