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
    public function eligible()
    {
        return File::query()->where('disk','local')->whereNotNull('identifier')->where('identifier','!=','')
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereIn('type',['cover','thumbnail','ipl'])->whereHasMorph('owner',[Release::class],fn ($r) => $r->where('status','released'));
                })->orWhere(function ($q) {
                    // A shared recording stays local while any linked release is not released.
                    $q->whereIn('type',['full','trimmed'])->whereHasMorph('owner',[Track::class],fn ($t) => $t
                        ->whereHas('releases',fn ($r) => $r->where('status','released'))
                        ->whereDoesntHave('releases',fn ($r) => $r->where('status','!=','released')->orWhereNull('status')));
                });
            });
    }

    public function archive(int $id): void
    {
        if (!app(SystemSettings::class)->get('drive_enabled')) return;
        $db = DB::connection();
        $mysql = $db->getDriverName() === 'mysql';
        $lock = 'drive_file_'.$id;
        if ($mysql && !(int) $db->selectOne('SELECT GET_LOCK(?, 0) AS acquired',[$lock])->acquired) return;
        try {
            $file = File::find($id);
            if (!$file) return;
            $pending = DB::table('drive_transfers')->where('file_id',$id)->first();
            if ($file->disk === 'rclone') {
                if ($pending && $pending->status !== 'complete') $this->cleanup($file,$pending);
                return;
            }
            if (!$this->eligible()->whereKey($id)->exists()) return;
            $source = $file->identifier;
            $local = $this->localPath($source);
            $size = filesize($local);
            $md5 = md5_file($local);
            if ($md5 === false || $size === false) throw new RuntimeException('File lokal tidak dapat dibaca.');
            $extension = strtolower(pathinfo($source,PATHINFO_EXTENSION));
            $remote = rtrim(app(RcloneDrive::class)->root(),'/').'/files/'.$id.'/'.$md5.($extension ? '.'.preg_replace('/[^a-z0-9]/','',$extension) : '');
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
                DB::table('drive_transfers')->where('file_id',$id)->update(['status'=>'cleanup_pending','verified_at'=>now(),'updated_at'=>now()]);
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
                    'status'=>$current->disk === 'rclone' ? 'cleanup_pending' : 'failed',
                    'error'=>mb_substr($safe,0,500),'updated_at'=>now(),'created_at'=>$existing->created_at ?? now(),
                ]);
            }
        } finally {
            if ($mysql) $db->select('SELECT RELEASE_LOCK(?)',[$lock]);
        }
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
