<?php

namespace Tests\Feature;

use App\Http\Controllers\AnalyticController;
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
        foreach (['report_aggregate_platforms', 'report_aggregate_countries', 'report_aggregate_tracks', 'report_aggregate_releases'] as $table) {
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
        DB::table('report_aggregate_platforms')->insert([
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
}
