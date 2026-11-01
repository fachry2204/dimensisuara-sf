<?php

namespace Tests\Feature;

use App\Jobs\Reporting\ProcessCalculationPerBatch;
use App\Models\File;
use App\Models\Release;
use App\Models\Reporting\ReportImport;
use App\Models\Reporting\ReportRow;
use App\Models\User\Admin;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportManualCalculationTest extends TestCase
{
    private array $dimensions = [
        'multi'=>['profileid','trackid','platform','country','sales_type','artist_name','reporting_month','sales_month'],
        'tracks'=>['profileid','trackid','reporting_month','sales_month'],
        'platforms'=>['profileid','platform','reporting_month','sales_month'],
        'countries'=>['profileid','country','reporting_month','sales_month'],
        'artists'=>['profileid','artist_name','reporting_month','sales_month'],
        'sales'=>['profileid','sales_type','reporting_month','sales_month'],
        'monthly'=>['profileid','reporting_month','sales_month'],
        'releases'=>['profileid','releaseid','reporting_month','sales_month'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'aggregate_deletion_testing','database.connections.aggregate_deletion_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        Storage::fake('local');
        Schema::create('system_settings',function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('report_imports',function ($t) { $t->id(); $t->string('status'); $t->integer('idr_rate')->nullable(); $t->string('batch_id')->nullable(); $t->string('original_filename')->default('source.xlsx'); $t->timestamps(); });
        // Deliberately omit cascade constraints to cover older imported databases.
        Schema::create('report_rows',function ($t) {
            $t->id(); $t->integer('report_import_id'); $t->string('status')->default('finalized');
            foreach (['profileid','trackid','releaseid'] as $field) $t->integer($field)->nullable();
            foreach (['platform','country','sales_type','artist_name'] as $field) $t->string($field)->nullable();
            $t->date('reporting_month'); $t->date('sales_month');
            $t->integer('quantity')->default(0);
            foreach (['rupiah_exact','client_rupiah_exact','aggregator_rupiah_exact'] as $field) $t->decimal($field,18,4)->default(0);
            $t->integer('idr_rate')->nullable(); $t->decimal('net_revenue',18,4)->default(1);
            foreach (['rupiah','client_rupiah','aggregator_rupiah','rate'] as $field) $t->decimal($field,18,4)->nullable();
            $t->timestamps();
        });
        Schema::create('user_profiles',function ($t) { $t->id(); $t->integer('aggregator_rate')->default(70); });
        DB::table('user_profiles')->insert(['id'=>1,'aggregator_rate'=>70]);
        foreach ($this->dimensions as $name=>$keys) Schema::create('report_aggregate_'.$name,function ($t) use ($keys) {
            $t->id();
            foreach ($keys as $key) {
                if (in_array($key,['profileid','trackid','releaseid'])) $t->integer($key)->nullable();
                else $t->string($key)->nullable();
            }
            $t->integer('quantity');
            foreach (['rupiah_exact','client_rupiah_exact','aggregator_rupiah_exact'] as $field) $t->decimal($field,18,4)->default(0);
            $t->unique($keys);
        });
        Schema::create('filestorage',function ($t) { $t->id(); $t->string('owner_type'); $t->integer('owner_id'); $t->string('disk'); $t->string('identifier'); $t->string('backup_identifier')->nullable(); $t->timestamps(); });
        foreach ([1,2] as $id) DB::table('report_imports')->insert(['id'=>$id,'status'=>'completed']);
        DB::table('report_imports')->where('id',1)->update(['status'=>'pending_calculation','idr_rate'=>10000,'batch_id'=>'old-batch']);
        $this->admin();
    }

    protected function tearDown(): void { DB::purge('aggregate_deletion_testing'); parent::tearDown(); }

    private function admin(bool $allowed = true): void
    {
        $admin = new class extends Admin {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission !== 'upload-reporting' || $this->allowed; }
        };
        $admin->allowed=$allowed;
        $admin->forceFill(['id'=>9,'email_verified_at'=>now()]);
        $this->actingAs($admin,'admin');
    }

    private function calculationRow(array $data = []): int
    {
        return DB::table('report_rows')->insertGetId(array_merge([
            'report_import_id'=>1,'profileid'=>1,'trackid'=>1,'releaseid'=>1,'status'=>'finalized',
            'reporting_month'=>'2026-03-01','sales_month'=>'2026-02-01',
            'platform'=>'Spotify','country'=>'ID','sales_type'=>'Stream','artist_name'=>'Artist','quantity'=>1,
        ],$data));
    }

    public function test_manual_calculation_resumes_and_publishes_without_queue_or_duplicate_totals(): void
    {
        foreach (range(1,251) as $i) $this->calculationRow();
        $preserved = $this->calculationRow(['idr_rate'=>10000,'net_revenue'=>2,'rupiah_exact'=>20000,'client_rupiah_exact'=>14000,'aggregator_rupiah_exact'=>6000]);
        $unknown = $this->calculationRow(['profileid'=>null,'trackid'=>null,'status'=>'incomplete','net_revenue'=>3]);
        $other = $this->calculationRow(['report_import_id'=>2,'rupiah_exact'=>999,'client_rupiah_exact'=>699,'aggregator_rupiah_exact'=>300]);
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertOk()->assertJsonPath('remaining',2)->assertJsonPath('done',false);
        $this->assertSame('pending_calculation',ReportImport::find(1)->status);
        $this->assertNull(ReportImport::find(1)->batch_id);
        $this->assertSame(0,DB::table('report_aggregate_monthly')->count());
        // A stale worker can finish the remaining rows; manual completion still rebuilds summaries.
        (new ProcessCalculationPerBatch([$preserved,$unknown]))->handle();
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertOk()->assertJsonPath('done',true);
        $this->assertSame('completed',ReportImport::find(1)->status);
        $this->assertEquals(20000,ReportRow::find($preserved)->rupiah_exact);
        $this->assertEquals(30000,ReportRow::find($unknown)->rupiah_exact);
        $this->assertSame('incomplete',ReportRow::find($unknown)->status);
        $this->assertEquals(999,ReportRow::find($other)->rupiah_exact);
        $total=DB::table('report_aggregate_monthly')->sum('rupiah_exact');
        $this->assertEquals(2530999,$total);
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertOk()->assertJsonPath('done',true);
        (new ProcessCalculationPerBatch([$preserved,$unknown]))->handle();
        $this->assertEquals($total,DB::table('report_aggregate_monthly')->sum('rupiah_exact'));
        $this->assertSame(254,ReportRow::count());
    }

    public function test_manual_calculation_rejects_invalid_stage_rate_and_permission(): void
    {
        $row=$this->calculationRow();
        $this->admin(false);
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertForbidden();
        $this->admin();
        DB::table('report_imports')->where('id',1)->update(['idr_rate'=>0]);
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertUnprocessable();
        DB::table('report_imports')->where('id',1)->update(['idr_rate'=>10000,'status'=>'pending_review']);
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertUnprocessable();
        $this->assertNull(ReportRow::find($row)->idr_rate);
        $this->postJson(route('admin.releases.reporting.calculate',999))->assertNotFound();
    }

    public function test_aggregate_failure_rolls_back_last_step_and_can_be_retried(): void
    {
        $row=$this->calculationRow();
        DB::unprepared("CREATE TRIGGER fail_manual_summary BEFORE INSERT ON report_aggregate_multi BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertStatus(500);
        $this->assertSame('pending_calculation',ReportImport::find(1)->status);
        $this->assertNull(ReportRow::find($row)->idr_rate);
        DB::unprepared('DROP TRIGGER fail_manual_summary');
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertOk()->assertJsonPath('done',true);
        $this->assertEquals(7000,ReportRow::find($row)->client_rupiah_exact);
    }

    public function test_zero_percent_share_and_rate_changes_are_handled_safely(): void
    {
        $row=$this->calculationRow();
        DB::table('user_profiles')->where('id',1)->update(['aggregator_rate'=>0]);
        $this->postJson(route('admin.releases.reporting.calculate',1))->assertOk();
        $this->assertEquals(0,ReportRow::find($row)->client_rupiah_exact);
        $this->assertEquals(10000,ReportRow::find($row)->aggregator_rupiah_exact);
        $this->put(route('admin.releases.reporting.rate',1),['idr_rate'=>20000])->assertSessionHasErrors();
        $this->assertEquals(10000,ReportImport::find(1)->idr_rate);
    }
    public function test_button_is_shown_only_for_calculation_stage(): void
    {
        $report=ReportImport::find(1);
        $render=fn () => \Illuminate\Support\Facades\Blade::render("@include('admin.aggregator.reporting.partials.calculate-manual')", ['report'=>$report]);
        $this->assertStringContainsString('id="calculate-report-manual"',$render());
        $report->status='completed';
        $this->assertStringNotContainsString('id="calculate-report-manual"',$render());
    }

}
