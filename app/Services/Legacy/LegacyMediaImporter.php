<?php

namespace App\Services\Legacy;

use App\Models\File;
use App\Models\LegacyImportRun;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LegacyMediaImporter
{
    /** One source record per job bounds memory and allows progress/retry between WAV files. */
    public function importRecord(LegacyImportRun $run, object $record): void
    {
        $row = unserialize(Crypt::decryptString($record->payload), ['allowed_classes' => false]);
        if (!is_array($row)) {
            throw new RuntimeException('Arsip pemetaan media tidak valid.');
        }
        $fields = $record->entity === 'releases'
            ? ['cover_art' => ['cover', ($row['cover_art'] ?? null) ?: ($row['cover_url'] ?? null)]]
            : ['audio_file' => ['full', $row['audio_file'] ?? null], 'audio_clip' => ['trimmed', $row['audio_clip'] ?? null], 'ipl_file' => ['ipl', $row['ipl_file'] ?? null]];
        foreach ($fields as $field => [$type, $reference]) {
            if (!$reference) {
                continue;
            }
            $key = ['run_id' => $run->id, 'record_id' => $record->id, 'field' => $field];
            try {
                $owner = $record->entity === 'releases' ? Release::find($record->target_id) : Track::find($record->target_id);
                // IPL is a release document in the new schema.
                if ($type === 'ipl' && $owner) {
                    $owner = $owner->releases()->first();
                }
                if (!$owner) {
                    throw new RuntimeException('Pemilik media tidak ditemukan di database baru.');
                }
                $source = app(ImportPaths::class)->media($reference, $run->media_root);
                $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($source);
                $allowed = match ($type) {
                    'cover' => in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) && @getimagesize($source),
                    'ipl' => $extension === 'pdf' && $mime === 'application/pdf',
                    'full', 'trimmed' => in_array($extension, ['wav', 'flac', 'mp3', 'm4a', 'ogg'], true) && in_array($mime, ['audio/x-wav', 'audio/wav', 'audio/vnd.wave', 'audio/x-flac', 'audio/flac', 'audio/mpeg', 'audio/mp4', 'audio/ogg', 'application/ogg'], true),
                    default => false,
                };
                if (!$allowed) {
                    throw new RuntimeException('Format file tidak sesuai jenis media.');
                }
                $hash = hash_file('sha256', $source);
                if (!$hash) {
                    throw new RuntimeException('Checksum sumber tidak dapat dibaca.');
                }
                $existing = $owner->morphMany(File::class, 'owner')->where('type', $type)->first();
                if ($existing) {
                    if ($existing->disk !== 'local' || !Storage::disk('local')->exists($existing->identifier)
                        || hash_file('sha256', Storage::disk('local')->path($existing->identifier)) !== $hash) {
                        throw new RuntimeException('Media tujuan sudah ada dan berbeda atau hilang; tidak ditimpa.');
                    }
                    $file = $existing;
                } else {
                    // Content-addressed name: retries reuse verified bytes; originals remain untouched.
                    $identifier = 'legacy-media/'.$hash.'.'.$extension;
                    $disk = Storage::disk('local');
                    if (!$disk->exists($identifier) || hash_file('sha256', $disk->path($identifier)) !== $hash) {
                        $temporary = $identifier.'.'.bin2hex(random_bytes(8)).'.part';
                        $stream = fopen($source, 'rb');
                        try {
                            if (!$stream || !$disk->put($temporary, $stream)
                                || hash_file('sha256', $disk->path($temporary)) !== $hash
                                || filesize($source) !== $disk->size($temporary)
                                || !$disk->move($temporary, $identifier)) {
                                throw new RuntimeException('Penyalinan atau verifikasi checksum file gagal.');
                            }
                        } finally {
                            if (is_resource($stream)) fclose($stream);
                            $disk->delete($temporary);
                        }
                    }
                    // Each owner gets its own physical file: deleting one release must not break another.
                    $ownedIdentifier = 'legacy-media/'.($owner instanceof Release ? 'releases' : 'tracks').'/'.$owner->id.'/'.$type.'-'.$hash.'.'.$extension;
                    if (!$disk->exists($ownedIdentifier) && !$disk->copy($identifier, $ownedIdentifier)) {
                        throw new RuntimeException('Tidak dapat menyimpan file untuk pemilik media.');
                    }
                    if (hash_file('sha256', $disk->path($ownedIdentifier)) !== $hash) {
                        throw new RuntimeException('Checksum file tujuan tidak cocok.');
                    }
                    $file = File::create([
                        'disk' => 'local', 'identifier' => $ownedIdentifier,
                        'original_name' => basename($source), 'mime_type' => $mime,
                        'size' => filesize($source), 'uploaded_at' => now(), 'type' => $type,
                        'owner_id' => $owner->id, 'owner_type' => $owner->getMorphClass(), 'profileid' => $owner->profileid,
                    ]);
                    $disk->delete($identifier);
                }
                // The UI uses a separate thumbnail relation. Copy original cover without distorting it.
                if ($type === 'cover' && !$owner->thumbnail()->exists()) {
                    $thumbnailPath = 'legacy-media/thumbnails/'.$owner->id.'-'.$hash.'.'.$extension;
                    if (!Storage::disk('local')->copy($file->identifier, $thumbnailPath)) {
                        throw new RuntimeException('Sampul tersimpan, tetapi salinan pratinjau gagal. Coba ulang media.');
                    }
                    File::create(array_merge($file->only(['disk', 'original_name', 'mime_type', 'size', 'uploaded_at', 'owner_id', 'owner_type', 'profileid']), ['identifier' => $thumbnailPath, 'type' => 'thumbnail']));
                }
                DB::table('legacy_import_media_results')->updateOrInsert($key, ['status' => 'copied', 'message' => 'Checksum cocok.', 'file_id' => $file->id, 'sha256' => $hash, 'created_at' => now(), 'updated_at' => now()]);
            } catch (RuntimeException $e) {
                DB::table('legacy_import_media_results')->updateOrInsert($key, ['status' => 'issue', 'message' => $e->getMessage(), 'file_id' => null, 'sha256' => null, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
