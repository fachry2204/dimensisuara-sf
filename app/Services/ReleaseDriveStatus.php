<?php

namespace App\Services;

use App\Models\File;
use App\Models\Release;
use App\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReleaseDriveStatus
{
    public function forRelease(Release $release): ?array
    {
        if ($release->status !== 'released') return null;
        $tracks = $release->tracks;
        $trackIds = $tracks->pluck('id');
        $files = File::query()->where(function ($query) use ($release, $trackIds) {
            $query->where(function ($query) use ($release) {
                $query->where('owner_type', $release->getMorphClass())->where('owner_id', $release->id)->whereIn('type', ['cover','thumbnail','ipl']);
            })->orWhere(function ($query) use ($trackIds) {
                $query->where('owner_type', (new Track)->getMorphClass())->whereIn('owner_id', $trackIds)->whereIn('type', ['full','trimmed']);
            });
        })->orderBy('id')->get();
        $ready = Schema::hasTable('drive_transfers');
        $transfers = $ready ? DB::table('drive_transfers')->whereIn('file_id', $files->pluck('id'))->get()->keyBy('file_id') : collect();
        $shared = DB::table('release_track as rt')->join('releases as r','r.id','=','rt.release_id')
            ->whereIn('rt.track_id',$trackIds)->where(fn ($query) => $query->where('r.status','!=','released')->orWhereNull('r.status'))
            ->pluck('rt.track_id')->all();
        $slots = [];
        foreach (['cover'=>'Cover art','thumbnail'=>'Thumbnail','ipl'=>'IPL'] as $type=>$label) {
            $slots[] = [$release->getMorphClass(), $release->id, $type, $label, $release->title, false];
        }
        foreach ($tracks as $track) foreach (['full'=>'Audio penuh','trimmed'=>'Audio potongan'] as $type=>$label) {
            $slots[] = [$track->getMorphClass(), $track->id, $type, $label, $track->title, in_array($track->id,$shared)];
        }
        $rows = [];
        $counts = ['files'=>0,'verified'=>0,'not_uploaded'=>0,'failed'=>0,'missing'=>0];
        foreach ($slots as [$ownerType,$ownerId,$type,$label,$title,$blocked]) {
            $matches = $files->filter(fn ($file) => $file->owner_type === $ownerType && (int) $file->owner_id === (int) $ownerId && $file->type === $type);
            // Include every file record if older data contains more than one attachment.
            if ($matches->isEmpty()) $matches = collect([null]);
            foreach ($matches as $file) {
                $transfer = $file ? $transfers->get($file->id) : null;
                if ($type === 'thumbnail') {
                    $local=$file && $file->disk==='local' && filled($file->identifier);
                    $rows[]=['file_id'=>$file?->id,'name'=>$file?->original_name ?: '-','type'=>$label,'title'=>$title,
                        'state'=>$local ? 'local_thumbnail' : ($file ? 'thumbnail_restore' : 'missing'),
                        'status'=>$local ? 'Thumbnail di server' : ($file ? ($transfer?->status==='thumbnail_restore_failed' ? 'Pemulihan thumbnail gagal' : 'Menunggu kembali ke server') : 'Tidak tersedia'),
                        'tone'=>$local ? 'success' : 'neutral',
                        'detail'=>$local ? 'Thumbnail tetap di server dan tidak diunggah ke Google Drive.' : 'Thumbnail dikembalikan dari Drive; cover asli tetap di Drive. Sumber harus tersedia dan lolos verifikasi.',
                        'location'=>$local ? 'Server lokal' : ($file ? 'Google Drive' : '-'),'verified_at'=>null,'updated_at'=>$transfer?->updated_at];
                    continue;
                }
                $verified = $file && $file->disk === 'rclone' && filled($file->identifier) && $transfer?->verified_at
                    && $transfer->remote_identifier === $file->identifier;
                $state = 'missing'; $status = 'Tidak tersedia'; $detail = 'Belum ada file tersimpan untuk jenis ini.';
                $location = '-'; $tone = 'neutral';
                if ($file && filled($file->identifier)) {
                    ++$counts['files'];
                    $location = match ($file->disk) { 'rclone'=>'Google Drive','local'=>'Server lokal','ftp'=>'FTP',default=>'Penyimpanan lain' };
                    if ($verified) {
                        $state='verified'; $status='Terverifikasi di Drive'; $tone='success';
                        $detail=match ($transfer->status) {
                            'cleanup_pending'=>'Salinan Drive terverifikasi; pembersihan lokal belum selesai.',
                            'remote_cleanup_pending'=>'Folder baru terverifikasi; pembersihan lokasi Drive lama belum selesai.',
                            'relocating'=>'Salinan Drive tersedia; folder sedang ditata ulang.',
                            'relocation_failed'=>'Salinan Drive lama dipertahankan; penataan folder perlu dicoba ulang.',
                            default=>'Ukuran dan checksum salinan telah cocok.',
                        };
                    } elseif ($file->disk === 'rclone') {
                        $state='unverified'; $status='Drive, belum terverifikasi'; $detail='File tercatat di Drive, tetapi catatan verifikasi yang cocok belum tersedia.';
                    } elseif ($file->disk !== 'local') {
                        $state='not_uploaded'; $status='Belum di Google Drive'; $detail='Pemindahan otomatis hanya mencakup file pada server lokal.';
                    } elseif ($transfer?->status === 'failed') {
                        $state='failed'; $status='Pemindahan gagal'; $tone='danger'; $detail='Periksa koneksi dan izin worker. File masih tercatat pada server lokal.';
                    } elseif ($transfer?->status === 'copying') {
                        $state='copying'; $status='Sedang diproses'; $detail='Status proses terakhir: menyalin ke Drive. Muat ulang untuk melihat pembaruan.';
                    } elseif ($transfer?->status === 'pending') {
                        $state='pending'; $status='Menunggu pemindahan'; $detail='Belum ada salinan Drive yang terverifikasi.';
                    } else {
                        $state='not_uploaded'; $status='Belum diunggah'; $detail='Belum ada salinan Drive terverifikasi untuk file ini.';
                    }
                    if (in_array($type,['full','trimmed']) && $blocked) $detail.=' Audio bersama memakai satu salinan di folder rilisan Published utama.';
                    if ($verified) ++$counts['verified'];
                    else ++$counts['not_uploaded'];
                    if ($state === 'failed') ++$counts['failed'];
                } else ++$counts['missing'];
                $rows[] = ['file_id'=>$file?->id,'name'=>$file?->original_name ?: '-', 'type'=>$label,'title'=>$title,
                    'state'=>$state,'status'=>$status,'tone'=>$tone,'detail'=>$detail,'location'=>$location,
                    'verified_at'=>$verified ? $transfer->verified_at : null,'updated_at'=>$transfer?->updated_at];
            }
        }
        return ['rows'=>$rows,'counts'=>$counts,'history_ready'=>$ready];
    }
}
