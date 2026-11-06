<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DriveUploadMonitor
{
    public function dashboard(Request $request): array
    {
        $request->validate(['status'=>['nullable','string','in:all,pending,uploading,uploaded,failed']]);
        $status=$request->input('status') ?: 'all';
        $ready=Schema::hasTable('drive_transfers');
        $layoutReady=$ready && Schema::hasColumn('drive_transfers','layout_version');
        $settings=app(SystemSettings::class);
        $base=app(ReleasedFilesDrive::class)->publishedFiles()->whereIn('disk',['local','rclone']);
        $other=app(ReleasedFilesDrive::class)->publishedFiles()->whereNotIn('disk',['local','rclone'])->count();
        if ($ready) {
            $base->leftJoin('drive_transfers as dt','dt.file_id','=','filestorage.id');
            $bucket="CASE WHEN dt.status IN ('copying','relocating') THEN 'uploading' WHEN dt.status IN ('failed','relocation_failed') THEN 'failed' WHEN filestorage.disk='rclone' AND dt.verified_at IS NOT NULL AND dt.remote_identifier=filestorage.identifier THEN 'uploaded' ELSE 'pending' END";
        } else $bucket="'pending'";
        $stats=['all'=>0,'pending'=>0,'uploading'=>0,'uploaded'=>0,'failed'=>0];
        foreach ((clone $base)->selectRaw($bucket.' as bucket, COUNT(*) as total')->groupByRaw($bucket)->get() as $group) { $stats[$group->bucket]=(int)$group->total; $stats['all']+=(int)$group->total; }
        if ($status !== 'all') $base->whereRaw($bucket.' = ?',[$status]);
        $columns=['filestorage.*'];
        if ($ready) $columns=array_merge($columns,['dt.status as transfer_status','dt.verified_at','dt.updated_at as processed_at','dt.remote_identifier']);
        $files=$base->select($columns)->with(['owner'=>function (\Illuminate\Database\Eloquent\Relations\MorphTo $morph) {
            $morph->morphWith([\App\Models\Release::class=>['profile'],\App\Models\Track::class=>['releases.profile']]);
        }])->orderBy('filestorage.id')->paginate(30)->withQueryString();
        $files->getCollection()->transform(function ($file) {
            try { $file->archive_context=app(DriveArchivePath::class)->context($file); } catch (\Throwable) { $file->archive_context=null; }
            return $file;
        });
        return compact('stats','files','status','ready','layoutReady','other') + ['driveEnabled'=>(bool)$settings->get('drive_enabled'),'connectionOk'=>$settings->get('drive_connection_status')==='ok'];
    }
}
