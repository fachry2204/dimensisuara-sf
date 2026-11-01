<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Release;
use App\Models\Track;
use App\Models\User\Admin;
use App\Services\RcloneDrive;
use App\Services\Releases\ReleaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ReleaseDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'deletion_testing', 'database.connections.deletion_testing'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'', 'foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        Schema::create('releases', function ($t) { $t->id(); $t->string('title')->default('Test Release'); $t->string('status')->default('pending'); $t->timestamps(); });
        Schema::create('tracks', function ($t) { $t->id(); $t->string('title')->default('Test Track'); $t->timestamps(); });
        Schema::create('release_track', function ($t) { $t->foreignId('release_id')->constrained()->cascadeOnDelete(); $t->foreignId('track_id')->constrained()->cascadeOnDelete(); $t->integer('track_order')->default(0); $t->timestamps(); });
        Schema::create('release_histories', function ($t) { $t->id(); $t->foreignId('release_id')->constrained()->cascadeOnDelete(); });
        Schema::create('filestorage', function ($t) {
            $t->id(); foreach (['disk','identifier','backup_identifier','type','owner_type'] as $column) $t->string($column)->nullable();
            $t->integer('owner_id'); $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_31_110000_create_drive_transfers_table.php'))->up();
        Storage::fake('local'); Storage::fake('ftp');
        $this->viewer();
    }

    protected function tearDown(): void { DB::purge('deletion_testing'); parent::tearDown(); }

    private function viewer(bool $canDelete = true, bool $all = true): void
    {
        $admin = new class extends Admin {
            public bool $canDelete = true;
            public bool $all = true;
            public function hasPermission(string $permission): bool {
                return in_array($permission, ['admin','view-releases'], true)
                    || ($permission === 'delete-releases' && $this->canDelete)
                    || ($permission === 'view-releases-all' && $this->all);
            }
        };
        $admin->canDelete = $canDelete; $admin->all = $all;
        $admin->forceFill(['id'=>1,'name'=>'Test Admin','email_verified_at'=>now()]);
        $this->actingAs($admin, 'admin');
    }

    private function release(): Release
    {
        $id = DB::table('releases')->insertGetId([]);
        return Release::findOrFail($id);
    }

    private function recording(Release $release): Track
    {
        $id = DB::table('tracks')->insertGetId([]);
        $track = Track::findOrFail($id);
        $release->tracks()->attach($id, ['track_order'=>1]);
        return $track;
    }

    private function media($owner, string $type): File
    {
        $path = 'media/'.class_basename($owner).'-'.$owner->id.'-'.$type;
        Storage::disk('local')->put($path, 'original bytes');
        return File::create(['disk'=>'local','identifier'=>$path,'type'=>$type,'owner_type'=>$owner->getMorphClass(),'owner_id'=>$owner->id]);
    }

    public function test_authorized_deletion_removes_release_recording_artwork_backups_and_history(): void
    {
        $release = $this->release(); $track = $this->recording($release);
        $files = [];
        foreach (['cover','thumbnail','ipl'] as $type) $files[] = $this->media($release, $type);
        foreach (['full','trimmed'] as $type) $files[] = $this->media($track, $type);
        $files[3]->update(['backup_identifier'=>'backup/full.wav']);
        Storage::disk('ftp')->put('backup/full.wav', 'backup bytes');
        DB::table('release_histories')->insert(['release_id'=>$release->id]);
        $this->delete(route('admin.releases.destroy',$release->id), ['confirm_delete'=>1])
            ->assertRedirect(route('admin.releases.index'))->assertSessionHasNoErrors();
        foreach (['releases','tracks','release_track','release_histories','filestorage'] as $table) $this->assertSame(0, DB::table($table)->count());
        foreach ($files as $file) Storage::disk('local')->assertMissing($file->identifier);
        Storage::disk('ftp')->assertMissing('backup/full.wav');
    }

    public function test_shared_recording_survives_until_its_last_release_is_deleted(): void
    {
        $first = $this->release(); $second = $this->release();
        $track = $this->recording($first); $second->tracks()->attach($track->id);
        $audio = $this->media($track, 'full');
        // Exercise the formerly unsafe eager-loaded track cache as well.
        $first->load('tracks');
        app(ReleaseService::class)->destroy($first);
        $this->assertNotNull($track->fresh()); $this->assertNotNull($audio->fresh());
        $this->assertSame(1, $second->tracks()->count());
        Storage::disk('local')->assertExists($audio->identifier);
        app(ReleaseService::class)->destroy($second);
        $this->assertNull(Track::find($track->id)); $this->assertNull(File::find($audio->id));
        Storage::disk('local')->assertMissing($audio->identifier);
    }

    public function test_a_file_path_still_referenced_by_another_release_is_retained(): void
    {
        $first = $this->release(); $second = $this->release();
        $cover = $this->media($first, 'cover');
        $other = $cover->replicate(); $other->owner_id = $second->id; $other->save();
        app(ReleaseService::class)->destroy($first);
        Storage::disk('local')->assertExists($cover->identifier);
        $this->assertNotNull($other->fresh());
        app(ReleaseService::class)->destroy($second);
        Storage::disk('local')->assertMissing($cover->identifier);
    }

    public function test_permission_confirmation_and_release_visibility_are_enforced(): void
    {
        $release = $this->release(); $cover = $this->media($release, 'cover');
        $url = route('admin.releases.destroy',$release->id);
        $this->viewer(false);
        $this->deleteJson($url,['confirm_delete'=>1])->assertForbidden();
        $this->viewer();
        $this->deleteJson($url)->assertUnprocessable()->assertJsonValidationErrors('confirm_delete');
        $this->viewer(true, false);
        $this->deleteJson($url,['confirm_delete'=>1])->assertNotFound();
        $this->assertNotNull($release->fresh());
        Storage::disk('local')->assertExists($cover->identifier);
    }

    public function test_storage_failure_does_not_report_success_or_remove_database_records(): void
    {
        $release = $this->release(); $cover = $this->media($release, 'cover');
        $disk = Mockery::mock(); $disk->shouldReceive('delete')->with($cover->identifier)->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->delete(route('admin.releases.destroy',$release->id),['confirm_delete'=>1])->assertSessionHasErrors();
        $this->assertNotNull($release->fresh()); $this->assertNotNull($cover->fresh());
    }

    public function test_drive_copy_and_leftover_local_copy_are_deleted_once_with_the_transfer_record(): void
    {
        $release = $this->release(); $cover = $this->media($release, 'cover'); $local = $cover->identifier;
        $remote = 'gdrive:archive/files/'.$cover->id.'/cover.png';
        $cover->update(['disk'=>'rclone','identifier'=>$remote]);
        DB::table('drive_transfers')->insert(['file_id'=>$cover->id,'local_identifier'=>$local,'remote_identifier'=>$remote,'md5'=>md5('original bytes'),'status'=>'cleanup_pending']);
        $client = Mockery::mock(RcloneDrive::class);
        $client->shouldReceive('deleteFile')->with($remote)->once();
        $this->app->instance(RcloneDrive::class,$client);
        app(ReleaseService::class)->destroy($release);
        Storage::disk('local')->assertMissing($local);
        $this->assertSame(0, DB::table('drive_transfers')->count());
        $this->assertNull(File::find($cover->id));
    }

    public function test_failed_drive_deletion_retains_database_pointers_for_retry(): void
    {
        $release = $this->release(); $cover = $this->media($release, 'cover');
        $cover->update(['disk'=>'rclone','identifier'=>'gdrive:archive/cover.png']);
        $client = Mockery::mock(RcloneDrive::class);
        $client->shouldReceive('deleteFile')->once()->andThrow(new \RuntimeException('Unavailable'));
        $this->app->instance(RcloneDrive::class,$client);
        $this->delete(route('admin.releases.destroy',$release->id),['confirm_delete'=>1])->assertSessionHasErrors();
        $this->assertNotNull($release->fresh()); $this->assertNotNull($cover->fresh());
    }

    public function test_missing_local_file_does_not_block_cleanup(): void
    {
        $release = $this->release(); $cover = $this->media($release, 'cover');
        Storage::disk('local')->delete($cover->identifier);
        app(ReleaseService::class)->destroy($release);
        $this->assertNull(Release::find($release->id)); $this->assertNull(File::find($cover->id));
    }

    public function test_unsafe_storage_pointer_is_rejected_without_deleting_records(): void
    {
        $release = $this->release(); $cover = $this->media($release, 'cover');
        $cover->update(['identifier'=>'../outside.wav']);
        $this->delete(route('admin.releases.destroy',$release->id),['confirm_delete'=>1])->assertSessionHasErrors();
        $this->assertNotNull($release->fresh()); $this->assertNotNull($cover->fresh());
    }
}
