<?php

namespace Tests\Feature;

use App\Models\File;
use App\Services\ReleasedFilesDrive;
use App\Services\RcloneDrive;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;

require_once __DIR__.'/LegacyImportTest.php';

class GoogleDriveArchiveTest extends LegacyImportTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('system_settings',function ($t) { $t->string('key')->primary();$t->text('value');$t->timestamps(); });
        Schema::create('filestorage',function ($t) {
            $t->id(); foreach (['disk','identifier','original_name','mime_type','type','owner_type','owner_id','profileid','uploaded_at','size'] as $column) $t->text($column)->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_31_110000_create_drive_transfers_table.php'))->up();
        (require base_path('database/migrations/2026_11_07_060000_add_drive_layout_tracking.php'))->up();
        Storage::fake('local');
        $this->app->forgetInstance(SystemSettings::class);
        app(SystemSettings::class)->save(['drive_enabled'=>true]);
    }

    private function media(string $type='full'): File
    {
        if (!DB::table('tracks')->exists()) {
            DB::beginTransaction();
            (new \App\Services\Legacy\DimensiImporter)->run($this->data(),str_repeat('a',64));
            DB::commit();
        }
        $owner = $type === 'full' ? \App\Models\Track::first() : \App\Models\Release::first();
        $identifier = 'media/test-'.$type.'.'.($type === 'full' ? 'wav' : 'png');
        Storage::disk('local')->put($identifier,'exact original media bytes');
        return File::create(['disk'=>'local','identifier'=>$identifier,'original_name'=>basename($identifier),
            'mime_type'=>$type === 'full' ? 'audio/wav' : 'image/png','size'=>26,'type'=>$type,'owner_type'=>$owner->getMorphClass(),'owner_id'=>$owner->id,'profileid'=>$owner->profileid]);
    }

    private function client(bool $valid=true): RcloneDrive
    {
        $client = Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->with(Mockery::on(fn ($args) => $args[0] === 'copyto' && in_array('--immutable',$args,true)))->andReturn('');
        $client->shouldReceive('stat')->andReturn(['Size'=>26,'IsDir'=>false,'Hashes'=>['MD5'=>$valid ? md5('exact original media bytes') : str_repeat('0',32)]]);
        $this->app->instance(RcloneDrive::class,$client);
        return $client;
    }

    public function test_named_archive_path_is_safe_unique_and_uses_main_published_release(): void
    {
        $file=$this->media();
        $release=\App\Models\Release::first();
        $release->update(['title'=>'A / Release: [] ..','primaryartists'=>json_encode([['name'=>'Artist / Name']])]);
        $release->profile->update(['firstname'=>'Client / Name','lastname'=>'Test']);
        $second=\App\Models\Release::create(['title'=>'Other','status'=>'released','profileid'=>$release->profileid]);
        DB::table('release_track')->insert(['release_id'=>$second->id,'track_id'=>$file->owner_id,'track_order'=>0]);
        $path=app(\App\Services\DriveArchivePath::class)->destination($file,md5('exact original media bytes'));
        $this->assertStringContainsString('/Client Name Test (C'.$release->profileid.')/Artist Name/A Release (R'.$release->id.')/',$path);
        $this->assertStringContainsString('Audio Penuh (F'.$file->id.'-',$path);
        $this->assertStringEndsWith('.wav',$path);
        app(RcloneDrive::class)->validateRemote($path);
        $this->assertStringNotContainsString('..',$path);
    }

    public function test_existing_drive_archive_is_relocated_only_after_destination_verification(): void
    {
        $file=$this->media(); $old='gdrive:DimensiSuara/files/'.$file->id.'/old.wav';
        $file->update(['disk'=>'rclone','identifier'=>$old]);
        $md5=md5('exact original media bytes'); $stat=['Size'=>26,'IsDir'=>false,'Hashes'=>['MD5'=>$md5]];
        $destination=app(\App\Services\DriveArchivePath::class)->destination($file,$md5);
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('stat')->with($old)->once()->andReturn($stat);
        $client->shouldReceive('stat')->with($destination)->twice()->andReturn($stat);
        $client->shouldReceive('run')->with(['copyto',$old,$destination,'--checksum','--immutable'])->once()->andReturnUsing(function () use ($file,$old) { $this->assertSame($old,$file->fresh()->identifier); return ''; });
        $client->shouldReceive('run')->with(Mockery::on(fn ($args)=>$args[0]==='rmdir'))->andReturn('');
        $client->shouldReceive('deleteFile')->with($old)->once()->andReturnUsing(function () use ($file,$destination) { $this->assertSame($destination,$file->fresh()->identifier); });
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame($destination,$file->fresh()->identifier);
        $transfer=DB::table('drive_transfers')->where('file_id',$file->id)->first();
        $this->assertSame('complete',$transfer->status);
        $this->assertEquals(2,$transfer->layout_version);
        $this->assertNull($transfer->previous_remote_identifier);
        app(ReleasedFilesDrive::class)->archive($file->id);
    }

    public function test_bad_relocation_checksum_preserves_old_pointer_and_remote_file(): void
    {
        $file=$this->media(); $old='gdrive:DimensiSuara/files/'.$file->id.'/old.wav'; $file->update(['disk'=>'rclone','identifier'=>$old]);
        $md5=md5('exact original media bytes');
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('stat')->with($old)->once()->andReturn(['Size'=>26,'Hashes'=>['MD5'=>$md5]]);
        $client->shouldReceive('stat')->with(Mockery::on(fn ($remote)=>$remote!==$old))->once()->andReturn(['Size'=>26,'Hashes'=>['MD5'=>str_repeat('0',32)]]);
        $client->shouldReceive('run')->once()->andReturn(''); $client->shouldNotReceive('deleteFile');
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame($old,$file->fresh()->identifier);
        $this->assertSame('relocation_failed',DB::table('drive_transfers')->where('file_id',$file->id)->value('status'));
    }

    public function test_copy_error_can_resume_only_when_destination_size_and_checksum_match(): void
    {
        foreach ([true,false] as $matching) {
            $file=$this->media(); $old='gdrive:DimensiSuara/files/'.$file->id.'/old.wav';
            $file->update(['disk'=>'rclone','identifier'=>$old]);
            $md5=md5('exact original media bytes'); $stat=['Size'=>26,'Hashes'=>['MD5'=>$md5]];
            $destination=app(\App\Services\DriveArchivePath::class)->destination($file,$md5);
            $client=Mockery::mock(RcloneDrive::class)->makePartial();
            $client->shouldReceive('stat')->with($old)->once()->andReturn($stat);
            $client->shouldReceive('stat')->with($destination)->times($matching ? 3 : 1)
                ->andReturn($matching ? $stat : ['Size'=>26,'Hashes'=>['MD5'=>str_repeat('0',32)]]);
            $client->shouldReceive('run')->with(['copyto',$old,$destination,'--checksum','--immutable'])->once()
                ->andThrow(new \App\Exceptions\DriveSetupException('Salinan tujuan tidak cocok. (copyto, kode 7)'));
            if ($matching) {
                $client->shouldReceive('deleteFile')->with($old)->once()->andReturnUsing(function () use ($file,$destination) {
                    $this->assertSame($destination,$file->fresh()->identifier);
                });
                $client->shouldReceive('run')->with(Mockery::on(fn ($args)=>$args[0]==='rmdir'))->andReturn('');
            } else $client->shouldNotReceive('deleteFile');
            $this->app->instance(RcloneDrive::class,$client);
            app(ReleasedFilesDrive::class)->archive($file->id);
            $this->assertSame($matching ? $destination : $old,$file->fresh()->identifier);
            $transfer=DB::table('drive_transfers')->where('file_id',$file->id)->first();
            $this->assertSame($matching ? 'complete' : 'relocation_failed',$transfer->status);
            if (!$matching) $this->assertStringContainsString('Tahap menyalin ke folder baru:',$transfer->error);
        }
    }

    public function test_source_lookup_failure_reports_stage_and_keeps_current_location(): void
    {
        $file=$this->media(); $old='gdrive:DimensiSuara/files/'.$file->id.'/old.wav';
        $file->update(['disk'=>'rclone','identifier'=>$old]);
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('stat')->with($old)->once()->andThrow(new \App\Exceptions\DriveSetupException('File tidak ditemukan. (lsjson, kode 3)'));
        $client->shouldNotReceive('run'); $client->shouldNotReceive('deleteFile');
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame($old,$file->fresh()->identifier);
        $this->assertStringContainsString('Tahap membaca sumber Drive: File tidak ditemukan.',DB::table('drive_transfers')->where('file_id',$file->id)->value('error'));
    }

    public function test_location_save_failure_preserves_source_and_hides_database_details(): void
    {
        $file=$this->media(); $old='gdrive:DimensiSuara/files/'.$file->id.'/old.wav';
        $file->update(['disk'=>'rclone','identifier'=>$old]);
        $this->client()->shouldNotReceive('deleteFile');
        DB::unprepared("CREATE TRIGGER reject_drive_pointer BEFORE UPDATE OF identifier ON filestorage BEGIN SELECT RAISE(ABORT, 'private-database-detail'); END");
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame($old,$file->fresh()->identifier);
        $transfer=DB::table('drive_transfers')->where('file_id',$file->id)->first();
        $this->assertSame('relocation_failed',$transfer->status);
        $this->assertStringContainsString('Tahap menyimpan lokasi baru:',$transfer->error);
        $this->assertStringNotContainsString('private-database-detail',$transfer->error);
    }

    public function test_remote_cleanup_failure_is_resumable_without_recopying_or_changing_access(): void
    {
        $file=$this->media(); $old='gdrive:DimensiSuara/files/'.$file->id.'/old.wav'; $file->update(['disk'=>'rclone','identifier'=>$old]);
        $md5=md5('exact original media bytes'); $stat=['Size'=>26,'Hashes'=>['MD5'=>$md5]];
        $destination=app(\App\Services\DriveArchivePath::class)->destination($file,$md5);
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('stat')->andReturn($stat);
        $client->shouldReceive('run')->with(['copyto',$old,$destination,'--checksum','--immutable'])->once()->andReturn('');
        $client->shouldReceive('run')->with(Mockery::on(fn ($args)=>$args[0]==='rmdir'))->andReturn('');
        $deletes=0;
        $client->shouldReceive('deleteFile')->with($old)->twice()->andReturnUsing(function () use (&$deletes) { if (++$deletes===1) throw new \RuntimeException('Cleanup unavailable'); });
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame($destination,$file->fresh()->identifier);
        $this->assertSame('remote_cleanup_pending',DB::table('drive_transfers')->where('file_id',$file->id)->value('status'));
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('complete',DB::table('drive_transfers')->where('file_id',$file->id)->value('status'));
        $this->assertNull(DB::table('drive_transfers')->where('file_id',$file->id)->value('previous_remote_identifier'));
    }

    public function test_queue_scan_records_pending_and_includes_old_drive_layout_once_per_file(): void
    {
        config(['cache.default'=>'array']); \Illuminate\Support\Facades\Queue::fake();
        $local=$this->media(); $old=$this->media('cover'); $old->update(['disk'=>'rclone','identifier'=>'gdrive:DimensiSuara/files/old.jpg']);
        $this->assertSame(2,app(ReleasedFilesDrive::class)->queuePublished());
        $this->assertSame('pending',DB::table('drive_transfers')->where('file_id',$local->id)->value('status'));
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ArchiveReleasedFile::class,2);
    }

    public function test_upload_monitor_counts_and_filters_published_file_states(): void
    {
        $pending=$this->media(); $uploaded=$this->media('cover'); $this->client();
        app(ReleasedFilesDrive::class)->archive($uploaded->id);
        $failed=$this->media('thumbnail'); $active=$this->media('ipl');
        foreach ([$failed->id=>'failed',$active->id=>'copying'] as $id=>$status) DB::table('drive_transfers')->insert(['file_id'=>$id,'local_identifier'=>'media/source','status'=>$status]);
        $monitor=app(\App\Services\DriveUploadMonitor::class);
        $data=$monitor->dashboard(Request::create('/automation','GET',['tab'=>'drive']));
        $this->assertSame(['all'=>4,'pending'=>1,'uploading'=>1,'uploaded'=>1,'failed'=>1],$data['stats']);
        $this->assertTrue($data['layoutReady']);
        $filtered=$monitor->dashboard(Request::create('/automation','GET',['tab'=>'drive','status'=>'failed']));
        $this->assertSame(1,$filtered['files']->total());
        $this->assertSame($failed->id,$filtered['files']->first()->id);
        $pending->replicate()->save();
        $filtered=$monitor->dashboard(Request::create('/automation','GET',['tab'=>'drive','status'=>'pending']));
        $this->assertSame(2,$filtered['files']->total());
        $this->assertSame(['all'=>5,'pending'=>2,'uploading'=>1,'uploaded'=>1,'failed'=>1],$filtered['stats']);
    }

    public function test_upload_monitor_without_files_returns_zero_for_every_state(): void
    {
        $data=app(\App\Services\DriveUploadMonitor::class)->dashboard(Request::create('/automation','GET',['tab'=>'drive']));
        $this->assertSame(['all'=>0,'pending'=>0,'uploading'=>0,'uploaded'=>0,'failed'=>0],$data['stats']);
        $this->assertSame(0,$data['files']->total());
    }

    public function test_drive_automation_page_and_start_action_use_async_queue_and_require_ready_connection(): void
    {
        config(['cache.default'=>'array']); \Illuminate\Support\Facades\Queue::fake();
        $file=$this->media();
        $admin=new class extends \App\Models\User\Admin { public function hasPermission(string $permission): bool { return true; } };
        $admin->forceFill(['id'=>91,'name'=>'Admin Drive','email_verified_at'=>now()]); $admin->setRelation('unreadNotifications',collect());
        $this->actingAs($admin,'admin');
        $this->get(route('admin.automation.index',['tab'=>'drive']))->assertOk()->assertSee('Status Upload Google Drive')->assertSee('Proses semua file Published');
        $this->post(route('admin.automation.drive.start'))->assertSessionHasErrors('drive');
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
        app(SystemSettings::class)->save(['drive_connection_status'=>'ok']);
        $this->post(route('admin.automation.drive.start'))->assertSessionHasNoErrors()->assertRedirect(route('admin.automation.index',['tab'=>'drive']));
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ArchiveReleasedFile::class,fn ($job)=>$job->fileId===$file->id);
        $this->assertSame('local',$file->fresh()->disk);
        $this->get(route('admin.automation.index',['tab'=>'drive','status'=>'failed']))->assertOk()->assertSee('Tidak ada file untuk status yang dipilih.');
    }

    public function test_layout_migration_is_idempotent_and_unknown_transfer_history_remains_explicit(): void
    {
        $file=$this->media();
        (require base_path('database/migrations/2026_11_07_060000_add_drive_layout_tracking.php'))->up();
        $this->assertNotNull($file->fresh());
        Schema::drop('drive_transfers');
        $data=app(\App\Services\DriveUploadMonitor::class)->dashboard(Request::create('/automation','GET',['tab'=>'drive']));
        $this->assertFalse($data['ready']); $this->assertFalse($data['layoutReady']);
        $this->assertSame(1,$data['stats']['pending']);
    }

    public function test_released_wav_and_artwork_are_verified_before_local_deletion(): void
    {
        $this->client();
        foreach (['full','cover','thumbnail','ipl'] as $type) {
            $file = $this->media($type); $local = $file->identifier;
            app(ReleasedFilesDrive::class)->archive($file->id);
            $this->assertSame('rclone',$file->fresh()->disk);
            Storage::disk('local')->assertMissing($local);
            $this->assertSame('complete',DB::table('drive_transfers')->where('file_id',$file->id)->value('status'));
            app(ReleasedFilesDrive::class)->archive($file->id);
            $this->assertSame('rclone',$file->fresh()->disk);
        }
    }

    public function test_bad_checksum_keeps_original_file_and_local_database_pointer(): void
    {
        $this->client(false);
        $file = $this->media();
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        Storage::disk('local')->assertExists($file->identifier);
        $this->assertSame('failed',DB::table('drive_transfers')->where('file_id',$file->id)->value('status'));
    }

    public function test_lowercase_rclone_md5_is_verified_before_archiving_and_local_cleanup(): void
    {
        $file = $this->media(); $local = $file->identifier;
        $client = Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->with(Mockery::on(fn ($args) => $args[0] === 'copyto'))->once()->andReturn('');
        $client->shouldReceive('run')->with(Mockery::on(fn ($args) => $args[0] === 'lsjson'))->twice()
            ->andReturn(json_encode(['Size'=>26, 'IsDir'=>false, 'Hashes'=>['md5'=>md5('exact original media bytes')]]));
        $this->app->instance(RcloneDrive::class, $client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('rclone', $file->fresh()->disk);
        Storage::disk('local')->assertMissing($local);
        $this->assertSame('complete', DB::table('drive_transfers')->where('file_id', $file->id)->value('status'));
    }

    public function test_only_recordings_with_no_published_release_stay_local(): void
    {
        $file = $this->media();
        $cover = $this->media('cover');
        DB::table('releases')->update(['status'=>'pending']);
        $this->assertSame(0,app(ReleasedFilesDrive::class)->eligible()->count());
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        DB::table('releases')->update(['status'=>'released']);
        $release = \App\Models\Release::create(['title'=>'Pending shared recording','status'=>'pending','profileid'=>$file->profileid]);
        DB::table('release_track')->insert(['release_id'=>$release->id,'track_id'=>$file->owner_id]);
        $this->assertTrue(app(ReleasedFilesDrive::class)->eligible()->whereKey($file->id)->exists());
        $this->assertTrue(app(ReleasedFilesDrive::class)->eligible()->whereKey($cover->id)->exists());
    }

    public function test_failed_upload_and_missing_local_file_do_not_delete_anything(): void
    {
        $file = $this->media();
        $client = Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->andThrow(new \RuntimeException('Rclone gagal.'));
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        Storage::disk('local')->assertExists($file->identifier);
        Storage::disk('local')->delete($file->identifier);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('failed',DB::table('drive_transfers')->where('file_id',$file->id)->value('status'));
    }

    public function test_disable_stops_transfers_but_drive_streaming_still_supports_ranges(): void
    {
        app(SystemSettings::class)->save(['drive_enabled'=>false]);
        $file = $this->media();
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        $file->forceFill(['disk'=>'rclone','identifier'=>'gdrive:DimensiSuara/files/1/test.wav'])->save();
        $client = $this->client();
        $request = Request::create('/file','GET',[],[],[],['HTTP_RANGE'=>'bytes=5-9']);
        $response = $client->response($file,$request);
        $this->assertSame(206,$response->getStatusCode());
        $this->assertSame('bytes 5-9/26',$response->headers->get('Content-Range'));
        $this->assertSame('5',$response->headers->get('Content-Length'));
        $client->shouldReceive('stream')->once()->with($file->identifier,5,5)->andReturnUsing(fn () => print('abcde'));
        ob_start(); $response->sendContent(); $this->assertSame('abcde',ob_get_clean());
        $invalid = $client->response($file,Request::create('/file','GET',[],[],[],['HTTP_RANGE'=>'bytes=99-100']));
        $this->assertSame(416,$invalid->getStatusCode());
    }

    public function test_cleanup_resumes_after_worker_stopped_after_database_pointer_changed(): void
    {
        $file = $this->media(); $local = $file->identifier;
        $file->update(['disk'=>'rclone','identifier'=>'gdrive:DimensiSuara/files/1/test.wav']);
        DB::table('drive_transfers')->insert(['file_id'=>$file->id,'local_identifier'=>$local,'remote_identifier'=>$file->identifier,'status'=>'cleanup_pending','size'=>26,'md5'=>md5('exact original media bytes'),'verified_at'=>now(),'layout_version'=>\App\Services\DriveArchivePath::VERSION]);
        $this->client();
        app(ReleasedFilesDrive::class)->archive($file->id);
        Storage::disk('local')->assertMissing($local);
        $this->assertSame('complete',DB::table('drive_transfers')->value('status'));
    }

    public function test_cloud_audio_access_keeps_owner_permission_checks(): void
    {
        $file = $this->media();
        $file->update(['disk'=>'rclone','identifier'=>'gdrive:DimensiSuara/files/1/test.wav']);
        $user = new class extends \App\Models\User\User {
            public function hasPermission(string $permission): bool { return false; }
        };
        $user->forceFill(['id'=>99,'email_verified_at'=>now()]);
        $user->setRelation('profile',new \App\Models\User\Profile(['id'=>999]));
        \Illuminate\Support\Facades\Gate::define('admin',fn () => false);
        $this->actingAs($user);
        $client = Mockery::mock(RcloneDrive::class);
        $client->shouldNotReceive('response');
        $this->app->instance(RcloneDrive::class,$client);
        $url = $file->url;
        $this->getJson($url)->assertForbidden();
        $user->setRelation('profile',\App\Models\User\Profile::find($file->profileid));
        $client = Mockery::mock(RcloneDrive::class);
        $client->shouldReceive('response')->once()->andReturn(response('cloud audio',200,['Content-Type'=>'audio/wav']));
        $this->app->instance(RcloneDrive::class,$client);
        $this->get($url)->assertOk()->assertSee('cloud audio');
    }

    public function test_release_changed_during_upload_keeps_local_copy(): void
    {
        $file = $this->media();
        $client = Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('run')->andReturnUsing(function () { DB::table('releases')->update(['status'=>'revision']); return ''; });
        $client->shouldReceive('stat')->andReturn(['Size'=>26,'IsDir'=>false,'Hashes'=>['MD5'=>md5('exact original media bytes')]]);
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        Storage::disk('local')->assertExists($file->identifier);
    }

    public function test_scheduler_queues_released_files_and_ftp_cannot_take_them_when_drive_enabled(): void
    {
        $file = $this->media();
        $this->media('cover');
        \Illuminate\Support\Facades\Bus::fake();
        $this->artisan('drive:archive-released')->assertSuccessful();
        \Illuminate\Support\Facades\Bus::assertDispatchedTimes(\App\Jobs\ArchiveReleasedFile::class,2);
        $this->assertSame(0,app(\App\Services\TrackFileBackupService::class)->eligibleFiles()->count());
        $this->assertFalse(app(\App\Services\TrackFileBackupService::class)->backup($file));
        Storage::disk('local')->assertExists($file->identifier);
    }
}
