<?php

namespace Tests\Feature;

use App\Services\AdminAnalytics;
use App\Services\ReportSubtypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportSubtypesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'subtypes_testing','database.connections.subtypes_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false]]);
        Schema::create('report_imports',function ($t) { $t->id(); $t->string('status'); });
        Schema::create('report_rows',function ($t) {
            $t->id(); $t->integer('report_import_id'); $t->integer('profileid'); $t->integer('trackid')->nullable();
            $t->string('status'); $t->string('platform')->nullable(); $t->string('country')->nullable(); $t->string('sales_type')->nullable(); $t->string('subscription_type')->nullable();
            $t->date('sales_month'); $t->integer('quantity'); $t->decimal('client_rupiah_exact',18,4); $t->decimal('rupiah_exact',18,4);
        });
        DB::table('report_imports')->insert([['id'=>1,'status'=>'completed'],['id'=>2,'status'=>'pending_calculation'],['id'=>3,'status'=>'pending_rate']]);
    }

    protected function tearDown(): void { DB::purge('subtypes_testing'); parent::tearDown(); }

    private function migration()
    {
        return require database_path('migrations/2026_11_07_040000_add_sales_sub_type_to_report_rows.php');
    }

    private function row(array $extra = []): void
    {
        DB::table('report_rows')->insert(array_merge(['report_import_id'=>1,'profileid'=>1,'trackid'=>1,'status'=>'finalized','platform'=>'TikTok','country'=>'ID','sales_type'=>'CREATION','sales_month'=>'2026-05-01','quantity'=>10,'client_rupiah_exact'=>1,'rupiah_exact'=>2],$extra));
    }

    public function test_missing_subtype_stays_unknown_and_migration_preserves_old_records(): void
    {
        $this->row(['subscription_type'=>'INDIVIDUAL']);
        $before = ReportSubtypes::customer(1,'2026-05-01','2026-05-31');
        $this->assertFalse(ReportSubtypes::available());
        $this->assertNull($before->first()->sales_sub_type);
        $this->migration()->up();
        $this->migration()->up();
        $this->assertTrue(ReportSubtypes::available());
        $this->assertNull(DB::table('report_rows')->value('sales_sub_type'));
        $this->assertEquals(10,ReportSubtypes::customer(1,'2026-05-01','2026-05-31')->sum('quantity'));
        $this->migration()->down();
        $this->assertSame('INDIVIDUAL',DB::table('report_rows')->value('subscription_type'));
        $this->assertEquals(1,DB::table('report_rows')->sum('client_rupiah_exact'));
    }

    public function test_parent_type_platform_and_missing_values_are_separate_with_signed_corrections(): void
    {
        $this->migration()->up();
        $this->row(['sales_sub_type'=>'UGC']);
        $this->row(['sales_sub_type'=>' ugc ','quantity'=>-2,'client_rupiah_exact'=>-.2]);
        $this->row(['sales_type'=>'VIDEO_VIEW','sales_sub_type'=>'UGC','quantity'=>20,'client_rupiah_exact'=>2]);
        $this->row(['sales_type'=>'STREAMING','sales_sub_type'=>'DEFAULT','quantity'=>30,'client_rupiah_exact'=>3]);
        $this->row(['sales_type'=>'STREAMING','sales_sub_type'=>null,'subscription_type'=>'PGC','quantity'=>40,'client_rupiah_exact'=>4]);
        $this->row(['sales_type'=>'STREAMING','sales_sub_type'=>'PGC','quantity'=>50,'client_rupiah_exact'=>5]);
        $this->row(['sales_type'=>'STREAMING','sales_sub_type'=>'NEW_VALUE','quantity'=>60,'client_rupiah_exact'=>6]);
        $this->row(['sales_sub_type'=>'UGC','profileid'=>2,'quantity'=>999]);
        $this->row(['sales_sub_type'=>'UGC','sales_month'=>'2025-01-01','quantity'=>555]);
        $this->row(['sales_sub_type'=>'UGC','report_import_id'=>2,'quantity'=>555]);
        $this->row(['sales_sub_type'=>'UGC','status'=>'incomplete','quantity'=>555]);
        $rows = ReportSubtypes::customer(1,'2026-05-01','2026-05-31');
        $this->assertCount(6,$rows);
        $this->assertEquals(208,$rows->sum('quantity'));
        $this->assertEqualsWithDelta(20.8,$rows->sum('earnings'),.00001);
        $this->assertEquals(8,$rows->firstWhere('sales_type','CREATION')->quantity);
        $this->assertEquals(20,$rows->firstWhere('sales_type','VIDEO_VIEW')->quantity);
        $this->assertEquals(30,$rows->firstWhere('sales_sub_type','DEFAULT')->quantity);
        $this->assertEquals(40,$rows->firstWhere('sales_sub_type',null)->quantity);
        $this->assertEquals(60,$rows->firstWhere('sales_sub_type','NEW_VALUE')->quantity);
        $this->row(['sales_sub_type'=>'UGC','platform'=>'Other platform','quantity'=>7]);
        $rows = ReportSubtypes::customer(1,'2026-05-01','2026-05-31');
        $this->assertCount(7,$rows);
        $this->assertEquals(7,$rows->firstWhere('platform','Other platform')->quantity);
    }

    public function test_admin_subtype_breakdown_respects_all_filters_and_completed_report_scope(): void
    {
        $this->migration()->up();
        $this->row(['sales_sub_type'=>'PGC']);
        $this->row(['sales_sub_type'=>'UGC','country'=>'US','quantity'=>99]);
        $this->row(['sales_sub_type'=>'DUO','profileid'=>2,'quantity'=>99]);
        $this->row(['sales_sub_type'=>'BUNDLE','platform'=>'Other','quantity'=>99]);
        $this->row(['sales_sub_type'=>'DEFAULT','report_import_id'=>3,'quantity'=>99]);
        $filters=['from'=>'2026-05-01','to'=>'2026-05-31','profile'=>1,'platform'=>'TikTok','country'=>'ID'];
        $rows = app(AdminAnalytics::class)->subtypes($filters);
        $this->assertCount(1,$rows);
        $this->assertSame('PGC',$rows->first()->sales_sub_type);
        $this->assertEquals(2,$rows->first()->earnings);
    }

    public function test_subtype_csv_keeps_real_values_and_neutralizes_formula_text(): void
    {
        $rows = collect([(object)['platform'=>'=FORMULA','sales_type'=>'CREATION','sales_sub_type'=>'+CUSTOM','quantity'=>-2,'earnings'=>-.2],(object)['platform'=>'TikTok','sales_type'=>'STREAMING','sales_sub_type'=>'DEFAULT','quantity'=>30,'earnings'=>3]]);
        $rows->push((object)['platform'=>'YouTube','sales_type'=>'VIDEO_VIEW','sales_sub_type'=>null,'quantity'=>5,'earnings'=>1]);
        $rows->push((object)['platform'=>'TikTok','sales_type'=>'CREATION','sales_sub_type'=>'NEW_VALUE','quantity'=>2,'earnings'=>1]);
        $response = ReportSubtypes::export($rows,'subtypes.csv');
        ob_start(); $response->sendContent(); $csv = ob_get_clean();
        $lines = array_map('str_getcsv',explode("\n",trim($csv)));
        $this->assertSame('Sales Sub Type',$lines[0][2]);
        $this->assertSame("'=FORMULA",$lines[1][0]);
        $this->assertSame("'+CUSTOM",$lines[1][2]);
        $this->assertEquals(-2,$lines[1][3]);
        $this->assertSame('DEFAULT',$lines[2][2]);
        $this->assertSame('VIDEO VIEW',$lines[3][1]);
        $this->assertSame('-',$lines[3][2]);
        $this->assertSame('NEW VALUE',$lines[4][2]);
    }
}
