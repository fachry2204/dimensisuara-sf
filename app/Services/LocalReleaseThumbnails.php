<?php

namespace App\Services;

use App\Models\File;
use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use RuntimeException;

class LocalReleaseThumbnails
{
    public function queue(int $limit=500): int
    {
        $files=File::where('type','thumbnail')->where('disk','rclone')
            ->whereHasMorph('owner',[Release::class])->leftJoin('drive_transfers as dt','dt.file_id','=','filestorage.id')
            ->select('filestorage.*')->orderByRaw('COALESCE(dt.updated_at, filestorage.created_at) ASC')->orderBy('filestorage.id')->limit($limit)->get();
        foreach ($files as $file) \App\Jobs\ArchiveReleasedFile::dispatch($file->id);
        return $files->count();
    }

    public function restore(File $file): void
    {
        if ($file->type!=='thumbnail' || $file->disk!=='rclone' || !$file->owner instanceof Release) return;
        $disk=Storage::disk('local');
        $disk->makeDirectory('thumbnails/drive-restored');
        $root=realpath($disk->path(''));
        $directory=realpath($disk->path('thumbnails/drive-restored'));
        if (!$root || !$directory || !str_starts_with($directory,$root.DIRECTORY_SEPARATOR)) throw new RuntimeException('Folder thumbnail lokal tidak valid.');
        $input=tempnam($directory,'source-'); $output=tempnam($directory,'thumb-');
        if (!$input || !$output) {
            if ($input) @unlink($input); if ($output) @unlink($output);
            throw new RuntimeException('Folder thumbnail tidak dapat ditulis.');
        }
        try {
            $sources=collect([$file,$file->owner->cover])->filter()->unique('id');
            $downloaded=false;
            foreach ($sources as $source) {
                try {
                    if ($source->disk==='rclone') {
                        $client=app(RcloneDrive::class);
                        $stat=$client->stat($source->identifier);
                        $md5=strtolower($stat['Hashes']['MD5'] ?? '');
                        if (!preg_match('/^[a-f0-9]{32}$/D',$md5) || (int)($stat['Size'] ?? -1)<0) throw new RuntimeException('Checksum sumber thumbnail tidak tersedia.');
                        $client->run(['copyto',$source->identifier,$input,'--checksum']);
                        clearstatcache(true,$input);
                        if (filesize($input)!==(int)$stat['Size'] || md5_file($input)!==$md5) throw new RuntimeException('Checksum unduhan thumbnail tidak cocok.');
                    } elseif ($source->disk==='local') {
                        $local=realpath($disk->path($source->identifier));
                        if (!$local || !str_starts_with($local,$root.DIRECTORY_SEPARATOR) || !is_file($local) || !copy($local,$input)) throw new RuntimeException('Cover lokal tidak dapat dibaca.');
                    } else continue;
                    $mime=@getimagesize($input)['mime'] ?? '';
                    if (!in_array($mime,['image/jpeg','image/png','image/webp'],true)) throw new RuntimeException('Sumber bukan gambar thumbnail yang didukung.');
                    $downloaded=true;break;
                } catch (\Throwable) { continue; }
            }
            if (!$downloaded) throw new RuntimeException('Thumbnail dan cover sumber belum dapat diakses atau diverifikasi. Lokasi Drive dipertahankan.');
            (new ImageManager(new Driver))->read($input)->scaleDown(width:300,height:300)->toJpeg(85)->save($output);
            clearstatcache(true,$output);
            $md5=md5_file($output);$size=filesize($output);$image=@getimagesize($output);
            if (!$md5 || !$size || !$image || $image[0]>300 || $image[1]>300 || $image['mime']!=='image/jpeg') throw new RuntimeException('Thumbnail lokal belum berhasil diverifikasi.');
            $identifier='thumbnails/drive-restored/'.$file->id.'-'.$md5.'.jpeg';
            $target=$disk->path($identifier);
            if (is_link($target)) throw new RuntimeException('File tujuan thumbnail tidak valid.');
            if (is_file($target)) {
                if (md5_file($target)!==$md5) throw new RuntimeException('File tujuan thumbnail berbeda; tidak ditimpa.');
            } elseif (!rename($output,$target)) throw new RuntimeException('Thumbnail lokal tidak dapat disimpan.');
            DB::transaction(function () use ($file,$identifier,$md5,$size) {
                $current=File::lockForUpdate()->findOrFail($file->id);
                if ($current->disk!=='rclone' || $current->identifier!==$file->identifier || $current->type!=='thumbnail') throw new RuntimeException('Data thumbnail berubah; lokasi belum diperbarui.');
                $current->update(['disk'=>'local','identifier'=>$identifier,'mime_type'=>'image/jpeg','size'=>$size]);
                $transfer=DB::table('drive_transfers')->where('file_id',$file->id)->first();
                DB::table('drive_transfers')->updateOrInsert(['file_id'=>$file->id],[
                    'local_identifier'=>$identifier,'remote_identifier'=>$transfer->remote_identifier ?? $file->identifier,
                    'status'=>'thumbnail_local','size'=>$size,'md5'=>$md5,'error'=>null,'created_at'=>$transfer->created_at ?? now(),'updated_at'=>now(),
                ]);
            });
        } finally { if (is_file($input)) @unlink($input); if (is_file($output)) @unlink($output); }
    }
}
