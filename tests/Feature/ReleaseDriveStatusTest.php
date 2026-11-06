<?php

namespace Tests\Feature;

use App\Models\Release;
use App\Models\Track;
use App\Services\ReleaseDriveStatus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReleaseDriveStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'release_drive_testing','database.connections.release_drive_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        Schema::create('releases',function ($t) { $t->id(); $t->string('status')->nullable(); $t->string('title'); });
        Schema::create('tracks',function ($t) { $t->id(); $t->string('title'); });
        Schema::create('release_track',function ($t) { $t->integer('release_id'); $t->integer('track_id'); $t->integer('track_order')->default(1); $t->timestamps(); });
        Schema::create('filestorage',function ($t) {
            $t->id(); foreach (['owner_type','owner_id','type','disk','identifier','original_name'] as $field) $t->string($field)->nullable();
        });
        (require database_path('migrations/2026_10_31_110000_create_drive_transfers_table.php'))->up();
        DB::table('releases')->insert([['id'=>1,'status'=>'released','title'=>'Release A'],['id'=>2,'status'=>'review','title'=>'Other release']]);
        DB::table('tracks')->insert(['id'=>1,'title'=>'Track A']);
        DB::table('release_track')->insert(['release_id'=>1,'track_id'=>1]);
        // Rendering must never call Drive or change a remote file.
        $this->app->bind(\App\Services\RcloneDrive::class,fn () => throw new \RuntimeException('Unexpected remote call'));
    }

    protected function tearDown(): void { DB::purge('release_drive_testing'); parent::tearDown(); }

    private function file(string $type, array $extra=[]): int
    {
        return DB::table('filestorage')->insertGetId(array_merge(['owner_type'=>in_array($type,['full','trimmed']) ? Track::class : Release::class,'owner_id'=>1,'type'=>$type,'disk'=>'local','identifier'=>'media/'.$type,'original_name'=>$type.'.wav'],$extra));
    }

    private function transfer(int $id, array $extra=[]): void
    {
        DB::table('drive_transfers')->insert(array_merge(['file_id'=>$id,'local_identifier'=>'media/original','remote_identifier'=>'gdrive:files/'.$id,'status'=>'complete','verified_at'=>'2026-11-07 10:00:00','created_at'=>'2026-11-07 09:00:00','updated_at'=>'2026-11-07 10:00:00'],$extra));
    }

    private function data(): array { return app(ReleaseDriveStatus::class)->forRelease(Release::findOrFail(1)); }

    public function test_statuses_distinguish_verified_cleanup_local_failed_and_ftp_without_double_counting(): void
    {
        $cover=$this->file('cover',['disk'=>'rclone','identifier'=>'gdrive:files/1']); $this->transfer($cover);
        $thumbnail=$this->file('thumbnail',['disk'=>'rclone','identifier'=>'gdrive:files/2']); $this->transfer($thumbnail,['status'=>'cleanup_pending']);
        $this->file('ipl',['disk'=>'ftp']);
        $full=$this->file('full'); $this->transfer($full,['status'=>'copying','verified_at'=>null]);
        $trimmed=$this->file('trimmed'); $this->transfer($trimmed,['status'=>'failed','verified_at'=>null,'error'=>'private-token-should-not-display']);
        $data=$this->data(); $states=array_column($data['rows'],'state');
        $this->assertSame(['verified','verified','not_uploaded','copying','failed'],$states);
        $this->assertSame(['files'=>5,'verified'=>2,'not_uploaded'=>3,'failed'=>1,'missing'=>0],$data['counts']);
        $this->assertStringContainsString('pembersihan lokal',$data['rows'][1]['detail']);
        $this->assertSame('FTP',$data['rows'][2]['location']);
        $html=Blade::render('<x-release-drive-status :storage="$storage" />',['storage'=>$data]);
        $this->assertStringContainsString('2 dari 5 file terverifikasi',$html);
        $this->assertStringContainsString('07 Nov 2026 10:00',$html);
        $this->assertStringNotContainsString('private-token-should-not-display',$html);
        $this->assertStringNotContainsString('gdrive:files/',$html);
    }

    public function test_stale_or_absent_verification_never_claims_uploaded_file_is_verified(): void
    {
        $id=$this->file('cover',['disk'=>'rclone','identifier'=>'gdrive:new-file']); $this->transfer($id);
        $this->file('full',['disk'=>'rclone']);
        $data=$this->data();
        $this->assertSame(0,$data['counts']['verified']);
        $this->assertSame('unverified',$data['rows'][0]['state']);
        $this->assertSame('unverified',$data['rows'][3]['state']);
        $this->assertNull($data['rows'][0]['verified_at']);
    }

    public function test_missing_files_pending_and_shared_track_waiting_are_explicit(): void
    {
        $id=$this->file('cover'); $this->transfer($id,['status'=>'pending','verified_at'=>null]);
        $this->file('full');
        DB::table('release_track')->insert(['release_id'=>2,'track_id'=>1]);
        $data=$this->data();
        $this->assertSame('pending',$data['rows'][0]['state']);
        $this->assertSame('not_uploaded',$data['rows'][3]['state']);
        $this->assertStringContainsString('satu salinan',$data['rows'][3]['detail']);
        $this->assertSame(3,$data['counts']['missing']);
        DB::table('releases')->where('id',2)->update(['status'=>'released']);
        $this->assertSame('not_uploaded',$this->data()['rows'][3]['state']);
    }

    public function test_drafts_have_no_panel_and_other_owners_are_excluded_even_with_same_id(): void
    {
        $this->assertNull(app(ReleaseDriveStatus::class)->forRelease(Release::findOrFail(2)));
        $this->file('cover',['owner_type'=>Track::class]);
        $this->file('full',['owner_id'=>99]);
        $this->file('cover',['owner_id'=>2]);
        $this->assertSame(0,$this->data()['counts']['files']);
    }

    public function test_missing_transfer_table_and_empty_catalogue_do_not_break_page(): void
    {
        Schema::drop('drive_transfers');
        $this->file('cover');
        $data=$this->data();
        $this->assertFalse($data['history_ready']);
        $this->assertSame('not_uploaded',$data['rows'][0]['state']);
        DB::table('filestorage')->delete();
        $data=$this->data();
        $this->assertSame(0,$data['counts']['files']);
        $html=Blade::render('<x-release-drive-status :storage="$storage" />',['storage'=>$data]);
        $this->assertStringContainsString('Riwayat pemindahan Google Drive belum tersedia',$html);
    }

    public function test_file_names_are_escaped_and_duplicate_attachments_are_not_hidden(): void
    {
        $this->file('full',['original_name'=>'<script>unsafe</script>.wav']);
        $this->file('full');
        $data=$this->data();
        $this->assertSame(2,$data['counts']['files']);
        $this->assertCount(6,$data['rows']);
        $html=Blade::render('<x-release-drive-status :storage="$storage" />',['storage'=>$data]);
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;',$html);
        $this->assertStringNotContainsString('<script>unsafe',$html);
    }
}
