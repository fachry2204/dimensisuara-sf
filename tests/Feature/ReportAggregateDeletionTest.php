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

    private function usageRestoreFixture(): array
    {
        Schema::table('report_rows', function ($t) {
            $t->string('sales_sub_type')->nullable(); $t->string('upc')->default('12345');
            $t->string('isrc')->default('IDTEST'); $t->decimal('net_revenue',20,12)->default(1.25);
        });
        Schema::table('report_imports', function ($t) { $t->string('source')->default('soundon'); $t->string('sheet')->nullable(); });
        DB::table('report_rows')->where('report_import_id',1)->update(['sales_type'=>null]);
        return array_map(fn ($row)=>(array) $row, DB::table('report_rows')->where('report_import_id',1)->orderBy('id')->get()->all());
    }

    public function test_restore_usage_dry_run_then_apply_is_idempotent_and_preserves_royalties(): void
    {
        $source = $this->usageRestoreFixture();
        $source[0]['sales_type'] = 'CREATION'; $source[0]['sales_sub_type'] = 'UGC';
        ReportService::aggregate();
        $before = DB::table('report_rows')->get()->map(fn ($row)=>(array) $row)->all();
        $dry = ReportService::restoreUsageTypes(1,$source);
        $this->assertSame(1,$dry['changes']);
        $this->assertNull(DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
        $this->assertEquals(10,DB::table('report_aggregate_multi')->where('sales_type','Stream_Failback')->sum('quantity'));
        $result = ReportService::restoreUsageTypes(1,$source,true);
        $this->assertSame(1,$result['changes']);
        $this->assertEquals(10,DB::table('report_aggregate_multi')->where('sales_type','CREATION')->sum('quantity'));
        $this->assertSame('UGC',DB::table('report_rows')->where('report_import_id',1)->value('sales_sub_type'));
        $after = DB::table('report_rows')->get()->map(fn ($row)=>(array) $row)->all();
        foreach ($before as $index=>$row) foreach ($row as $key=>$value) {
            if (!in_array($key,['sales_type','sales_sub_type'],true)) $this->assertEquals($value,$after[$index][$key],$key);
        }
        $this->assertSame(0,ReportService::restoreUsageTypes(1,$source,true)['changes']);
        foreach ($this->dimensions as $name=>$keys) $this->assertEquals(150,DB::table('report_aggregate_'.$name)->sum('rupiah_exact'));
    }

    public function test_restore_rejects_row_mismatch_and_existing_category_conflict(): void
    {
        $source = $this->usageRestoreFixture();
        $source[0]['sales_type']='CREATION'; $source[0]['quantity']=999;
        try { ReportService::restoreUsageTypes(1,$source,true); $this->fail('Mismatch should fail'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('quantity',$error->getMessage()); }
        $this->assertNull(DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
        $source[0]['quantity']=10;
        DB::table('report_rows')->where('report_import_id',1)->update(['sales_type'=>'STREAMING']);
        try { ReportService::restoreUsageTypes(1,$source,true); $this->fail('Conflict should fail'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('berbeda',$error->getMessage()); }
        $this->assertSame('STREAMING',DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
    }

    public function test_restore_rejects_missing_rows_and_does_not_guess_missing_source_categories(): void
    {
        $source = $this->usageRestoreFixture();
        try { ReportService::restoreUsageTypes(1,[],true); $this->fail('Missing row should fail'); }
        catch (\RuntimeException $error) { $this->assertStringContainsString('Jumlah baris',$error->getMessage()); }
        $result = ReportService::restoreUsageTypes(1,$source,true);
        $this->assertSame(0,$result['changes']);
        $this->assertSame(1,$result['missing_types']);
        $this->assertSame(1,$result['missing_subtypes']);
        $this->assertNull(DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
        $this->artisan('reports:restore-sales-types',['report'=>1,'--apply'=>true])->assertFailed();
        $this->assertNull(DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
    }

    public function test_restore_rolls_back_types_when_summary_rebuild_fails(): void
    {
        $source = $this->usageRestoreFixture(); $source[0]['sales_type']='CREATION';
        ReportService::aggregate();
        DB::unprepared("CREATE TRIGGER reject_restore BEFORE INSERT ON report_aggregate_monthly BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        try { ReportService::restoreUsageTypes(1,$source,true); $this->fail('Rebuild should fail'); }
        catch (\Illuminate\Database\QueryException) {}
        $this->assertNull(DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
        $this->assertEquals(150,DB::table('report_aggregate_multi')->sum('rupiah_exact'));
    }

    public function test_command_reads_saved_soundon_file_and_defaults_to_read_only(): void
    {
        $this->usageRestoreFixture();
        $sheet=(new \PhpOffice\PhpSpreadsheet\Spreadsheet)->getActiveSheet();
        $sheet->fromArray([
            ['Sales Period','Reporting Period','Track Title','ISRC','Track Artists','UPC Code','Album Title','Store Name','Sales Region','Units of Sold','Final Royalty','Sales Type','Sales Sub Type'],
            ['2026-01','2026-02','Track','IDTEST','Artist','12345','Album','Spotify','ID',10,1.25,'VIDEO_VIEW','PGC'],
        ]);
        Storage::disk('local')->makeDirectory('reports');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet->getParent()))->save(Storage::disk('local')->path('reports/restore.xlsx'));
        DB::table('filestorage')->insert(['owner_type'=>ReportImport::class,'owner_id'=>1,'disk'=>'local','identifier'=>'reports/restore.xlsx']);
        $exit = \Illuminate\Support\Facades\Artisan::call('reports:restore-sales-types',['report'=>1]);
        $this->assertSame(0,$exit,\Illuminate\Support\Facades\Artisan::output());
        $this->assertNull(DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
        $this->artisan('reports:restore-sales-types',['report'=>1,'--apply'=>true])->assertSuccessful();
        $this->assertSame('VIDEO_VIEW',DB::table('report_rows')->where('report_import_id',1)->value('sales_type'));
        $this->assertSame('PGC',DB::table('report_rows')->where('report_import_id',1)->value('sales_sub_type'));
        $this->assertEquals(10,DB::table('report_aggregate_multi')->where('sales_type','VIDEO_VIEW')->sum('quantity'));
        Storage::disk('local')->delete('reports/restore.xlsx');
    }

    public function test_reporting_rows_and_csv_keep_creation_separate_from_stream_quantity(): void
    {
        Schema::table('report_rows', function ($t) {
            foreach (['upc','isrc','track_title','release_title','region'] as $column) $t->string($column)->nullable();
        });
        Schema::create('user_profiles',function ($t) { $t->id(); $t->string('firstname')->nullable(); $t->string('lastname')->nullable(); });
        DB::connection()->getPdo()->sqliteCreateFunction('Month',fn($date)=>(int)date('n',strtotime($date)),1);
        $this->row(1,25,500,['sales_type'=>'CREATION']);
        $this->row(1,3,3,['sales_type'=>null]);
        $controller = app(\App\Http\Controllers\Admin\Aggregator\ReportingController::class);
        $result = $controller->get_data(\Illuminate\Http\Request::create('/reporting/data'))->getData(true);
        $this->assertEquals(15, array_sum(array_column($result['results'],'views')));
        $this->assertEquals(500, array_sum(array_column($result['results'],'creations')));
        $this->assertEquals(3, array_sum(array_column($result['results'],'other_units')));
        $summary = $controller->show(1)->getData();
        $this->assertEquals(10, $summary['total_views']);
        $this->assertEquals(500, $summary['creations']);
        $this->assertEquals(3, $summary['other_units']);
        $response = $controller->export(\Illuminate\Http\Request::create('/reporting/export'));
        ob_start(); $response->sendContent(); $csv = ob_get_clean();
        $this->assertStringContainsString('CREATION', $csv);
        $this->assertStringContainsString('Unit lainnya', $csv);
        $lines = array_map(fn ($line) => str_getcsv($line, ';'),explode("\n",trim($csv)));
        $this->assertCount(count($lines[0]), $lines[1]);
    }

    public function test_reporting_json_summary_and_csv_have_individual_columns_for_each_sales_type(): void
    {
        Schema::table('report_rows',function ($t) { foreach (['upc','isrc','track_title','release_title','region'] as $column) $t->string($column)->nullable(); });
        Schema::create('user_profiles',function ($t) { $t->id(); $t->string('firstname')->nullable(); $t->string('lastname')->nullable(); });
        DB::connection()->getPdo()->sqliteCreateFunction('Month',fn($date)=>(int)date('n',strtotime($date)),1);
        foreach (['CREATION'=>22,'VIDEO_VIEW'=>33,'PROMOTIONADVERTISING'=>44,'NONPROMOTIONADVERTISING'=>55] as $type=>$quantity) $this->row(1,$quantity,$quantity,['sales_type'=>$type]);
        $controller = app(\App\Http\Controllers\Admin\Aggregator\ReportingController::class);
        $data = $controller->get_data(\Illuminate\Http\Request::create('/reporting/data'))->getData(true);
        $summary = $controller->show(1)->getData();
        foreach (['creations'=>22,'video_views'=>33,'promotion_advertising'=>44,'nonpromotion_advertising'=>55] as $metric=>$quantity) {
            $this->assertEquals($quantity, array_sum(array_column($data['results'],$metric)));
            $this->assertEquals($quantity, $summary['usageBreakdown'][$metric]);
        }
        $this->assertEquals(15, array_sum(array_column($data['results'],'views')));
        $response = $controller->export(\Illuminate\Http\Request::create('/reporting/export'));
        ob_start(); $response->sendContent(); $csv = ob_get_clean();
        $lines = array_map(fn($line)=>str_getcsv($line,';'),explode("\n",trim($csv)));
        foreach (\App\Services\ReportUsage::LABELS as $metric=>$label) {
            $column = array_search($label,$lines[0],true);
            $this->assertNotFalse($column,$label);
            $expected = ['streams'=>15,'creations'=>22,'video_views'=>33,'promotion_advertising'=>44,'nonpromotion_advertising'=>55,'other_units'=>0][$metric];
            $this->assertEquals($expected,array_sum(array_column(array_slice($lines,1),$column)));
        }
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
