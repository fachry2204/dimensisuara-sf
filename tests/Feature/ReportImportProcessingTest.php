<?php

namespace Tests\Feature;

use App\Jobs\Reporting\ProcessDetectionPerBatch;
use App\Models\Reporting\ReportImport;
use App\Models\Reporting\ReportRow;
use App\Models\User\Admin;
use App\Services\ReportService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportImportProcessingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'report_processing_testing','database.connections.report_processing_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        Schema::create('system_settings',function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('user_profiles',function ($t) { $t->id(); });
        Schema::create('tracks',function ($t) { $t->id(); $t->integer('profileid'); $t->string('isrc'); });
        Schema::create('report_imports',function ($t) { $t->id(); $t->string('status'); $t->string('batch_id')->nullable(); $t->integer('idr_rate')->nullable(); $t->timestamps(); });
        Schema::create('report_rows',function ($t) {
            $t->id(); $t->integer('report_import_id'); $t->string('status')->default('pending');
            $t->string('isrc')->nullable(); $t->string('region')->default('Indonesia'); $t->string('country')->nullable();
            $t->integer('trackid')->nullable(); $t->integer('profileid')->nullable(); $t->decimal('rupiah_exact',16,4)->default(0); $t->timestamps();
        });
        DB::table('user_profiles')->insert(['id'=>1]);
        DB::table('tracks')->insert(['id'=>1,'profileid'=>1,'isrc'=>'IDTEST123456']);
        DB::table('report_imports')->insert(['id'=>1,'status'=>'pending_import','batch_id'=>'old-batch']);
        $this->admin();
    }

    protected function tearDown(): void { DB::purge('report_processing_testing'); parent::tearDown(); }

    private function admin(bool $allowed = true): void
    {
        $admin = new class extends Admin {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission !== 'upload-reporting' || $this->allowed; }
        };
        $admin->allowed = $allowed;
        $admin->forceFill(['id'=>1,'email_verified_at'=>now()]);
        $this->actingAs($admin,'admin');
    }

    private function row(array $data = []): int
    {
        return DB::table('report_rows')->insertGetId(array_merge(['report_import_id'=>1,'isrc'=>'IDTEST123456'],$data));
    }

    public function test_button_processes_persisted_rows_in_bounded_steps_and_preserves_existing_results(): void
    {
        $existing = $this->row(['status'=>'finalized','rupiah_exact'=>123.45,'country'=>'Existing']);
        foreach (range(1,251) as $i) $this->row();
        $this->postJson(route('admin.releases.reporting.process',1))->assertOk()->assertJsonPath('remaining',1)->assertJsonPath('done',false);
        $this->assertSame('pending_import',ReportImport::find(1)->status);
        $this->assertNull(ReportImport::find(1)->batch_id);
        $this->postJson(route('admin.releases.reporting.process',1))->assertOk()->assertJsonPath('remaining',0)->assertJsonPath('done',true);
        $this->assertSame('pending_review',ReportImport::find(1)->status);
        $this->assertSame(251,ReportRow::where('profileid',1)->where('trackid',1)->count());
        $this->assertEquals(123.45,ReportRow::find($existing)->rupiah_exact);
        $this->assertSame('Existing',ReportRow::find($existing)->country);
        $this->postJson(route('admin.releases.reporting.process',1))->assertUnprocessable();
        $this->assertSame(252,ReportRow::count());
    }

    public function test_unmatched_rows_become_incomplete_and_other_reports_are_untouched(): void
    {
        $id = $this->row(['isrc'=>'UNKNOWN']);
        $other = $this->row(['report_import_id'=>2]);
        $this->postJson(route('admin.releases.reporting.process',1))->assertOk();
        $this->assertSame('incomplete',ReportRow::find($id)->status);
        $this->assertNull(ReportRow::find($id)->profileid);
        $this->assertSame('pending',ReportRow::find($other)->status);
    }

    public function test_stale_queue_jobs_do_not_overwrite_manually_processed_or_completed_rows(): void
    {
        $id = $this->row();
        $job = new ProcessDetectionPerBatch([$id]);
        $this->postJson(route('admin.releases.reporting.process',1))->assertOk();
        DB::table('report_rows')->where('id',$id)->update(['country'=>'Preserved','rupiah_exact'=>200]);
        DB::table('report_imports')->where('id',1)->update(['status'=>'completed']);
        $job->handle();
        $this->assertSame('Preserved',ReportRow::find($id)->country);
        $this->assertEquals(200,ReportRow::find($id)->rupiah_exact);
        $this->assertSame('completed',ReportImport::find(1)->status);
        $this->postJson(route('admin.releases.reporting.process',1))->assertUnprocessable();
    }

    public function test_failed_import_can_resume_but_failed_calculation_cannot_be_misclassified_as_import(): void
    {
        DB::table('report_imports')->where('id',1)->update(['status'=>'failed']);
        $this->postJson(route('admin.releases.reporting.process',1))->assertUnprocessable();
        $this->row();
        $this->postJson(route('admin.releases.reporting.process',1))->assertOk()->assertJsonPath('done',true);
    }

    public function test_process_endpoint_requires_upload_permission(): void
    {
        $id = $this->row();
        $this->admin(false);
        $this->postJson(route('admin.releases.reporting.process',1))->assertForbidden();
        $this->assertSame('pending',ReportRow::find($id)->status);
    }

    public function test_pending_import_cannot_be_finalized_before_detection_finishes(): void
    {
        $this->row();
        $this->expectException(\DomainException::class);
        ReportService::finalize(ReportImport::find(1));
    }

    public function test_processing_controls_are_visible_for_pending_import_but_not_completed_reports(): void
    {
        $report = ReportImport::find(1);
        $html = view('admin.aggregator.reporting.partials.process-import',['report'=>$report,'pendingRows'=>12])->render();
        $this->assertStringContainsString('Proses Laporan',$html);
        $this->assertStringContainsString('12 baris masih Pending.',$html);
        $report->status = 'completed';
        $this->assertSame('',trim(view('admin.aggregator.reporting.partials.process-import',['report'=>$report,'pendingRows'=>0])->render()));
    }
}
