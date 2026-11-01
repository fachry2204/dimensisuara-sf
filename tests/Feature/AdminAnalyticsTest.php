<?php
namespace Tests\Feature;

use App\Http\Controllers\Admin\AnalyticsController;
use App\Models\User\Admin;
use App\Services\AdminAnalytics;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'admin_analytics_testing','database.connections.admin_analytics_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false,
        ]]);
        Carbon::setTestNow('2026-11-02 12:00:00');
        Log::setDefaultDriver('null');
        Schema::create('system_settings',fn ($t) => [$t->string('key')->primary(),$t->text('value'),$t->timestamps()]);
        Schema::create('report_imports',fn ($t) => [$t->id(),$t->string('status')]);
        Schema::create('user_profiles',fn ($t) => [$t->id(),$t->integer('userid'),$t->string('firstname'),$t->string('lastname')]);
        Schema::create('releases',fn ($t) => [$t->id(),$t->integer('profileid'),$t->string('title'),$t->string('status'),$t->string('upc')->nullable()]);
        Schema::create('tracks',fn ($t) => [$t->id(),$t->string('isrc')]);
        Schema::create('release_track',fn ($t) => [$t->integer('release_id'),$t->integer('track_id')]);
        Schema::create('filestorage',fn ($t) => [$t->id(),$t->integer('owner_id'),$t->string('owner_type'),$t->string('type')]);
        Schema::create('report_rows',function ($t) {
            $t->id();$t->integer('report_import_id');$t->string('status');
            foreach (['profileid','trackid','releaseid'] as $field) $t->integer($field)->nullable();
            foreach (['upc','isrc','artist_name','track_title','country','platform'] as $field) $t->string($field)->nullable();
            $t->date('sales_month');$t->integer('quantity');
            foreach (['rupiah_exact','client_rupiah_exact','aggregator_rupiah_exact'] as $field) $t->decimal($field,18,4);
        });
        DB::table('report_imports')->insert([['id'=>1,'status'=>'completed'],['id'=>2,'status'=>'pending_calculation']]);
        DB::table('user_profiles')->insert([['id'=>1,'userid'=>11,'firstname'=>'Studio','lastname'=>'Nusantara'],['id'=>2,'userid'=>12,'firstname'=>'Indie','lastname'=>'Music']]);
        foreach ([1,2,3,4] as $id) DB::table('releases')->insert(['id'=>$id,'profileid'=>$id===3?2:1,'title'=>['','Bayang Semu Yang Hilang','Cinta Membawamu Kembali','Aku Tak Siap','Rilis Tanpa Laporan'][$id],'status'=>'released','upc'=>'00'.$id]);
        DB::table('tracks')->insert([['id'=>1,'isrc'=>'IDONE'],['id'=>2,'isrc'=>'IDTWO']]);
        DB::table('release_track')->insert([['release_id'=>1,'track_id'=>1],['release_id'=>2,'track_id'=>1],['release_id'=>3,'track_id'=>2]]);
        $this->row();
        $this->row(['profileid'=>2,'trackid'=>2,'releaseid'=>3,'upc'=>'003','isrc'=>'IDTWO','sales_month'=>'2026-02-01','quantity'=>200,'rupiah_exact'=>2000,'platform'=>'YouTube Music','country'=>'Malaysia']);
        $this->row(['upc'=>'UNKNOWN','quantity'=>5,'rupiah_exact'=>50]);
        $this->row(['quantity'=>-10,'rupiah_exact'=>-100]);
        $this->row(['sales_month'=>'2025-12-01','quantity'=>50,'rupiah_exact'=>500]);
        $this->row(['report_import_id'=>2,'quantity'=>9999,'rupiah_exact'=>99999]);
        $this->row(['status'=>'incomplete','profileid'=>null,'trackid'=>null,'isrc'=>'NOACCOUNT','quantity'=>8888,'rupiah_exact'=>88888]);
        $this->row(['status'=>'pending','quantity'=>7777,'rupiah_exact'=>77777]);
        $this->row(['report_import_id'=>999,'quantity'=>6666,'rupiah_exact'=>66666]);
        $this->admin();
    }

    protected function tearDown(): void { Carbon::setTestNow(); DB::purge('admin_analytics_testing'); parent::tearDown(); }

    private function admin(bool $allowed=true): void
    {
        $admin=new class extends Admin {
            public bool $allowed=true;
            public function hasPermission(string $permission): bool { return $permission==='admin' || $this->allowed; }
        };
        $admin->allowed=$allowed;
        $admin->forceFill(['id'=>1,'name'=>'Admin Demo','firstname'=>'Admin','lastname'=>'Demo','email_verified_at'=>now()]);
        $admin->setRelation('unreadNotifications',collect());
        $this->actingAs($admin,'admin');
    }

    private function row(array $extra=[]): void
    {
        $data=array_merge(['report_import_id'=>1,'status'=>'finalized','profileid'=>1,'trackid'=>1,'releaseid'=>null,'upc'=>'001','isrc'=>'IDONE','artist_name'=>'Artis A','track_title'=>'Lagu A','sales_month'=>'2026-01-01','quantity'=>100,'rupiah_exact'=>1000,'platform'=>'Spotify','country'=>'Indonesia'],$extra);
        $data['client_rupiah_exact']=$data['rupiah_exact']*.7;
        $data['aggregator_rupiah_exact']=$data['rupiah_exact']*.3;
        DB::table('report_rows')->insert($data);
    }

    private function data(array $query=[]): array
    {
        $request=Request::create('/admin/analytics','GET',array_merge(['start'=>'2026-01','end'=>'2026-02'],$query));
        $this->app->instance('request',$request);
        return app(AnalyticsController::class)->index($request,app(AdminAnalytics::class))->getData();
    }

    public function test_totals_use_only_calculated_owned_rows_and_keep_signed_corrections(): void
    {
        $data=$this->data();
        $this->assertEquals(295,$data['totals']->streams);
        $this->assertEquals(2950,$data['totals']->revenue);
        $this->assertEquals(2065,$data['totals']->user_revenue);
        $this->assertEquals(885,$data['totals']->system_revenue);
        $this->assertEquals(50,$data['previous']->streams);
        $this->assertSame([95.0,200.0],array_column($data['series'],'streams'));
        $this->assertSame([0.0,50.0],array_column($data['series'],'previous_streams'));
        $this->assertEquals(295,$data['platforms']->sum('streams'));
        $this->assertEquals(2950,$data['countries']->sum('revenue'));
        $this->assertSame(1,$data['quality']['incomplete']);
        $this->assertSame(1,$data['quality']['waiting']);
        $this->assertSame(1,$data['quality']['unassigned']);
    }

    public function test_release_mapping_never_multiplies_a_track_and_zero_catalogue_is_included(): void
    {
        $data=$this->data(); $releases=$data['releases']->keyBy('id');
        $this->assertSame(4,$data['releases']->total());
        $this->assertEquals(900,$releases[1]->revenue);
        $this->assertEquals(50,$releases[1]->previous_streams);
        $this->assertEquals(0,$releases[2]->revenue);
        $this->assertEquals(2000,$releases[3]->revenue);
        $this->assertEquals(0,$releases[4]->streams);
        $this->assertEquals(2900,$releases->sum('revenue'));
        $this->row(['releaseid'=>3,'upc'=>'001','quantity'=>1,'rupiah_exact'=>10]); // Another owner's explicit release cannot steal the row.
        $this->assertEquals(910,$this->data()['releases']->keyBy('id')[1]->revenue);
    }

    public function test_filters_comparison_search_and_export_share_the_same_scope(): void
    {
        $data=$this->data(['profile'=>1,'platform'=>'Spotify','country'=>'Indonesia','q'=>'IDONE','compare'=>'none']);
        $this->assertEquals(95,$data['totals']->streams);
        $this->assertSame(2,$data['releases']->total());
        $this->assertNull($data['previous']);
        $this->assertSame('2025-01-01',$this->data(['compare'=>'year'])['filters']['previous_from']);
        $this->get(route('admin.analytics.export',['start'=>'2026-01','end'=>'2026-02','profile'=>2]))->assertOk()->assertDownload('analitik-rilis-2026-01-2026-02.csv');
    }

    public function test_csv_preserves_leading_zero_upc_and_neutralizes_formula_text(): void
    {
        DB::table('releases')->where('id',3)->update(['title'=>'=HYPERLINK("bad")']);
        $response=$this->get(route('admin.analytics.export',['start'=>'2026-01','end'=>'2026-02','profile'=>2]));
        $response->assertOk();$csv=$response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK",$csv);
        $this->assertStringContainsString("'003",$csv);
        $this->assertStringContainsString('2000',$csv);
        $this->assertStringNotContainsString('Bayang Semu',$csv);
    }

    public function test_invalid_filters_and_unauthorized_admin_are_rejected(): void
    {
        $this->getJson(route('admin.analytics.index',['start'=>'2026-03','end'=>'2026-01']))->assertUnprocessable();
        $this->getJson(route('admin.analytics.index',['start'=>'2000-01','end'=>'2026-01']))->assertUnprocessable();
        $this->getJson(route('admin.analytics.export',['sort'=>'DROP TABLE users']))->assertUnprocessable();
        $this->admin(false);
        $this->getJson(route('admin.analytics.index'))->assertForbidden();
        $this->getJson(route('admin.analytics.export'))->assertForbidden();
    }

    public function test_all_tabs_render_and_no_data_is_a_valid_state(): void
    {
        $source=str_replace("@extends('layouts.app')",'',file_get_contents(resource_path('views/admin/analytics/index.blade.php')))."\n@yield('content') @stack('css') @stack('script')";
        foreach (['overview','releases','artists','geography','quality'] as $tab) {
            $html=Blade::render($source,$this->data(['tab'=>$tab]));
            $this->assertStringContainsString('Analitik Semua Rilis',$html);
        }
        DB::table('report_rows')->delete();
        $data=$this->data();
        $this->assertEquals(0,$data['totals']->revenue);
        $this->assertSame(4,$data['releases']->total());
        $this->assertStringContainsString('Belum ada aktivitas',Blade::render($source,$data));
    }
    public function test_full_admin_page_contains_navigation_and_live_charts(): void
    {
        $response=$this->get(route('admin.analytics.index',['start'=>'2026-01','end'=>'2026-02']));
        $response->assertOk()->assertSee('Analitik Semua Rilis')->assertSee('aa-chart-data',false)->assertSee('admin-analytics.js',false);

    }

    public function test_pagination_keeps_filters_and_does_not_change_summary_totals(): void
    {
        foreach (range(5,24) as $id) DB::table('releases')->insert(['id'=>$id,'profileid'=>1,'title'=>'Album '.$id,'status'=>'released','upc'=>'NEW'.$id]);
        $data=$this->data(['profile'=>1,'platform'=>'Spotify']);
        $this->assertSame(23,$data['releases']->total());
        $this->assertSame(15,$data['releases']->count());
        $this->assertEquals(950,$data['totals']->revenue);
        $this->assertStringContainsString('profile=1',$data['releases']->nextPageUrl());
        $this->assertStringContainsString('platform=Spotify',$data['releases']->nextPageUrl());
    }

    public function test_schema_without_releaseid_supports_dashboard_csv_and_safe_release_mapping(): void
    {
        Schema::table('report_rows',fn ($table) => $table->dropColumn('releaseid'));
        $data=$this->data();
        $this->assertEquals(2950,$data['totals']->revenue);
        $releases=$data['releases']->keyBy('id');
        $this->assertEquals(900,$releases[1]->revenue);
        $this->assertEquals(2000,$releases[3]->revenue);
        $this->assertEquals(0,$releases[2]->revenue);
        $this->assertSame(1,$data['quality']['unassigned']);
        $this->assertSame(4,$data['releases']->total());
        $this->get(route('admin.analytics.index',['start'=>'2026-01','end'=>'2026-02']))->assertOk()->assertSee('Analitik Semua Rilis');
        $export=$this->get(route('admin.analytics.export',['start'=>'2026-01','end'=>'2026-02','profile'=>2]));
        $export->assertOk();
        $this->assertStringContainsString('2000',$export->streamedContent());
    }

}
