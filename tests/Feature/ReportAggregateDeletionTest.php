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

class ReportAggregateDeletionTest extends TestCase
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
        Schema::create('report_imports',function ($t) { $t->id(); $t->string('status'); $t->string('batch_id')->nullable(); $t->string('original_filename')->default('source.xlsx'); $t->timestamps(); });
        // Deliberately omit cascade constraints to cover older imported databases.
        Schema::create('report_rows',function ($t) {
            $t->id(); $t->integer('report_import_id'); $t->string('status')->default('finalized');
            foreach (['profileid','trackid','releaseid'] as $field) $t->integer($field)->nullable();
            foreach (['platform','country','sales_type','artist_name'] as $field) $t->string($field)->nullable();
            $t->date('reporting_month'); $t->date('sales_month');
            $t->integer('quantity')->default(0);
            foreach (['rupiah_exact','client_rupiah_exact','aggregator_rupiah_exact'] as $field) $t->decimal($field,18,4)->default(0);
            $t->timestamps();
        });
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
        $this->row(1,100,10);
        $this->row(2,50,5);
        $this->admin();
    }

    protected function tearDown(): void { DB::purge('aggregate_deletion_testing'); parent::tearDown(); }

    private function admin(bool $allowed = true): void
    {
        $admin = new class extends Admin {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission !== 'delete-reporting' || $this->allowed; }
        };
        $admin->allowed=$allowed;
        $admin->forceFill(['id'=>9,'email_verified_at'=>now()]);
        $this->actingAs($admin,'admin');
    }

    private function row(int $report, float $revenue, int $quantity, array $extra = []): int
    {
        return DB::table('report_rows')->insertGetId(array_merge([
            'report_import_id'=>$report,'profileid'=>1,'trackid'=>1,'releaseid'=>1,
            'platform'=>'Spotify','country'=>'ID','sales_type'=>'Stream','artist_name'=>'Artist',
            'reporting_month'=>'2026-02-01','sales_month'=>'2026-01-01','quantity'=>$quantity,
            'rupiah_exact'=>$revenue,'client_rupiah_exact'=>$revenue*.7,'aggregator_rupiah_exact'=>$revenue*.3,
        ],$extra));
    }

    public function test_delete_rebuilds_all_dimensions_immediately_and_retains_other_reports(): void
    {
        $this->row(1,200,20,['profileid'=>2,'trackid'=>2,'releaseid'=>2,'platform'=>'YouTube','artist_name'=>'Deleted artist']);
        ReportService::aggregate();
        $this->delete(route('admin.releases.reporting.destroy',1))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull(ReportImport::find(1));
        $this->assertNotNull(ReportImport::find(2));
        $this->assertSame(0,ReportRow::where('report_import_id',1)->count());
        $this->assertSame(1,ReportRow::where('report_import_id',2)->count());
        foreach ($this->dimensions as $name=>$keys) {
            $table = DB::table('report_aggregate_'.$name);
            $this->assertSame(1,$table->count(),$name);
            $this->assertEquals(50,$table->sum('rupiah_exact'),$name);
            $this->assertEquals(35,$table->sum('client_rupiah_exact'),$name);
            $this->assertEquals(5,$table->sum('quantity'),$name);
        }
    }

    public function test_repeated_rebuild_does_not_duplicate_null_dimensions_or_signed_corrections(): void
    {
        $this->row(2,-10,-1,['platform'=>null,'country'=>null,'sales_type'=>null,'artist_name'=>null]);
        $this->row(2,20,2,['platform'=>null,'country'=>null,'sales_type'=>'Stream_Failback','artist_name'=>null]);
        ReportService::aggregate();
        $first=[];
        foreach ($this->dimensions as $name=>$keys) $first[$name] = [DB::table('report_aggregate_'.$name)->count(),DB::table('report_aggregate_'.$name)->sum('rupiah_exact')];
        ReportService::aggregate();
        foreach ($first as $name=>[$count,$sum]) {
            $this->assertSame($count,DB::table('report_aggregate_'.$name)->count());
            $this->assertEquals($sum,DB::table('report_aggregate_'.$name)->sum('rupiah_exact'));
            $this->assertEquals(160,$sum);
        }
    }

    public function test_last_report_deletion_clears_all_derived_rows_and_repair_command_clears_stale_groups(): void
    {
        ReportService::aggregate();
        ReportService::deleteImport(1);
        ReportService::deleteImport(2);
        foreach ($this->dimensions as $name=>$keys) $this->assertSame(0,DB::table('report_aggregate_'.$name)->count());
        DB::table('report_aggregate_monthly')->insert(['profileid'=>9,'reporting_month'=>'2020-01-01','sales_month'=>'2020-01-01','quantity'=>999,'rupiah_exact'=>999]);
        $this->row(999,999,999);
        $this->artisan('reports:rebuild-aggregates')->assertSuccessful();
        foreach ($this->dimensions as $name=>$keys) $this->assertSame(0,DB::table('report_aggregate_'.$name)->count());
        // Raw orphaned rows do not leak into the rebuilt summaries.
        $this->assertSame(1,ReportRow::count());
    }

    public function test_rebuild_failure_rolls_back_batch_rows_and_summaries_and_keeps_source_file(): void
    {
        ReportService::aggregate();
        Storage::disk('local')->put('reports/source.xlsx','original');
        DB::table('filestorage')->insert(['owner_type'=>ReportImport::class,'owner_id'=>1,'disk'=>'local','identifier'=>'reports/source.xlsx']);
        DB::unprepared("CREATE TRIGGER reject_aggregate BEFORE INSERT ON report_aggregate_monthly BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        try { ReportService::deleteImport(1); $this->fail('Expected rebuild failure'); }
        catch (\Illuminate\Database\QueryException) {}
        $this->assertNotNull(ReportImport::find(1));
        $this->assertSame(2,ReportRow::count());
        foreach ($this->dimensions as $name=>$keys) $this->assertEquals(150,DB::table('report_aggregate_'.$name)->sum('rupiah_exact'));
        Storage::disk('local')->assertExists('reports/source.xlsx');
    }

    public function test_source_file_cleanup_does_not_touch_release_media_and_stale_jobs_do_not_restore_deleted_data(): void
    {
        Storage::disk('local')->put('reports/source.xlsx','report');
        Storage::disk('local')->put('releases/cover.jpg','cover');
        DB::table('filestorage')->insert([
            ['owner_type'=>ReportImport::class,'owner_id'=>1,'disk'=>'local','identifier'=>'reports/source.xlsx'],
            ['owner_type'=>Release::class,'owner_id'=>1,'disk'=>'local','identifier'=>'releases/cover.jpg'],
        ]);
        $staleRows = ReportRow::where('report_import_id',1)->get();
        $ids = $staleRows->pluck('id')->all();
        ReportService::deleteImport(1);
        ReportService::calculatePerBatch($staleRows);
        (new ProcessCalculationPerBatch($ids))->handle();
        ReportService::aggregate();
        $this->assertSame(0,ReportRow::where('report_import_id',1)->count());
        $this->assertEquals(50,DB::table('report_aggregate_monthly')->sum('rupiah_exact'));
        Storage::disk('local')->assertMissing('reports/source.xlsx');
        Storage::disk('local')->assertExists('releases/cover.jpg');
        $this->assertSame(1,File::count());
    }

    public function test_unpublished_and_unmapped_rows_are_excluded_and_delete_permission_is_enforced(): void
    {
        DB::table('report_imports')->insert(['id'=>3,'status'=>'pending_calculation']);
        $this->row(3,1000,100);
        $this->row(2,1000,100,['status'=>'incomplete','profileid'=>null]);
        ReportService::aggregate();
        $this->assertEquals(150,DB::table('report_aggregate_monthly')->sum('rupiah_exact'));
        $this->admin(false);
        $this->deleteJson(route('admin.releases.reporting.destroy',1))->assertForbidden();
        $this->assertNotNull(ReportImport::find(1));
    }
}
