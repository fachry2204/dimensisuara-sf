<?php

namespace App\Services;

use App\Models\File;
use App\Models\Track;
use App\Models\User\User;
use App\Notifications\Admin\TrackBackupFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class TrackFileBackupService
{
    public const BACKUP_DISK = 'ftp';

    public const BACKUP_DIR = 'tracks';

    public const AGE_DAYS = 7;

    public const ALERT_THRESHOLD = 3;

    public function eligibleFiles()
    {
        return File::query()
            ->when(app(SystemSettings::class)->get('drive_enabled'), fn ($q) => $q->whereRaw('1 = 0'))
            ->where('disk', 'local')
            ->whereIn('type', ['full', 'trimmed'])
            ->whereNotNull('identifier')
            ->where('identifier', '!=', '')
            ->whereNull('backup_identifier')
            ->where('uploaded_at', '<', now()->subDays(self::AGE_DAYS))
            ->whereHasMorph('owner', [Track::class], function ($query) {
                $query->whereHas('releases', function ($releaseQuery) {
                    $releaseQuery->where('status', 'released');
                });
            })
            ->with('owner');
    }

    public function backup(File $file): bool
    {
        $db = \Illuminate\Support\Facades\DB::connection();
        $mysql = $db->getDriverName() === 'mysql';
        $lock = 'drive_file_'.$file->id;
        if ($mysql && !(int) $db->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired) return false;
        try {
            app(SystemSettings::class)->refresh();
            $file->refresh();
            return $this->backupLocked($file);
        } finally {
            if ($mysql) $db->select('SELECT RELEASE_LOCK(?)', [$lock]);
        }
    }

    private function backupLocked(File $file): bool
    {
        if (app(SystemSettings::class)->get('drive_enabled') || $file->disk !== 'local') return false;
        if (! in_array($file->type, ['full', 'trimmed'], true)) {
            return false;
        }

        if (! $file->identifier || $file->backup_identifier) {
            return false;
        }

        $track = $file->owner;

        if (! $track instanceof Track) {
            return false;
        }

        $localDisk = Storage::disk('local');
        $localPath = str_replace('\\', '/', $file->identifier);

        if (! $this->isValidIdentifier($localPath)) {
            $this->markFailed($file, 'Invalid local identifier.');

            return false;
        }

        if (! $localDisk->exists($localPath)) {
            $this->markFailed($file, 'Local file missing.');

            return false;
        }

        $localRealPath = $localDisk->path($localPath);
        $expectedHash = hash_file('sha256', $localRealPath);

        $remotePath = $this->buildRemotePath($file);
        $remoteDisk = Storage::disk(self::BACKUP_DISK);

        $stream = fopen($localRealPath, 'rb');

        if ($stream === false) {
            $this->markFailed($file, 'Unable to open local file.');

            return false;
        }

        try {
            $uploaded = $remoteDisk->writeStream($remotePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $uploaded) {
            $this->markFailed($file, 'Upload to FTP failed.');

            return false;
        }

        $remoteHash = $this->hashRemote($remoteDisk, $remotePath);

        if ($remoteHash === '') {
            $remoteDisk->delete($remotePath);

            $this->markFailed($file, 'Unable to read back uploaded file for verification.');

            return false;
        }

        if (! hash_equals($expectedHash, $remoteHash)) {
            $remoteDisk->delete($remotePath);

            $this->markFailed($file, 'SHA-256 verification failed.');

            return false;
        }

        $localDisk->delete($localPath);

        $file->update([
            'identifier' => null,
            'backup_identifier' => $remotePath,
            'backed_up_at' => now(),
            'backup_attempts' => 0,
            'backup_error' => null,
        ]);

        Log::info('Track file backed up.', [
            'file_id' => $file->id,
            'backup_identifier' => $remotePath,
            'size' => $file->size,
        ]);

        return true;
    }

    public function buildRemotePath(File $file): string
    {
        $track = $file->owner;
        $firstArtist = data_get($track->primaryartists, '0.name', 'Unknown Artist');
        $title = $track->title ?: 'Untitled';
        $suffix = $file->type === 'full' ? '(full)' : '(reff)';
        $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION)) ?: 'wav';

        $base = $this->sanitize("{$firstArtist} - {$title} {$suffix}");
        $remoteDisk = Storage::disk(self::BACKUP_DISK);

        $path = self::BACKUP_DIR.'/'."{$base}.{$ext}";

        $counter = 2;
        while ($remoteDisk->exists($path)) {
            $path = self::BACKUP_DIR.'/'."{$base} - {$counter}.{$ext}";
            $counter++;
        }

        return $path;
    }

    protected function sanitize(string $name): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name);

        return $name ?: 'track';
    }

    protected function hashRemote($remoteDisk, string $path): string
    {
        $stream = $remoteDisk->readStream($path);

        if (! is_resource($stream)) {
            return '';
        }

        $ctx = hash_init('sha256');
        hash_update_stream($ctx, $stream);
        fclose($stream);

        return hash_final($ctx);
    }

    protected function isValidIdentifier(string $identifier): bool
    {
        return $identifier !== ''
            && ! str_contains($identifier, '..')
            && ! str_starts_with($identifier, '/')
            && ! str_starts_with($identifier, '\\');
    }

    public function markFailed(File $file, string $message): void
    {
        $attempts = ($file->backup_attempts ?? 0) + 1;

        $file->update([
            'backup_attempts' => $attempts,
            'backup_error' => $message,
        ]);

        Log::error('Track file backup failed.', [
            'file_id' => $file->id,
            'attempts' => $attempts,
            'message' => $message,
        ]);

        if ($attempts === self::ALERT_THRESHOLD) {
            $this->alertAdmins($file, $attempts, $message);
        }
    }

    protected function alertAdmins(File $file, int $attempts, string $message): void
    {
        $admins = User::getByPermission('manage-releases-status');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new TrackBackupFailed($file, $attempts, $message));
    }
}
