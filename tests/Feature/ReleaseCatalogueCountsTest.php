<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\Aggregator\MainController;
use App\Models\Track;
use App\Models\User\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReleaseCatalogueCountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'catalogue_testing','database.connections.catalogue_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false]]);
        Schema::create('releases', function ($t) { $t->id(); $t->string('title')->default('Test release'); $t->string('status'); $t->string('type')->default('single'); $t->integer('profileid')->nullable(); $t->timestamps(); });
        Schema::create('tracks', function ($t) { $t->id(); $t->string('title')->default('Test track'); });
        Schema::create('release_track', function ($t) { $t->integer('release_id'); $t->integer('track_id'); });
        Schema::create('user_profiles', function ($t) { $t->id(); });
        Schema::create('filestorage', function ($t) { $t->id(); $t->string('owner_type'); $t->integer('owner_id'); $t->string('type'); });
        $this->viewer(true);
    }

    protected function tearDown(): void { DB::purge('catalogue_testing'); parent::tearDown(); }

    private function viewer(bool $all): void
    {
        $admin = new class extends Admin {
            public bool $all = true;
            public function hasPermission(string $permission): bool { return $permission === 'view-releases-all' && $this->all; }
        };
        $admin->all = $all;
        $admin->forceFill(['id'=>1,'name'=>'Test admin']);
        $this->actingAs($admin, 'admin');
    }

    private function release(string $status): int
    {
        return DB::table('releases')->insertGetId(['status'=>$status,'type'=>'single','created_at'=>now(),'updated_at'=>now()]);
    }

    private function assertTotals(int $expected): void
    {
        $home = app(HomeController::class)->index()->getData();
        $catalogue = app(MainController::class)->index()->getData();
        $this->assertSame($expected, $home['totalTracks']);
        $this->assertSame($expected, $catalogue['by_type']['tracks']);
    }

    public function test_orphan_tracks_and_stale_pivot_rows_do_not_inflate_empty_catalogue(): void
    {
        DB::table('tracks')->insert([['id'=>1],['id'=>2]]);
        DB::table('release_track')->insert(['release_id'=>999,'track_id'=>2]);
        $this->assertTotals(0);
        $this->assertSame(2, Track::count());
        $response = app(MainController::class)->exportTracks();
        $this->assertSame(200, $response->getStatusCode());
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertCount(1, array_filter(explode("\n", trim($csv))));
    }

    public function test_shared_tracks_are_counted_once_and_unattached_tracks_are_excluded(): void
    {
        $published = $this->release('released');
        $pending = $this->release('pending');
        DB::table('tracks')->insert([['id'=>1],['id'=>2],['id'=>3]]);
        DB::table('release_track')->insert([['release_id'=>$published,'track_id'=>1],['release_id'=>$pending,'track_id'=>1],['release_id'=>$published,'track_id'=>2]]);
        $this->assertTotals(2);
        DB::table('releases')->delete();
        $this->assertTotals(0);
        $this->assertSame(3, Track::count());
    }

    public function test_limited_admin_counts_only_tracks_and_releases_they_can_access(): void
    {
        $published = $this->release('released');
        $pending = $this->release('pending');
        DB::table('tracks')->insert([['id'=>1],['id'=>2]]);
        DB::table('release_track')->insert([['release_id'=>$published,'track_id'=>1],['release_id'=>$pending,'track_id'=>2]]);
        $this->viewer(false);
        $this->assertTotals(1);
        $catalogue = app(MainController::class)->index()->getData();
        $this->assertSame(1, $catalogue['by_status']['all']);
        $this->assertFalse($catalogue['by_status']->has('pending'));
        DB::table('releases')->where('id',$published)->delete();
        $this->assertTotals(0);
    }

    public function test_all_releases_counter_matches_the_non_draft_list(): void
    {
        $this->release('draft');
        $this->release('pending');
        $catalogue = app(MainController::class)->index()->getData();
        $this->assertSame(1, $catalogue['by_status']['all']);
        $this->assertSame(1, $catalogue['by_status']['draft']);
    }
}
