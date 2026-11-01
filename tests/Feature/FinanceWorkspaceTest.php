<?php
namespace Tests\Feature;

use App\Models\User\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceWorkspaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'finance_workspace_testing','database.connections.finance_workspace_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false]]);
        Log::setDefaultDriver('null');
        DB::connection()->getPdo()->sqliteCreateFunction('Month',fn ($date)=>(int)substr($date,5,2),1);
        Schema::create('system_settings',fn($t)=>[$t->string('key')->primary(),$t->text('value'),$t->timestamps()]);
        Schema::create('user_profiles',fn($t)=>[$t->id(),$t->integer('userid'),$t->string('firstname'),$t->string('lastname')]);
        DB::table('user_profiles')->insert(['id'=>1,'userid'=>1,'firstname'=>'Studio','lastname'=>'Nusantara']);
        Schema::create('user_admin',fn($t)=>[$t->id(),$t->string('name')]);
        Schema::create('settlements',function($t){$t->id();$t->integer('profileid')->nullable();$t->string('type');$t->integer('year');$t->integer('quarter');$t->string('status');foreach(['gross','aggregator','subtotal','tax','tax2','total'] as $c)$t->decimal($c,18,4);$t->timestamps();});
        foreach(['pending','transferred','carried_over','cancelled'] as $status) DB::table('settlements')->insert(['profileid'=>1,'type'=>'aggregator','year'=>2026,'quarter'=>1,'status'=>$status,'gross'=>1000,'aggregator'=>300,'subtotal'=>700,'tax'=>35,'tax2'=>0,'total'=>665]);
        DB::table('settlements')->insert(['profileid'=>1,'type'=>'publishing','year'=>2026,'quarter'=>1,'status'=>'pending','gross'=>9999,'aggregator'=>0,'subtotal'=>9999,'tax'=>0,'tax2'=>0,'total'=>9999]);
        Schema::create('report_imports',function($t){$t->id();$t->integer('userid')->nullable();$t->string('original_filename');$t->string('source')->default('soundon');$t->string('sheet')->nullable();$t->integer('total_rows')->default(1);$t->string('status');$t->timestamps();});
        foreach(['pending_import','pending_review','pending_rate','pending_calculation','completed','failed'] as $status) DB::table('report_imports')->insert(['original_filename'=>'SoundOn_'.$status.'_2026.xlsx','status'=>$status]);
        Schema::create('report_rows',function($t){$t->id();$t->integer('report_import_id');$t->integer('profileid')->nullable();$t->string('status');$t->date('reporting_month');foreach(['artist_name','track_title','release_title','upc','isrc','platform','region'] as $c)$t->string($c)->default('Demo');$t->integer('quantity')->default(10);foreach(['rupiah_exact','client_rupiah_exact','aggregator_rupiah_exact','rupiah','client_rupiah','aggregator_rupiah'] as $c)$t->decimal($c,18,4)->default(0);});
        DB::table('report_rows')->insert(['report_import_id'=>5,'profileid'=>1,'status'=>'finalized','reporting_month'=>'2026-01-01','rupiah_exact'=>1000,'client_rupiah_exact'=>700,'aggregator_rupiah_exact'=>300]);
        DB::table('report_rows')->insert(['report_import_id'=>5,'profileid'=>null,'status'=>'incomplete','reporting_month'=>'2026-01-01','rupiah_exact'=>99999]);
        $admin=new class extends Admin {public function hasPermission(string $permission): bool{return true;}};
        $admin->forceFill(['id'=>1,'name'=>'Admin Demo','email_verified_at'=>now()]);$admin->setRelation('unreadNotifications',collect());$this->actingAs($admin,'admin');
    }
    protected function tearDown(): void {DB::purge('finance_workspace_testing');parent::tearDown();}

    public function test_transaction_summary_distinguishes_payouts_from_gross_and_excludes_other_products(): void
    {
        $response=$this->getJson(route('admin.releases.settlement.get_data'));
        $response->assertOk()->assertJsonPath('summary.count',4)->assertJsonPath('summary.pending',1)->assertJsonPath('summary.transferred',1)->assertJsonPath('summary.carried_over',1)->assertJsonPath('sum_total','2.000')->assertJsonPath('sum_aggregator','600')->assertJsonPath('sum_client','1.400');
        $this->assertEquals(665,$response->json('summary.pending_amount'));
        $this->assertEquals(665,$response->json('summary.transferred_amount'));
        $this->getJson(route('admin.releases.settlement.get_data',['status'=>'transferred']))->assertOk()->assertJsonCount(1,'results')->assertJsonPath('summary.pending',0);
        $this->getJson(route('admin.releases.settlement.get_data',['status'=>'carried_over']))->assertOk()->assertJsonPath('sum_total','0');
        $this->getJson(route('admin.releases.settlement.get_data',['status'=>'unknown']))->assertUnprocessable();
    }
    public function test_reporting_summary_and_earnings_have_clear_scopes(): void
    {
        $page=$this->get(route('admin.releases.reporting.index'));
        $page->assertOk()->assertSee('Jumlah baris sumber, bukan progres calculation')->assertSee('Jalankan worker atau Calculation Manual.')->assertSee('report-batch-status',false);
        $this->getJson(route('admin.releases.reporting.get_data'))->assertOk()->assertJsonCount(1,'results')->assertJsonPath('sum_total','1.000');
    }
    public function test_pages_keep_existing_actions_and_escape_table_text(): void
    {
        $page=$this->get(route('admin.releases.settlement.index'));
        $page->assertOk()->assertSee('Nominal Transfer')->assertSee('transaction-pending-amount',false)->assertSee('generate-transaction-modal',false)->assertSee('admin-finance.css',false);
        $export=$this->post(route('admin.releases.settlement.export'),['status'=>'transferred']);
        $export->assertOk();
        $this->assertStringNotContainsString('9999',$export->streamedContent());

    }
}
