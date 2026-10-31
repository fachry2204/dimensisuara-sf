<?php

namespace App\Services\Legacy;

use RuntimeException;

class ImportPaths
{
    public function inside(string $path, string $root): string
    {
        $resolved = realpath($path);
        $base = realpath($root);
        if ($resolved === false || $base === false || !is_readable($resolved)) {
            throw new RuntimeException('Folder atau file sumber tidak ditemukan atau tidak dapat dibaca.');
        }
        $normalize = static fn ($p) => PHP_OS_FAMILY === 'Windows' ? strtolower(str_replace('\\', '/', $p)) : $p;
        $prefix = rtrim($normalize($base), '/').'/';
        if (!str_starts_with($normalize($resolved).'/', $prefix)) {
            throw new RuntimeException('Lokasi sumber berada di luar folder hosting yang diizinkan.');
        }
        return $resolved;
    }

    public function mediaRoot(string $root): string
    {
        if (trim($root) === '' || !preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $root)) {
            throw new RuntimeException('Isi path absolut folder media pada server.');
        }
        $path = $this->inside($root, config('legacy-import.allowed_root'));
        if (!is_dir($path)) {
            throw new RuntimeException('Sumber media harus berupa folder.');
        }
        return $path;
    }

    public function media(string $reference, string $root): string
    {
        $root = $this->mediaRoot($root);
        // URLs supply a path only: never fetch a URL or contact the old website.
        $path = parse_url($reference, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            throw new RuntimeException('Referensi media tidak berisi lokasi file.');
        }
        $path = str_replace('\\', '/', rawurldecode($path));
        if (str_contains($path, "\0") || preg_match('~(^|/)\.\.(/|$)|^[a-zA-Z]:~', $path)) {
            throw new RuntimeException('Referensi media tidak valid.');
        }
        $file = $this->inside($root.DIRECTORY_SEPARATOR.ltrim($path, '/'), $root);
        if (!is_file($file)) {
            throw new RuntimeException('Sumber media bukan file.');
        }
        return $file;
    }
}
