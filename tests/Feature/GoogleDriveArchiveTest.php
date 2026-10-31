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

    public function test_non_released_and_shared_with_pending_release_stay_local(): void
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
        $this->assertFalse(app(ReleasedFilesDrive::class)->eligible()->whereKey($file->id)->exists());
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
        DB::table('drive_transfers')->insert(['file_id'=>$file->id,'local_identifier'=>$local,'remote_identifier'=>$file->identifier,'status'=>'cleanup_pending','size'=>26,'md5'=>md5('exact original media bytes'),'verified_at'=>now()]);
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
