<?php

namespace Tests\Feature;

use App\Models\User\Admin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivityLogFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'log_filter_testing','database.connections.log_filter_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'']]);
        Carbon::setTestNow('2026-11-07 12:00:00');
        Log::setDefaultDriver('null');
        Schema::create('system_settings',function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        foreach (['users','user_admin'] as $table) Schema::create($table,function ($t) { $t->id(); $t->string('name'); });
        Schema::create('user_profiles',function ($t) { $t->id(); $t->integer('userid'); });
        Schema::create('logs',function ($t) { $t->id(); $t->string('level'); $t->string('message'); $t->integer('userid')->nullable(); $t->integer('admin_user_id')->nullable(); $t->timestamps(); });
        DB::table('user_admin')->insert(['id'=>1,'name'=>'AdminOne']);
        foreach ([
            ['info','regular today','2026-11-07 00:00:00'],
            ['warning','needle warning','2026-11-07 11:00:00'],
            ['error','needle error','2026-11-06 23:59:59'],
            ['critical','critical Monday','2026-11-02 00:00:00'],
            ['info','old info','2026-11-01 23:59:59'],
            ['warning','future warning','2026-11-08 00:00:00'],
        ] as [$level,$message,$date]) DB::table('logs')->insert(['level'=>$level,'message'=>$message,'created_at'=>$date,'updated_at'=>$date,'admin_user_id'=>1]);
        $admin=new class extends Admin { public function hasPermission(string $permission): bool { return true; } };
        $admin->forceFill(['id'=>1,'name'=>'AdminOne','email_verified_at'=>now()]);
        $admin->setRelation('unreadNotifications',collect());
        $this->actingAs($admin,'admin');
    }

    protected function tearDown(): void { Carbon::setTestNow(); DB::purge('log_filter_testing'); parent::tearDown(); }

    private function rows(string $filter, array $extra=[])
    {
        return $this->getJson(route('admin.logactivity.get_data',array_replace_recursive(['filter'=>$filter,'length'=>10,'order'=>[['column'=>3,'dir'=>'desc']]],$extra)))->assertOk();
    }

    public function test_date_and_attention_cards_match_summary_counts_and_newest_order(): void
    {
        foreach (['today'=>[2,1],'this_week'=>[2,1,3,4],'attention'=>[6,2,3,4],'all'=>[6,2,1,3,4,5]] as $filter=>$ids) {
            $response=$this->rows($filter);
            $this->assertSame($ids,array_column($response->json('data'),'id'),$filter);
            $response->assertJsonPath('recordsTotal',6)->assertJsonPath('recordsFiltered',count($ids));
        }
        $data=app(\App\Http\Controllers\Admin\LogActivityController::class)->index(\Illuminate\Http\Request::create('/admin/logactivity'))->getData();
        $this->assertSame(['today'=>2,'this_week'=>4,'total'=>6,'attention'=>4],$data['activitySummary']);
    }

    public function test_search_and_pagination_remain_inside_selected_card_filter(): void
    {
        $this->rows('today',['search'=>['value'=>'needle']])->assertJsonPath('recordsFiltered',1)->assertJsonPath('data.0.id',2);
        $this->rows('attention',['search'=>['value'=>'AdminOne']])->assertJsonPath('recordsFiltered',4);
        $this->rows('attention',['start'=>1,'length'=>1])->assertJsonPath('recordsFiltered',4)->assertJsonCount(1,'data')->assertJsonPath('data.0.id',2);
        $this->rows('today',['order'=>[['column'=>3,'dir'=>'asc']]])->assertJsonPath('data.0.id',1);
    }

    public function test_cards_link_to_filters_and_selected_filter_is_visible(): void
    {
        $page=$this->get(route('admin.logactivity.index',['filter'=>'attention']))->assertOk();
        $page->assertSee('Filter aktif:')->assertSee('Perlu perhatian');
        foreach (['all','today','this_week','attention'] as $filter) $page->assertSee(route('admin.logactivity.index',['filter'=>$filter]),false);
        $dom=new \DOMDocument; @$dom->loadHTML($page->getContent());
        $selected=(new \DOMXPath($dom))->query('//a[@class="activity-summary-link" and @aria-current="page"]');
        $this->assertSame(1,$selected->length);
        $this->assertStringContainsString('filter=attention',$selected->item(0)->getAttribute('href'));
    }

    public function test_invalid_filter_is_rejected_before_query(): void
    {
        $this->getJson(route('admin.logactivity.get_data',['filter'=>'arbitrary']))->assertUnprocessable()->assertJsonValidationErrors('filter');
    }
}
