<?php

namespace App\Console\Commands;

use App\Models\Release;
use App\Services\RcloneDrive;
use Illuminate\Console\Command;

class InspectReleaseArtwork extends Command
{
    protected $signature = 'drive:inspect-artwork {release : ID rilisan}';
    protected $description = 'Check release artwork locations without moving or deleting files';

    public function handle(RcloneDrive $drive): int
    {
        $release=Release::with(['thumbnail','cover'])->find($this->argument('release'));
        if (!$release) { $this->error('Rilisan tidak ditemukan.'); return self::FAILURE; }
        $failed=false;
        foreach (['thumbnail','cover'] as $type) {
            $file=$release->$type;
            if (!$file) { $this->line($type.': tidak ada record file.'); continue; }
            $this->line($type.' #'.$file->id.' | disk: '.$file->disk.' | MIME database: '.($file->mime_type ?: '-'));
            if ($file->disk!=='rclone') { $this->line('Tidak berada pada Google Drive.'); continue; }
            try {
                $stat=$drive->stat($file->identifier);
                $this->info('Akses Drive berhasil | MIME Drive: '.($stat['MimeType'] ?? '-').' | ukuran: '.(int)($stat['Size'] ?? 0).' byte');
            } catch (\App\Exceptions\DriveSetupException $exception) {
                $failed=true; $this->error($exception->getMessage());
            } catch (\Throwable) {
                $failed=true; $this->error('Metadata file Drive tidak dapat dibaca. Periksa lokasi file dan konfigurasi rclone aplikasi.');
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
