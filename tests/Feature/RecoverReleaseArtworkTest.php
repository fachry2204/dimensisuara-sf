<?php

namespace Tests\Feature;

use App\Exceptions\DriveSetupException;
use App\Models\Release;
use App\Services\RcloneDrive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RecoverReleaseArtworkTest extends TestCase
{
    private string $old='gdrive:dimensisuara/files/1/old.jpeg';
    private string $target='gdrive:dimensisuara/Client/Artist/Release/Cover (F1-abcdef).jpeg';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'recover_artwork_test','database.connections.recover_artwork_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
        Schema::create('releases',fn ($t)=>[$t->id(),$t->string('title')]);
        Schema::create('filestorage',function ($t) {
            $t->id(); foreach (['disk','identifier','owner_type','type','mime_type'] as $column) $t->string($column)->nullable();
            $t->integer('owner_id');$t->integer('size')->nullable();$t->timestamps();
        });
        (require database_path('migrations/2026_10_31_110000_create_drive_transfers_table.php'))->up();
        (require database_path('migrations/2026_11_07_060000_add_drive_layout_tracking.php'))->up();
        DB::table('releases')->insert(['id'=>1,'title'=>'Example']);
        DB::table('filestorage')->insert(['id'=>1,'disk'=>'rclone','identifier'=>$this->old,'type'=>'cover','owner_type'=>Release::class,'owner_id'=>1]);
        DB::table('drive_transfers')->insert(['file_id'=>1,'local_identifier'=>'old.jpeg','remote_identifier'=>$this->old,'md5'=>md5('jpeg'),'size'=>4,'status'=>'relocation_failed']);
    }

    protected function tearDown(): void { DB::purge('recover_artwork_test'); parent::tearDown(); }

    private function entry(string $path='Client/Artist/Release/Cover (F1-abcdef).jpeg'): array
    {
        return ['Path'=>$path,'IsDir'=>false,'MimeType'=>'image/jpeg','Size'=>4,'Hashes'=>['md5'=>md5('jpeg')]];
    }

    private function client(array $entries): RcloneDrive
    {
        $client=Mockery::mock(RcloneDrive::class)->makePartial();
        $client->shouldReceive('root')->andReturn('gdrive:dimensisuara');
        $client->shouldReceive('stat')->with($this->old)->andThrow(new DriveSetupException('File atau folder Google Drive tidak ditemukan. (lsjson, kode 3)'));
        $client->shouldReceive('run')->with(['lsjson','gdrive:dimensisuara','--recursive','--files-only','--hash','--hash-type','MD5'])->once()->andReturn(json_encode($entries));
        $client->shouldNotReceive('deleteFile');
        $this->app->instance(RcloneDrive::class,$client);
        return $client;
    }

    public function test_dry_run_verifies_candidate_without_mutation_and_apply_restores_pointer(): void
    {
        $client=$this->client([$this->entry()]);
        $client->shouldReceive('stat')->with($this->target)->once()->andReturn($this->entry());
        $this->artisan('drive:recover-artwork',['release'=>1])->assertSuccessful();
        $this->assertSame($this->old,DB::table('filestorage')->value('identifier'));
        $this->assertSame('relocation_failed',DB::table('drive_transfers')->value('status'));
        // Each command takes its own inventory snapshot.
        $client=$this->client([$this->entry()]);
        $client->shouldReceive('stat')->with($this->target)->once()->andReturn($this->entry());
        $this->artisan('drive:recover-artwork',['release'=>1,'--apply'=>true])->assertSuccessful();
        $this->assertSame($this->target,DB::table('filestorage')->value('identifier'));
        $this->assertSame('image/jpeg',DB::table('filestorage')->value('mime_type'));
        $this->assertSame('complete',DB::table('drive_transfers')->value('status'));
        $this->assertSame($this->target,DB::table('drive_transfers')->value('remote_identifier'));
    }

    public function test_ambiguous_or_mismatched_inventory_does_not_change_pointer(): void
    {
        $this->client([$this->entry('Other/a.jpeg'),$this->entry('Other/b.jpeg')]);
        $this->artisan('drive:recover-artwork',['release'=>1,'--apply'=>true])->assertFailed();
        $this->assertSame($this->old,DB::table('filestorage')->value('identifier'));
    }

    public function test_destination_that_changes_after_inventory_is_rejected(): void
    {
        $client=$this->client([$this->entry()]);
        $stat=$this->entry();$stat['Size']=5;
        $client->shouldReceive('stat')->with($this->target)->once()->andReturn($stat);
        $this->artisan('drive:recover-artwork',['release'=>1,'--apply'=>true])->assertFailed();
        $this->assertSame($this->old,DB::table('filestorage')->value('identifier'));
    }

    public function test_permission_failure_does_not_trigger_search_or_repoint(): void
    {
        $client=Mockery::mock(RcloneDrive::class);
        $client->shouldReceive('stat')->once()->andThrow(new DriveSetupException('Akses ditolak. (lsjson, kode 7)'));
        $client->shouldNotReceive('run');$client->shouldNotReceive('deleteFile');
        $this->app->instance(RcloneDrive::class,$client);
        $this->artisan('drive:recover-artwork',['release'=>1,'--apply'=>true])->assertFailed();
        $this->assertSame($this->old,DB::table('filestorage')->value('identifier'));
    }

    public function test_wrong_checksum_and_nonimage_candidates_are_not_used(): void
    {
        $wrong=$this->entry('Other/hash.jpeg');$wrong['Hashes']['md5']=md5('different');
        $unsafe=$this->entry('Other/unsafe.jpeg');$unsafe['MimeType']='text/html';
        $this->client([$wrong,$unsafe]);
        $this->artisan('drive:recover-artwork',['release'=>1,'--apply'=>true])->assertFailed();
        $this->assertSame($this->old,DB::table('filestorage')->value('identifier'));
    }

    public function test_matching_file_id_disambiguates_identical_copies_and_transaction_is_atomic(): void
    {
        $client=$this->client([$this->entry(),$this->entry('Other/another.jpeg')]);
        $client->shouldReceive('stat')->with($this->target)->once()->andReturn($this->entry());
        DB::unprepared("CREATE TRIGGER reject_recovery BEFORE UPDATE ON drive_transfers BEGIN SELECT RAISE(ABORT,'private-db-error'); END");
        $this->artisan('drive:recover-artwork',['release'=>1,'--apply'=>true])->assertFailed();
        $this->assertSame($this->old,DB::table('filestorage')->value('identifier'));
        $this->assertSame('relocation_failed',DB::table('drive_transfers')->value('status'));
    }
}
