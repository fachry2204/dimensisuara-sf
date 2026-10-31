<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BrandingAssets
{
    public function path(?string $stored): ?string
    {
        if (!$stored || !preg_match('#^(uploads/)?branding/(logo|favicon)-[a-f0-9-]+\.(png|jpg|jpeg|webp|ico)$#D', $stored)) return null;
        $directory = str_starts_with($stored, 'uploads/') ? public_path('uploads/branding') : config('system-settings.branding_path', storage_path('app/public/branding'));
        $root = realpath($directory);
        $path = realpath($directory.DIRECTORY_SEPARATOR.basename($stored));
        if (!$root || !$path || !str_starts_with($path, $root.DIRECTORY_SEPARATOR) || !is_file($path) || !is_readable($path)) return null;
        return $path;
    }

    public function store(string $kind, UploadedFile $file): string
    {
        $directory = config('system-settings.branding_path', storage_path('app/public/branding'));
        File::ensureDirectoryExists($directory, 0755);
        $filename = $kind.'-'.Str::uuid().'.'.$file->extension();
        $file->move($directory, $filename);
        $stored = 'branding/'.$filename;
        if (!$this->path($stored)) throw new \RuntimeException('Berkas gambar tidak dapat disimpan. Periksa izin tulis folder storage aplikasi.');
        return $stored;
    }

    public function delete(?string $stored): void
    {
        if ($path = $this->path($stored)) File::delete($path);
    }

    public function version(string $stored): string { return substr(hash('sha256', $stored), 0, 20); }

    public function url(string $kind, string $stored): string
    {
        return route('branding.show', ['kind'=>$kind,'version'=>$this->version($stored)]);
    }
}
