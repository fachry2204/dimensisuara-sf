<?php

namespace App\Console\Commands;

use App\Exceptions\DriveSetupException;
use App\Models\Release;
use App\Models\File;
use App\Services\RcloneDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RecoverReleaseArtwork extends Command
{
    protected $signature = 'drive:recover-artwork {release : ID rilisan} {--apply : Simpan lokasi setelah verifikasi}';
    protected $description = 'Find missing artwork by recorded MD5 and size; dry run unless --apply is supplied';

    public function handle(RcloneDrive $drive): int
    {
        if (!Schema::hasTable('drive_transfers') || !Schema::hasColumn('drive_transfers','layout_version')) {
            $this->error('Riwayat penataan Google Drive belum tersedia.'); return self::FAILURE;
        }
        $release=Release::with(['cover','thumbnail'])->find($this->argument('release'));
        if (!$release) { $this->error('Rilisan tidak ditemukan.'); return self::FAILURE; }
        $inventory=null; $failed=false;
        $this->info($this->option('apply') ? 'Mode terapkan: hanya lokasi database yang diperbarui; tidak menghapus file.' : 'Mode pemeriksaan: tidak mengubah data atau file.');
        foreach (['cover','thumbnail'] as $type) {
            $file=$release->$type;
            if (!$file || $file->disk!=='rclone') { $this->line($type.': tidak ada file Google Drive untuk dipulihkan.'); continue; }
            $db=DB::connection(); $mysql=$db->getDriverName()==='mysql'; $lock='drive_file_'.$file->id;
            if ($mysql && !(int)$db->selectOne('SELECT GET_LOCK(?, 0) AS acquired',[$lock])->acquired) {
                $failed=true; $this->error($type.': file sedang diproses worker. Coba kembali.'); continue;
            }
            try {
                $file->refresh();
                try { $drive->stat($file->identifier); $this->info($type.' #'.$file->id.': lokasi saat ini dapat diakses.'); continue; }
                catch (DriveSetupException $exception) {
                    if (!str_contains($exception->getMessage(),'File atau folder Google Drive tidak ditemukan')) throw $exception;
                }
                $transfer=DB::table('drive_transfers')->where('file_id',$file->id)->first();
                if (!$transfer || !preg_match('/^[a-f0-9]{32}$/iD',(string)$transfer->md5) || !isset($transfer->size) || $transfer->size<0 || filled($transfer->previous_remote_identifier)) {
                    throw new RuntimeException('Riwayat checksum/ukuran tidak lengkap atau masih ada pembersihan lama. Tidak mengubah lokasi.');
                }
                $root=rtrim($drive->root(),'/');
                if ($inventory===null) {
                    $this->line('Mencari metadata file dalam folder tujuan Drive; proses dapat memerlukan waktu.');
                    $inventory=json_decode($drive->run(['lsjson',$root,'--recursive','--files-only','--hash','--hash-type','MD5']),true,512,JSON_THROW_ON_ERROR);
                    if (!is_array($inventory) || !array_is_list($inventory)) throw new RuntimeException('Daftar metadata Drive tidak valid.');
                }
                $matches=[];
                foreach ($inventory as $entry) {
                    if (!is_array($entry) || ($entry['IsDir'] ?? true)) continue;
                    $hashes=array_change_key_case($entry['Hashes'] ?? [],CASE_UPPER);
                    if (strtolower($hashes['MD5'] ?? '')!==strtolower($transfer->md5) || (int)($entry['Size'] ?? -1)!==(int)$transfer->size) continue;
                    if (!in_array(strtolower($entry['MimeType'] ?? ''),['image/jpeg','image/png','image/webp'],true)) continue;
                    $path=$entry['Path'] ?? '';
                    if (!is_string($path) || $path==='' || str_starts_with($path,'/')) continue;
                    $remote=$root.'/'.$path;
                    $drive->validateRemote($remote);
                    $matches[]=$remote;
                }
                $owned=array_values(array_filter($matches,fn ($path)=>str_contains(basename($path),'(F'.$file->id.'-')));
                $matches=array_values(array_unique($owned ?: $matches));
                if (count($matches)!==1) throw new RuntimeException(count($matches) ? 'Ditemukan beberapa salinan identik; lokasi tidak diubah agar tidak salah memilih.' : 'Tidak ditemukan salinan dengan ukuran, MD5, dan tipe gambar yang cocok.');
                $target=$matches[0];
                $stat=$drive->stat($target);
                $hashes=array_change_key_case($stat['Hashes'] ?? [],CASE_UPPER);
                if ((int)($stat['Size'] ?? -1)!==(int)$transfer->size || strtolower($hashes['MD5'] ?? '')!==strtolower($transfer->md5)
                    || !in_array(strtolower($stat['MimeType'] ?? ''),['image/jpeg','image/png','image/webp'],true)) throw new RuntimeException('Verifikasi ulang salinan tidak cocok. Lokasi tidak diubah.');
                if ($this->option('apply')) {
                    DB::transaction(function () use ($file,$transfer,$target,$stat) {
                        $current=File::lockForUpdate()->findOrFail($file->id);
                        $journal=DB::table('drive_transfers')->where('file_id',$file->id)->lockForUpdate()->first();
                        if ($current->disk!=='rclone' || $current->identifier!==$file->identifier || !$journal
                            || $journal->md5!==$transfer->md5 || (int)$journal->size!==(int)$transfer->size || filled($journal->previous_remote_identifier)) throw new RuntimeException('Data file berubah. Jalankan pemeriksaan kembali.');
                        $current->update(['identifier'=>$target,'mime_type'=>strtolower($stat['MimeType']),'size'=>$transfer->size]);
                        DB::table('drive_transfers')->where('file_id',$file->id)->update(['remote_identifier'=>$target,'verified_at'=>now(),'status'=>'complete','layout_version'=>0,'error'=>null,'updated_at'=>now()]);
                    });
                    $this->info($type.' #'.$file->id.': lokasi dipulihkan ke salinan terverifikasi.');
                } else $this->info($type.' #'.$file->id.': satu salinan cocok dan terverifikasi; siap dipulihkan dengan --apply.');
            } catch (DriveSetupException $exception) {
                $failed=true; $this->error($type.' #'.$file->id.': '.$exception->getMessage());
            } catch (\Throwable $exception) {
                $failed=true;
                $this->error($type.' #'.$file->id.': '.($exception instanceof RuntimeException && !$exception instanceof \Illuminate\Database\QueryException ? $exception->getMessage() : 'Pemeriksaan gagal. Lokasi tidak diubah; periksa akses worker dan database.'));
            } finally { if ($mysql) $db->select('SELECT RELEASE_LOCK(?)',[$lock]); }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
