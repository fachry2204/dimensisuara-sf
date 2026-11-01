<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaDeletion
{
    public function delete(File $file, callable $deleteRecord)
    {
        $db = DB::connection();
        $mysql = $db->getDriverName() === 'mysql';
        $lock = 'drive_file_'.$file->id;
        // Coordinate with the rclone archival worker before touching either copy.
        if ($mysql && !(int) $db->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lock])->acquired) {
            throw new RuntimeException('File sedang diproses oleh worker. Coba kembali.');
        }
        try {
            $file->refresh();
            $transfer = Schema::hasTable('drive_transfers') ? DB::table('drive_transfers')->where('file_id', $file->id)->first() : null;
            $pointers = [[$file->disk, $file->identifier]];
            if ($file->backup_identifier) $pointers[] = ['ftp', $file->backup_identifier];
            if ($transfer) {
                if ($transfer->remote_identifier) $pointers[] = ['rclone', $transfer->remote_identifier];
                if ($transfer->local_identifier) {
                    $local = Storage::disk('local');
                    $identifier = $transfer->local_identifier;
                    $this->validatePath($identifier);
                    if ($local->exists($identifier) && $transfer->md5 && md5_file($local->path($identifier)) !== $transfer->md5) {
                        throw new RuntimeException('Salinan lokal berubah; periksa file sebelum menghapus.');
                    }
                    $pointers[] = ['local', $identifier];
                }
            }
            $seen = [];
            foreach ($pointers as [$disk, $identifier]) {
                if (!$identifier || isset($seen[$disk.'|'.$identifier])) continue;
                $seen[$disk.'|'.$identifier] = true;
                $other = File::whereKeyNot($file->id)->where('disk', $disk)->where('identifier', $identifier)->exists();
                if ($disk === 'ftp') $other = $other || File::whereKeyNot($file->id)->where('backup_identifier', $identifier)->exists();
                if ($other) continue;
                if ($disk === 'rclone') {
                    app(RcloneDrive::class)->deleteFile($identifier);
                } else {
                    $this->validatePath($identifier);
                    if (!$disk || !config('filesystems.disks.'.$disk)) throw new RuntimeException('Disk penyimpanan tidak tersedia.');
                    if (!Storage::disk($disk)->delete($identifier)) throw new RuntimeException('File belum dapat dihapus dari penyimpanan.');
                }
            }
            if ($transfer) DB::table('drive_transfers')->where('file_id', $file->id)->delete();
            return $deleteRecord();
        } finally {
            if ($mysql) $db->select('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function validatePath(string $path): void
    {
        if ($path === '' || preg_match('~(^|[/\\\\])\.\.([/\\\\]|$)|^[A-Za-z]:|^[/\\\\]|[\x00-\x1f]~', $path)) {
            throw new RuntimeException('Lokasi file tidak valid.');
        }
    }
}
