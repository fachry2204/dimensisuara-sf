<?php

namespace App\Services;

use App\Models\File;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReleasedFilesDrive
{
    public function queuePublished(int $limit = 500): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('drive_transfers','layout_version')) throw new RuntimeException('Pembaruan riwayat folder Google Drive belum dipasang. Jalankan migrasi penataan folder.');
        $files = $this->publishedFiles()->whereIn('disk',['local','rclone'])->select('filestorage.*')
            ->leftJoin('drive_transfers','filestorage.id','=','drive_transfers.file_id')
            ->where(fn ($query) => $query->where('filestorage.disk','local')->orWhereNull('drive_transfers.layout_version')
                ->orWhere('drive_transfers.layout_version','<',DriveArchivePath::VERSION)->orWhereIn('drive_transfers.status',['cleanup_pending','remote_cleanup_pending','relocation_failed']))
            ->orderByRaw('COALESCE(drive_transfers.updated_at, filestorage.created_at) ASC')->orderBy('filestorage.id')->limit($limit)->get();
        foreach ($files as $file) {
            if ($file->disk === 'local') {
                $transfer=DB::table('drive_transfers')->where('file_id',$file->id)->first();
                if (($transfer->status ?? '') !== 'copying') DB::table('drive_transfers')->updateOrInsert(['file_id'=>$file->id],[
                    'local_identifier'=>$file->identifier,'status'=>'pending','error'=>null,'created_at'=>$transfer->created_at ?? now(),'updated_at'=>now(),
                ]);
            }
            \App\Jobs\ArchiveReleasedFile::dispatch($file->id);
        }
        return $files->count();
    }
    public function eligible()
    {
        return $this->publishedFiles()->where('disk','local');
    }

    public function publishedFiles()
    {
        return File::query()->whereNotNull('identifier')->where('identifier','!=','')
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereIn('type',['cover','thumbnail','ipl'])->whereHasMorph('owner',[Release::class],fn ($r) => $r->where('status','released'));
                })->orWhere(function ($q) {
                    $q->whereIn('type',['full','trimmed'])->whereHasMorph('owner',[Track::class],fn ($t) => $t
                        ->whereHas('releases',fn ($r) => $r->where('status','released')));
                });
            });
    }

    public function archive(int $id): void
    {
        if (!app(SystemSettings::class)->get('drive_enabled')) return;
        if (!\Illuminate\Support\Facades\Schema::hasColumn('drive_transfers','layout_version')) return;
        $db = DB::connection();
        $mysql = $db->getDriverName() === 'mysql';
        $lock = 'drive_file_'.$id;
        if ($mysql && !(int) $db->selectOne('SELECT GET_LOCK(?, 0) AS acquired',[$lock])->acquired) return;
        try {
            $file = File::find($id);
            if (!$file) return;
            $pending = DB::table('drive_transfers')->where('file_id',$id)->first();
            if ($file->disk === 'rclone') {
                if ($pending && filled($pending->local_identifier) && $pending->status === 'cleanup_pending') $this->cleanup($file,$pending);
                $pending = DB::table('drive_transfers')->where('file_id',$id)->first();
                if ($pending && filled($pending->previous_remote_identifier ?? null)) $this->cleanupOldRemote($file,$pending);
                if ((int)($pending->layout_version ?? 0) < DriveArchivePath::VERSION && $this->publishedFiles()->whereKey($id)->exists()) $this->relocate($file,$pending);
                return;
            }
            if (!$this->eligible()->whereKey($id)->exists()) return;
            $source = $file->identifier;
            $local = $this->localPath($source);
            $size = filesize($local);
            $md5 = md5_file($local);
            if ($md5 === false || $size === false) throw new RuntimeException('File lokal tidak dapat dibaca.');
            $remote = app(DriveArchivePath::class)->destination($file,$md5);
            DB::table('drive_transfers')->updateOrInsert(['file_id'=>$id],[
                'local_identifier'=>$source,'remote_identifier'=>$remote,'status'=>'copying','size'=>$size,'md5'=>$md5,
                'attempts'=>($pending->attempts ?? 0)+1,'error'=>null,'created_at'=>$pending->created_at ?? now(),'updated_at'=>now(),
            ]);
            $client = app(RcloneDrive::class);
            $client->run(['copyto',$local,$remote,'--checksum','--immutable']);
            $stat = $client->stat($remote);
            if ((int) ($stat['Size'] ?? -1) !== $size || strtolower($stat['Hashes']['MD5'] ?? '') !== $md5
                || md5_file($local) !== $md5 || filesize($local) !== $size) {
                throw new RuntimeException('Ukuran atau checksum MD5 tidak cocok. File lokal dipertahankan.');
            }
            // Recheck current status and pointer after upload. Never delete on stale eligibility.
            DB::transaction(function () use ($id,$source,$remote,$size) {
                $locked = File::lockForUpdate()->find($id);
                if (!$locked || $locked->disk !== 'local' || $locked->identifier !== $source || !$this->eligible()->whereKey($id)->exists()) {
                    throw new RuntimeException('Status rilis atau file berubah saat proses. File lokal dipertahankan.');
                }
                $locked->update(['disk'=>'rclone','identifier'=>$remote,'size'=>$size]);
                DB::table('drive_transfers')->where('file_id',$id)->update(['status'=>'cleanup_pending','verified_at'=>now(),'updated_at'=>now(),'layout_version'=>DriveArchivePath::VERSION]);
            });
            $this->cleanup($file->fresh(),DB::table('drive_transfers')->where('file_id',$id)->first());
        } catch (\Throwable $e) {
            // Raw process/SQL exception text must not reach the settings UI.
            $safe = $e instanceof RuntimeException && !$e instanceof \Illuminate\Database\QueryException ? $e->getMessage() : 'Pemindahan gagal. File lokal dipertahankan; periksa izin worker dan koneksi.';
            $current = File::find($id);
            if ($current) {
                $existing = DB::table('drive_transfers')->where('file_id',$id)->first();
                DB::table('drive_transfers')->updateOrInsert(['file_id'=>$id],[
                    'local_identifier'=>$existing->local_identifier ?? ($current->disk === 'local' ? $current->identifier : ''),
                    'status'=>$current->disk === 'rclone' ? (filled($existing->previous_remote_identifier ?? null) ? 'remote_cleanup_pending' : (($existing->layout_version ?? 0) < DriveArchivePath::VERSION ? 'relocation_failed' : 'cleanup_pending')) : 'failed',
                    'error'=>mb_substr($safe,0,500),'updated_at'=>now(),'created_at'=>$existing->created_at ?? now(),
                ]);
            }
        } finally {
            if ($mysql) $db->select('SELECT RELEASE_LOCK(?)',[$lock]);
        }
    }

    private function verify(array $stat, int $size, string $md5): void
    {
        if ((int)($stat['Size'] ?? -1) !== $size || strtolower($stat['Hashes']['MD5'] ?? '') !== strtolower($md5)) {
            throw new RuntimeException('Ukuran atau checksum salinan Drive tidak cocok. Sumber dipertahankan.');
        }
    }

    private function relocate(File $file, ?object $transfer): void
    {
        $client = app(RcloneDrive::class);
        $source = $file->identifier;
        $stat = $client->stat($source);
        $md5 = strtolower($stat['Hashes']['MD5'] ?? ''); $size = (int)($stat['Size'] ?? -1);
        if ($size < 0 || !preg_match('/^[a-f0-9]{32}$/D',$md5)) throw new RuntimeException('Sumber Drive belum dapat diverifikasi untuk penataan folder.');
        $destination = app(DriveArchivePath::class)->destination($file,$md5);
        if ($source === $destination) {
            DB::table('drive_transfers')->updateOrInsert(['file_id'=>$file->id],['local_identifier'=>$transfer->local_identifier ?? '', 'remote_identifier'=>$source,'size'=>$size,'md5'=>$md5,'verified_at'=>now(),'status'=>'complete','layout_version'=>DriveArchivePath::VERSION,'updated_at'=>now(),'created_at'=>$transfer->created_at ?? now()]);
            return;
        }
        DB::table('drive_transfers')->updateOrInsert(['file_id'=>$file->id],['local_identifier'=>$transfer->local_identifier ?? '', 'remote_identifier'=>$source,'status'=>'relocating','error'=>null,'attempts'=>($transfer->attempts ?? 0)+1,'updated_at'=>now(),'created_at'=>$transfer->created_at ?? now()]);
        $client->run(['copyto',$source,$destination,'--checksum','--immutable']);
        $this->verify($client->stat($destination),$size,$md5);
        DB::transaction(function () use ($file,$source,$destination,$size,$md5) {
            $locked = File::lockForUpdate()->findOrFail($file->id);
            if ($locked->disk !== 'rclone' || $locked->identifier !== $source || !$this->publishedFiles()->whereKey($file->id)->exists()) throw new RuntimeException('Data file berubah saat penataan. Lokasi lama dipertahankan.');
            $locked->update(['identifier'=>$destination,'size'=>$size]);
            DB::table('drive_transfers')->where('file_id',$file->id)->update(['remote_identifier'=>$destination,'previous_remote_identifier'=>$source,'size'=>$size,'md5'=>$md5,'verified_at'=>now(),'status'=>'remote_cleanup_pending','layout_version'=>DriveArchivePath::VERSION,'updated_at'=>now()]);
        });
        $this->cleanupOldRemote($file->fresh(),DB::table('drive_transfers')->where('file_id',$file->id)->first());
    }

    private function cleanupOldRemote(File $file, object $transfer): void
    {
        if (!filled($transfer->previous_remote_identifier ?? null)) return;
        if ($file->identifier !== $transfer->remote_identifier) throw new RuntimeException('Lokasi file berubah; folder lama belum dibersihkan.');
        $client = app(RcloneDrive::class);
        $this->verify($client->stat($file->identifier),(int)$transfer->size,$transfer->md5);
        if (File::whereKeyNot($file->id)->where('disk','rclone')->where('identifier',$transfer->previous_remote_identifier)->exists()) throw new RuntimeException('Lokasi lama masih dipakai file lain; pembersihan menunggu.');
        $client->deleteFile($transfer->previous_remote_identifier);
        // Only remove known legacy directories, and only when rclone confirms they are empty.
        if (preg_match('~^(.*:.*?/files)/[0-9]+/[^/]+$~u',$transfer->previous_remote_identifier,$match)) {
            try { $client->run(['rmdir',dirname($transfer->previous_remote_identifier)]); } catch (\Throwable) {}
            try { $client->run(['rmdir',$match[1]]); } catch (\Throwable) {}
        }
        DB::table('drive_transfers')->where('file_id',$file->id)->update(['previous_remote_identifier'=>null,'status'=>'complete','error'=>null,'updated_at'=>now()]);
    }

    private function localPath(string $identifier): string
    {
        if ($identifier === '' || str_contains($identifier,'..') || preg_match('~^(?:[A-Za-z]:|[/\\\\])~',$identifier)) throw new RuntimeException('Lokasi file lokal tidak valid.');
        $disk = Storage::disk('local');
        $base = realpath($disk->path(''));
        $path = realpath($disk->path($identifier));
        if (!$path || !$base || !str_starts_with($path,$base.DIRECTORY_SEPARATOR) || !is_file($path) || !is_readable($path)) throw new RuntimeException('File lokal hilang atau tidak dapat dibaca.');
        return $path;
    }

    private function cleanup(File $file, object $transfer): void
    {
        // Recover safely if a worker died after saving the Drive pointer but before unlink.
        $stat = app(RcloneDrive::class)->stat($file->identifier);
        if ((int) $stat['Size'] !== (int) $transfer->size || strtolower($stat['Hashes']['MD5'] ?? '') !== $transfer->md5) throw new RuntimeException('Verifikasi Google Drive gagal. Salinan lokal tidak dihapus.');
        $disk = Storage::disk('local');
        if ($disk->exists($transfer->local_identifier)) {
            $path = $this->localPath($transfer->local_identifier);
            if (md5_file($path) !== $transfer->md5) throw new RuntimeException('Salinan lokal berubah; tidak dihapus.');
            if (File::where('disk','local')->where('identifier',$transfer->local_identifier)->exists()) throw new RuntimeException('File lokal masih dipakai data lain; salinan dipertahankan.');
            if (!$disk->delete($transfer->local_identifier)) throw new RuntimeException('File tersimpan di Drive, tetapi penghapusan lokal gagal.');
        }
        DB::table('drive_transfers')->where('file_id',$file->id)->update(['status'=>'complete','error'=>null,'updated_at'=>now()]);
    }
}
