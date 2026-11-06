<?php

namespace Tests\Feature;

use App\Http\Controllers\AnalyticController;
use App\Database\RelationalFields;
use App\Models\User\Profile;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnalyticsPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Dedicated in-memory database: never migrate or modify the local database.
        config([
            'database.default' => 'analytics_testing',
            'database.connections.analytics_testing' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false],
        ]);
        Carbon::setTestNow('2026-10-14 12:00:00');
        foreach (['report_aggregate_multi', 'report_aggregate_platforms', 'report_aggregate_countries', 'report_aggregate_tracks', 'report_aggregate_releases'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->integer('profileid');
                $table->date('sales_month');
                $table->integer('quantity');
                $table->decimal('client_rupiah_exact', 18, 4)->default(0);
                $table->string('platform')->nullable();
                $table->string('country')->nullable();
                $table->integer('trackid')->nullable();
                $table->integer('releaseid')->nullable();
                $table->string('sales_type')->default('Stream');
                $table->string('artist_name')->nullable();
            });
        }
        Schema::create('tracks', function (Blueprint $table) {
            $table->id(); $table->integer('profileid'); $table->string('title');
        });
        Schema::create('releases', function (Blueprint $table) {
            $table->id(); $table->integer('profileid'); $table->string('title');
            $table->text('primaryartists')->nullable(); $table->string('type')->nullable(); $table->date('releaseon')->nullable();
        });
        Schema::create('release_track', function (Blueprint $table) {
            $table->integer('release_id'); $table->integer('track_id');
        });
        DB::table('report_aggregate_multi')->insert([
            ['profileid' => 1, 'sales_month' => '2025-04-01', 'quantity' => 100, 'client_rupiah_exact' => 50, 'platform' => 'Spotify'],
            ['profileid' => 1, 'sales_month' => '2025-11-01', 'quantity' => 80, 'client_rupiah_exact' => 40, 'platform' => 'Spotify'],
            ['profileid' => 1, 'sales_month' => '2026-05-01', 'quantity' => 200, 'client_rupiah_exact' => 100, 'platform' => 'Spotify'],
            ['profileid' => 2, 'sales_month' => '2026-09-01', 'quantity' => 999, 'client_rupiah_exact' => 999, 'platform' => 'Other'],
        ]);
        $this->signInProfile(1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('analytics_testing');
        parent::tearDown();
    }

    private function signInProfile(int $id): void
    {
        $user = new User();
        $user->forceFill(['id' => $id]);
        $profile = new Profile();
        $profile->forceFill(['id' => $id]);
        $user->setRelation('profile', $profile);
        $this->actingAs($user);
    }

    private function analytics(array $query = []): array
    {
        return app(AnalyticController::class)->index(Request::create('/analytics', 'GET', $query))->getData();
    }

    public function test_subtype_csv_uses_current_customer_and_selected_reporting_period(): void
    {
        Schema::create('report_imports',fn ($t)=>[$t->id(),$t->string('status')]);
        Schema::create('report_rows',function ($t) {
            $t->id(); $t->integer('report_import_id'); $t->integer('profileid'); $t->string('status');
            $t->string('platform'); $t->string('sales_type'); $t->string('sales_sub_type');
            $t->date('sales_month'); $t->integer('quantity'); $t->decimal('client_rupiah_exact',18,4);
        });
        DB::table('report_imports')->insert(['id'=>1,'status'=>'completed']);
        foreach ([[1,'2026-05-01','INDIVIDUAL'],[2,'2026-05-01','PRIVATE_OTHER_ACCOUNT'],[1,'2025-04-01','OUTSIDE_PERIOD']] as [$profile,$month,$subtype]) {
            DB::table('report_rows')->insert(['report_import_id'=>1,'profileid'=>$profile,'status'=>'finalized','platform'=>'Spotify','sales_type'=>'STREAMING','sales_sub_type'=>$subtype,'sales_month'=>$month,'quantity'=>10,'client_rupiah_exact'=>5]);
        }
        $response = app(AnalyticController::class)->exportSubtypes(Request::create('/analytics/sales-sub-types/export','GET',['date_range'=>'last_6_months']));
        ob_start(); $response->sendContent(); $csv = ob_get_clean();
        $this->assertStringContainsString('INDIVIDUAL',$csv);
        $this->assertStringNotContainsString('PRIVATE_OTHER_ACCOUNT',$csv);
        $this->assertStringNotContainsString('OUTSIDE_PERIOD',$csv);
    }

    public function test_default_shows_all_available_reports_despite_reporting_delay(): void
    {
        $data = $this->analytics();
        $this->assertSame('all_time', $data['dateRange']);
        $this->assertSame('2025-04-01', $data['startDate']);
        $this->assertSame('2026-05-31', $data['endDate']);
        $this->assertSame(380, $data['currentPeriod']);
        $this->assertSame(190.0, $data['periodRevenue']);
        $this->assertSame(1, $data['platformCount']);
        $this->assertCount(3, $data['monthsFormatted']);
    }

    public function test_six_months_and_comparison_follow_latest_report_month(): void
    {
        $data = $this->analytics(['date_range' => 'last_6_months']);
        $this->assertSame('2025-12-01', $data['startDate']);
        $this->assertSame('2026-05-31', $data['endDate']);
        $this->assertSame(200, $data['currentPeriod']);
        $this->assertEquals(150, $data['trendPercentage']);
    }

    public function test_twelve_months_include_twelve_reporting_months(): void
    {
        $data = $this->analytics(['date_range' => 'last_12_months']);
        $this->assertSame('2025-06-01', $data['startDate']);
        $this->assertSame('2026-05-31', $data['endDate']);
        $this->assertSame(280, $data['currentPeriod']);
        $this->assertEquals(180, $data['trendPercentage']);
    }

    public function test_explicit_calendar_quarter_is_kept_even_without_reports(): void
    {
        $data = $this->analytics(['date_range' => 'last_completed_quarter']);
        $this->assertSame('2026-07-01', $data['startDate']);
        $this->assertSame('2026-09-30', $data['endDate']);
        $this->assertSame(0, $data['currentPeriod']);
        $this->assertSame('2026-05-31', $data['latestAvailableDate']);
    }

    public function test_account_without_reports_does_not_claim_an_available_month(): void
    {
        $this->signInProfile(3);
        $data = $this->analytics();
        $this->assertNull($data['latestAvailableDate']);
        $this->assertSame(0, $data['currentPeriod']);
        $this->assertSame(0.0, $data['periodRevenue']);
    }

    private function seedCatalogue(bool $normalized): void
    {
        DB::table('releases')->insert([
            ['id' => 1, 'profileid' => 1, 'title' => 'Rilis Saya', 'primaryartists' => '[{"name":"Artis Saya"}]'],
            ['id' => 2, 'profileid' => 2, 'title' => 'Rilis Lain', 'primaryartists' => null],
            ['id' => 3, 'profileid' => 1, 'title' => 'Tanpa Laporan', 'primaryartists' => null],
        ]);
        DB::table('tracks')->insert(['id' => 1, 'profileid' => 1, 'title' => 'Lagu Saya']);
        DB::table('release_track')->insert(['release_id' => 1, 'track_id' => 1]);
        DB::table('report_aggregate_multi')->where('profileid', 1)->whereIn('sales_month', ['2025-04-01', '2026-05-01'])->update(['trackid'=>1]);
        DB::table('report_aggregate_tracks')->insert([
            ['profileid' => 1, 'trackid' => 1, 'sales_month' => '2026-05-01', 'quantity' => 200, 'client_rupiah_exact' => 100],
            ['profileid' => 1, 'trackid' => 1, 'sales_month' => '2025-04-01', 'quantity' => 100, 'client_rupiah_exact' => 50],
            ['profileid' => 2, 'trackid' => 1, 'sales_month' => '2026-05-01', 'quantity' => 999, 'client_rupiah_exact' => 999],
        ]);
        if ($normalized) {
            (require database_path('migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
            RelationalFields::resetSchemaCache();
            RelationalFields::write('tracks', 1, 'primaryartists', [(object) ['name' => 'Artis Saya']]);
            $this->assertFalse(Schema::hasColumn('releases', 'primaryartists'));
            $this->assertFalse(Schema::hasColumn('tracks', 'primaryartists'));
        }
    }

    public function test_creations_do_not_inflate_streams_but_keep_their_royalties(): void
    {
        DB::table('report_aggregate_multi')->insert([
            ['profileid'=>1,'sales_month'=>'2026-05-01','platform'=>'TikTok','quantity'=>5000,'client_rupiah_exact'=>12,'sales_type'=>'CREATION'],
            ['profileid'=>1,'sales_month'=>'2026-05-01','platform'=>'TikTok','quantity'=>-100,'client_rupiah_exact'=>-2,'sales_type'=>' creation '],
            ['profileid'=>1,'sales_month'=>'2026-05-01','platform'=>'TikTok','quantity'=>40,'client_rupiah_exact'=>3,'sales_type'=>'Download'],
            ['profileid'=>2,'sales_month'=>'2026-05-01','platform'=>'TikTok','quantity'=>99999,'client_rupiah_exact'=>999,'sales_type'=>'CREATION'],
        ]);
        $data = $this->analytics();
        $this->assertSame(380, $data['currentPeriod']);
        $this->assertSame(4900, $data['creationTotal']);
        $this->assertSame(40, $data['otherUnitsTotal']);
        $this->assertEquals(203, $data['periodRevenue']);
        $tiktok = $data['platformRows']->firstWhere('platform','TikTok');
        $this->assertEquals(0, $tiktok->streams);
        $this->assertEquals(4900, $tiktok->creations);
        $this->assertEquals(40, $tiktok->other_units);
    }

    public function test_client_analytics_keeps_video_views_and_both_advertising_categories_separate(): void
    {
        $this->seedCatalogue(false);
        $types = ['STREAMING'=>11,'CREATION'=>22,'VIDEO_VIEW'=>33,'PROMOTIONADVERTISING'=>44,'NONPROMOTIONADVERTISING'=>55];
        foreach ($types as $type => $quantity) DB::table('report_aggregate_multi')->insert(['profileid'=>1,'trackid'=>1,'sales_month'=>'2026-05-01','platform'=>'Mixed Platform','sales_type'=>$type,'quantity'=>$quantity,'client_rupiah_exact'=>$quantity]);
        DB::table('report_aggregate_multi')->insert(['profileid'=>2,'sales_month'=>'2026-05-01','platform'=>'Mixed Platform','sales_type'=>'VIDEO_VIEW','quantity'=>9999,'client_rupiah_exact'=>9999]);
        $data = $this->analytics();
        $platform = $data['platformRows']->firstWhere('platform','Mixed Platform');
        foreach (['streams'=>11,'creations'=>22,'video_views'=>33,'promotion_advertising'=>44,'nonpromotion_advertising'=>55,'other_units'=>0] as $metric => $quantity) {
            $this->assertEquals($quantity, $platform->$metric, $metric);
            $this->assertEquals($quantity + ($metric === 'streams' ? 380 : 0), $data['usageBreakdown'][$metric], $metric);
        }
        $this->assertEquals(355, $data['periodRevenue']);
        $release = $data['releases']->firstWhere('id',1);
        $this->assertEquals(311, $release->streams);
        $this->assertEquals(33, $release->video_views);
        $this->assertEquals(44, $release->promotion_advertising);
        $this->assertEquals(55, $release->nonpromotion_advertising);
        Schema::table('tracks', fn ($table) => $table->text('primaryartists')->nullable());
        $response = app(AnalyticController::class)->get_tracks(Request::create('/analytics/tracks','GET',['start'=>0,'length'=>10]));
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $track = $response->getData(true)['data'][0];
        $this->assertEquals(311, $track['views']);
        $this->assertEquals(33, $track['video_views']);
        $this->assertEquals(44, $track['promotion_advertising']);
        $this->assertEquals(55, $track['nonpromotion_advertising']);
    }

    public function test_normalized_release_analytics_preserve_artists_totals_and_account_scope(): void
    {
        $this->seedCatalogue(true);
        $data = $this->analytics(['date_range' => 'last_6_months']);
        $this->assertSame(2, $data['releases']->total());
        $release = $data['releases']->first();
        $this->assertSame('Rilis Saya', $release->title);
        $this->assertTrue($release->relationLoaded('relationalFieldRows'));
        $this->assertSame('Artis Saya', $release->primaryartists[0]->name);
        $this->assertEquals(200, $release->streams);
        $this->assertEquals(100, $release->revenue);
        $this->assertEquals(0, $data['releases']->last()->streams);
    }

    public function test_legacy_release_analytics_still_read_artist_names(): void
    {
        $this->seedCatalogue(false);
        $release = $this->analytics()['releases']->first();
        $this->assertSame('Artis Saya', $release->primaryartists[0]->name);
        $this->assertEquals(300, $release->streams);
    }

    public function test_normalized_track_endpoint_returns_credits_without_other_account_totals(): void
    {
        $this->seedCatalogue(true);
        $response = app(AnalyticController::class)->get_tracks(Request::create('/analytics/tracks', 'GET', [
            'start' => 0, 'length' => 10, 'search' => ['value' => 'Lagu'],
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame(1, $data['recordsTotal']);
        $this->assertSame(1, $data['recordsFiltered']);
        $this->assertSame('Artis Saya', $data['data'][0]['primaryartists'][0]['name']);
        $this->assertEquals(300, $data['data'][0]['views']);
    }
}
