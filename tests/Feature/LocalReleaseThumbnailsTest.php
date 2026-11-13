<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Release;
use App\Services\LocalReleaseThumbnails;
use App\Services\RcloneDrive;
use App\Services\ReleasedFilesDrive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;

class LocalReleaseThumbnailsTest extends \Tests\TestCase
{
    private string $bytes;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'local_thumbnail_test','database.connections.local_thumbnail_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        Schema::create('releases',fn ($t)=>[$t->id(),$t->string('title'),$t->string('status')]);
        Schema::create('tracks',fn ($t)=>$t->id());
        Schema::create('release_track',fn ($t)=>[$t->integer('track_id'),$t->integer('release_id')]);
        Schema::create('filestorage',function ($t) {
            $t->id();foreach (['disk','identifier','mime_type','type','owner_type','owner_id','size'] as $field) $t->text($field)->nullable();$t->timestamps();
        });
        (require database_path('migrations/2026_10_31_110000_create_drive_transfers_table.php'))->up();
        (require database_path('migrations/2026_11_07_060000_add_drive_layout_tracking.php'))->up();
        Storage::fake('local');
        DB::table('releases')->insert(['id'=>1,'title'=>'Published','status'=>'released']);
        DB::table('releases')->insert(['id'=>2,'title'=>'In review','status'=>'review']);
        $image=imagecreatetruecolor(600,600);ob_start();imagepng($image);$this->bytes=ob_get_clean();imagedestroy($image);
    }

    protected function tearDown(): void { DB::purge('local_thumbnail_test');parent::tearDown(); }

    private function thumbnail(): File
    {
        return File::create(['disk'=>'rclone','identifier'=>'gdrive:files/thumb.png','mime_type'=>'image/png','type'=>'thumbnail','owner_type'=>Release::class,'owner_id'=>1]);
    }

    private function client(): RcloneDrive
    {
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldNotReceive('deleteFile');
        $this->app->instance(RcloneDrive::class,$client);
        return $client;
    }

    private function downloadable(RcloneDrive $client,string $remote): void
    {
        $client->shouldReceive('stat')->with($remote)->once()->andReturn(['Size'=>strlen($this->bytes),'Hashes'=>['MD5'=>md5($this->bytes)]]);
        $client->shouldReceive('run')->with(Mockery::on(fn ($args)=>$args[0]==='copyto' && $args[1]===$remote && $args[3]==='--checksum'))
            ->once()->andReturnUsing(function ($args) {file_put_contents($args[2],$this->bytes);return '';});
    }

    public function test_thumbnail_is_downloaded_resized_verified_and_never_archived_again(): void
    {
        $file=$this->thumbnail();$client=$this->client();$this->downloadable($client,$file->identifier);
        app(ReleasedFilesDrive::class)->archive($file->id);
        $file->refresh();$this->assertSame('local',$file->disk);
        $this->assertSame('image/jpeg',$file->mime_type);
        $image=getimagesize(Storage::disk('local')->path($file->identifier));
        $this->assertSame([300,300],[$image[0],$image[1]]);
        $this->assertSame('thumbnail_local',DB::table('drive_transfers')->value('status'));
        $this->assertSame(0,app(ReleasedFilesDrive::class)->eligible()->count());
        app(ReleasedFilesDrive::class)->archive($file->id);
        Storage::disk('local')->assertExists($file->identifier);
        $this->assertSame(1,count(Storage::disk('local')->allFiles()));
    }

    public function test_missing_drive_thumbnail_can_be_regenerated_from_verified_original_cover(): void
    {
        $file=$this->thumbnail();
        File::create(['disk'=>'rclone','identifier'=>'gdrive:files/cover.png','type'=>'cover','owner_type'=>Release::class,'owner_id'=>1]);
        $client=$this->client();
        $client->shouldReceive('stat')->with($file->identifier)->once()->andThrow(new \App\Exceptions\DriveSetupException('File tidak ditemukan.'));
        $this->downloadable($client,'gdrive:files/cover.png');
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        $this->assertSame('rclone',File::where('type','cover')->first()->disk);
    }

    public function test_bad_download_checksum_keeps_drive_pointer_and_records_failure(): void
    {
        $file=$this->thumbnail();$client=$this->client();
        $client->shouldReceive('stat')->once()->andReturn(['Size'=>strlen($this->bytes),'Hashes'=>['MD5'=>md5($this->bytes)]]);
        $client->shouldReceive('run')->once()->andReturnUsing(function ($args){file_put_contents($args[2],'bad');return '';});
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('rclone',$file->fresh()->disk);
        $this->assertSame('gdrive:files/thumb.png',$file->fresh()->identifier);
        $this->assertSame('thumbnail_restore_failed',DB::table('drive_transfers')->value('status'));
        $this->assertSame([],Storage::disk('local')->allFiles());
    }

    public function test_old_local_thumbnail_jobs_cannot_delete_or_upload_local_image(): void
    {
        $file=$this->thumbnail();$file->update(['disk'=>'local','identifier'=>'thumbnails/keep.png']);
        Storage::disk('local')->put($file->identifier,$this->bytes);
        $client=$this->client();$client->shouldNotReceive('stat');$client->shouldNotReceive('run');
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('local',$file->fresh()->disk);
        Storage::disk('local')->assertExists($file->identifier);
    }

    public function test_database_failure_keeps_cloud_pointer_and_remote_source(): void
    {
        $file=$this->thumbnail();$client=$this->client();$this->downloadable($client,$file->identifier);
        DB::unprepared("CREATE TRIGGER reject_local_thumbnail BEFORE UPDATE ON filestorage BEGIN SELECT RAISE(ABORT,'test failure'); END");
        app(ReleasedFilesDrive::class)->archive($file->id);
        $this->assertSame('rclone',$file->fresh()->disk);
        $this->assertSame('gdrive:files/thumb.png',$file->fresh()->identifier);
        $this->assertSame('thumbnail_restore_failed',DB::table('drive_transfers')->value('status'));
    }

    public function test_restore_queue_covers_all_release_statuses_and_skips_local_thumbnails_and_covers(): void
    {
        config(['cache.default'=>'array']);\Illuminate\Support\Facades\Queue::fake();
        $first=$this->thumbnail();$second=$this->thumbnail();$second->update(['owner_id'=>2]);
        $local=$this->thumbnail();$local->update(['disk'=>'local']);
        File::create(['disk'=>'rclone','identifier'=>'gdrive:cover.png','type'=>'cover','owner_type'=>Release::class,'owner_id'=>1]);
        $this->artisan('drive:restore-thumbnails')->expectsOutput('2 thumbnail dijadwalkan untuk dikembalikan ke server.')->assertSuccessful();
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ArchiveReleasedFile::class,2);
        $this->assertSame(0,app(ReleasedFilesDrive::class)->eligible()->count());
    }
}
