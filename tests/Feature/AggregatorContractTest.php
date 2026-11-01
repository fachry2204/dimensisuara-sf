<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User\User;
use App\Models\User\Profile;
use App\Services\AggregatorContractTemplates;
use App\Services\ContractService;
use App\Services\SystemSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AggregatorContractTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/aggregator-contract-test-'.bin2hex(random_bytes(6));
        config([
            'system-settings.path' => $this->directory.'/settings.enc',
            'system-settings.company_signatures_path' => $this->directory.'/signatures',
            'database.default' => 'contract_testing',
            'database.connections.contract_testing' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false],
            'session.driver' => 'array',
        ]);
        $this->app->forgetInstance(SystemSettings::class);
        Schema::create('system_settings', function (Blueprint $table) { $table->string('key')->primary(); $table->text('value'); $table->timestamps(); });
        Schema::create('access_roles', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('email'); $table->string('name'); $table->timestamps();
        });
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id(); $table->integer('userid'); $table->string('firstname'); $table->string('lastname')->nullable();
            $table->string('companyname')->nullable(); $table->string('status'); $table->boolean('is_company')->default(false);
            // MySQL DECIMAL rates are returned as strings, including trailing zeroes.
            $table->boolean('feature_aggregator')->default(true); $table->string('aggregator_rate')->default('70.00');
            $table->string('address1')->nullable(); $table->string('reason')->nullable(); $table->dateTime('delete_by')->nullable(); $table->timestamps();
        });
        Schema::create('contracts', function (Blueprint $table) {
            $table->id(); $table->integer('profileid'); $table->string('type'); $table->integer('number');
            $table->integer('month'); $table->integer('year'); $table->string('sort'); $table->string('docnum');
            $table->nullableMorphs('owner'); $table->longText('generated_html')->nullable(); $table->timestamps();
            $table->unsignedTinyInteger('user_percentage')->nullable(); $table->unsignedTinyInteger('system_percentage')->nullable();
            $table->date('contract_date')->nullable();
        });
        Schema::create('filestorage', function (Blueprint $table) {
            $table->id(); $table->nullableMorphs('owner'); $table->string('type')->nullable();
            $table->integer('profileid'); $table->string('disk'); $table->string('identifier');
            $table->string('original_name'); $table->string('mime_type'); $table->integer('size'); $table->timestamp('uploaded_at'); $table->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'email' => 'client@example.test', 'name' => 'Client']);
        DB::table('user_profiles')->insert(['id' => 1, 'userid' => 1, 'firstname' => 'Client <b>Sample</b>', 'status' => 'verified', 'aggregator_rate' => '85.00']);
        $admin = new class extends User {
            public function hasPermission(string $permission): bool { return true; }
        };
        $admin->forceFill(['id' => 99, 'name' => 'Admin Test', 'email_verified_at' => now()]);
        $admin->setRelation('profile', null);
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        DB::purge('contract_testing');
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function generate(int $number = 1): Contract
    {
        return app(ContractService::class)->store(['profileid' => 1, 'type' => 'aggregator', 'number' => $number, 'month' => 10, 'year' => 2026]);
    }

    private function draft(string $title): string
    {
        return '<h1>'.$title.'</h1><p>[FULLNAME] [USER_PERCENT]% / [SYSTEM_PERCENT]% [COMPANYNAME]</p>';
    }

    public function test_selected_contract_date_is_saved_and_used_in_frozen_document(): void
    {
        $this->post(route('admin.releases.contract.store'), [
            'profileid'=>1, 'type'=>'aggregator', 'number'=>1, 'month'=>10, 'year'=>2026,
            'aggregator_rate'=>75, 'system_rate'=>25, 'contract_date'=>'2026-09-15',
        ])->assertSessionHasNoErrors();
        $contract = Contract::firstOrFail();
        $this->assertSame('2026-09-15', $contract->contract_date->toDateString());
        $this->assertStringContainsString('15 September 2026', $contract->generated_html);
        $this->assertStringContainsString('/X/2026', $contract->docnum);
        $html = $contract->generated_html;
        $this->travel(10)->days();
        $this->assertSame($html, $contract->fresh()->generated_html);
        $this->assertStringContainsString('15 September 2026', ContractService::renderAggregatorHtml($contract, false));
        $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($contract, false));
    }

    public function test_invalid_contract_dates_are_rejected_without_creating_contract(): void
    {
        foreach (['2026-02-30', '15/09/2026', 'invalid'] as $date) {
            $this->post(route('admin.releases.contract.store'), [
                'profileid'=>1, 'type'=>'aggregator', 'number'=>1, 'month'=>10, 'year'=>2026,
                'aggregator_rate'=>75, 'system_rate'=>25, 'contract_date'=>$date,
            ])->assertSessionHasErrors('contract_date');
        }
        $this->assertSame(0, Contract::count());
        $this->assertEquals(85, Profile::find(1)->aggregator_rate);
    }

    public function test_contract_without_date_defaults_to_today_and_legacy_uses_creation_date(): void
    {
        $contract = $this->generate();
        $this->assertSame(now()->toDateString(), $contract->contract_date->toDateString());
        $contract->contract_date = null;
        $contract->created_at = '2020-01-02 10:00:00';
        $this->assertStringContainsString('2 Januari 2020', ContractService::renderAggregatorHtml($contract, false));
    }

    public function test_contract_rate_fields_remove_trailing_decimal_zeroes(): void
    {
        app('request')->setLaravelSession(app('session.store'));
        foreach ([[], ['aggregator_rate'=>'75.00', 'system_rate'=>'25.00']] as $old) {
            session()->flashInput($old);
            $html = view('admin.aggregator.partials.contract-rates', ['initialUserRate'=>'75.00', 'ratePrefix'=>'test', 'errors'=>new \Illuminate\Support\ViewErrorBag()])->render();
            $dom = new \DOMDocument();
            $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $values = [];
            foreach ($dom->getElementsByTagName('input') as $input) $values[$input->getAttribute('name')] = $input->getAttribute('value');
            $this->assertSame(['aggregator_rate'=>'75', 'system_rate'=>'25'], $values);
        }
    }

    public function test_templates_select_personal_or_company_and_snapshot_is_immutable(): void
    {
        app(SystemSettings::class)->save(['aggregator_draft_personal' => $this->draft('Personal'), 'aggregator_draft_company' => $this->draft('Company')]);
        $personal = $this->generate();
        $this->assertStringContainsString('Personal', $personal->generated_html);
        $this->assertStringContainsString('85% / 15%', $personal->generated_html);
        $this->assertStringContainsString('&lt;B&gt;SAMPLE&lt;/B&gt;', $personal->generated_html);
        $this->assertStringNotContainsString('Personal', DB::table('contracts')->value('generated_html'));
        $this->assertArrayNotHasKey('generated_html', $personal->toArray());
        DB::table('user_profiles')->where('id', 1)->update(['is_company' => true, 'companyname' => 'PT Example', 'aggregator_rate' => 90]);
        app(SystemSettings::class)->save(['aggregator_draft_personal' => $this->draft('Changed')]);
        $this->assertSame($personal->generated_html, $personal->fresh()->generated_html);
        $company = $this->generate(2);
        $this->assertStringContainsString('Company', $company->generated_html);
        $this->assertStringContainsString('PT EXAMPLE', $company->generated_html);
        $this->assertStringContainsString('90% / 10%', $company->generated_html);
        $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($company, false));
    }

    public function test_pending_profile_cannot_generate_a_contract(): void
    {
        DB::table('user_profiles')->where('id', 1)->update(['status' => 'pending']);
        try { $this->generate(); $this->fail('Pending user must be rejected.'); }
        catch (\DomainException $exception) { $this->assertStringContainsString('terverifikasi', $exception->getMessage()); }
        $this->assertSame(0, Contract::count());
    }

    public function test_verification_rejects_invalid_split_and_persists_valid_split(): void
    {
        DB::table('user_profiles')->where('id', 1)->update(['status' => 'pending']);
        $this->put(route('admin.users.verify', 1), ['aggregator_rate' => 80, 'system_rate' => 30])->assertSessionHasErrors('aggregator_rate');
        $this->assertSame('pending', Profile::find(1)->status);
        $this->put(route('admin.users.verify', 1), ['aggregator_rate' => 80, 'system_rate' => 20])->assertSessionHasNoErrors();
        $this->assertSame('verified', Profile::find(1)->status);
        $this->assertEquals(80, Profile::find(1)->aggregator_rate);
    }

    public function test_settings_save_both_drafts_and_validation_preserves_old_values(): void
    {
        $this->put(route('admin.system-settings.update'), ['section' => 'contracts', 'aggregator_draft_personal' => $this->draft('Personal'), 'aggregator_draft_company' => $this->draft('Company')])->assertSessionHasNoErrors()->assertRedirect(route('admin.system-settings.index', ['tab' => 'contracts']));
        $before = app(SystemSettings::class)->get('aggregator_draft_company');
        $this->put(route('admin.system-settings.update'), ['section' => 'contracts', 'aggregator_draft_personal' => $this->draft('New'), 'aggregator_draft_company' => '<p>Missing placeholders</p>'])->assertSessionHasErrors('aggregator_draft_company');
        $this->assertSame($before, app(SystemSettings::class)->get('aggregator_draft_company'));
    }

    public function test_draft_markup_cannot_execute_php_or_load_assets(): void
    {
        $service = app(AggregatorContractTemplates::class);
        $clean = $service->validateDraft($this->draft('Safe').'<script>alert(1)</script><img src="file:///etc/passwd"><p onclick="alert(1)">Text</p>', 'draft');
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->validateDraft($this->draft('Unsafe').'<style>body{background:url(file:///secret)}</style>', 'draft');
    }

    public function test_built_in_drafts_have_dynamic_rates(): void
    {
        foreach ([false, true] as $company) {
            $draft = app(AggregatorContractTemplates::class)->get($company);
            $this->assertStringContainsString('[USER_PERCENT]%', $draft);
            $this->assertStringContainsString('[SYSTEM_PERCENT]%', $draft);
            $this->assertNotEmpty(app(AggregatorContractTemplates::class)->validateDraft($draft, 'draft'));
        }
        $contract = $this->generate();
        $this->assertStringContainsString('85%', $contract->generated_html);
        $this->assertStringContainsString('15%', $contract->generated_html);
        $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($contract, false));
    }

    public function test_optional_manual_pdf_is_stored_alongside_generated_contract(): void
    {
        $this->app->useStoragePath($this->directory.'/storage');
        config(['filesystems.disks.local.root' => $this->directory.'/storage/app']);
        $this->post(route('admin.releases.contract.store'), [
            'profileid' => 1, 'type' => 'aggregator', 'number' => 1, 'month' => 10, 'year' => 2026,
            'aggregator_rate' => 85, 'system_rate' => 15,
            'pdf' => \Illuminate\Http\UploadedFile::fake()->createWithContent('manual.pdf', "%PDF-1.4\nManual document"),
        ])->assertSessionHasNoErrors();
        $contract = Contract::firstOrFail();
        $this->assertNotEmpty($contract->generated_html);
        $this->assertSame('application/pdf', $contract->file->mime_type);
        $this->assertFileExists(storage_path('app/'.$contract->file->identifier));
    }

    public function test_duplicate_number_does_not_create_partial_contract(): void
    {
        $this->generate();
        try { $this->generate(); $this->fail('Duplicate number must be rejected.'); }
        catch (\App\Exceptions\Contract\NumberAlreadyUsedException $exception) { $this->assertSame(1, Contract::count()); }
    }

    public function test_company_details_and_signature_are_frozen_in_generated_contract(): void
    {
        File::ensureDirectoryExists($this->directory.'/signatures');
        $signature = 'director-11111111-1111-1111-1111-111111111111.png';
        $image = \Illuminate\Http\UploadedFile::fake()->image('director.png', 160, 80);
        File::copy($image->getRealPath(), $this->directory.'/signatures/'.$signature);
        app(SystemSettings::class)->save([
            'company_name'=>'PT Contoh & Mitra', 'company_director'=>'Direktur Contoh', 'company_address'=>'Alamat Utama',
            'company_contact_name'=>'Kontak Contoh', 'company_contact_address'=>'Alamat Kontak', 'company_phone'=>'0812345678',
            'company_email'=>'contact@example.test', 'company_signature'=>$signature,
        ]);
        $contract = $this->generate();
        $snapshot = $contract->generated_html;
        foreach (['PT Contoh &amp; Mitra','Direktur Contoh','Alamat Utama','Kontak Contoh','Alamat Kontak','0812345678','contact@example.test','data:image/png;base64,'] as $expected) {
            $this->assertStringContainsString($expected, $snapshot);
        }
        $this->assertStringNotContainsString('[PROVIDER_', $snapshot);
        $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($contract, false));
        app(SystemSettings::class)->save(['company_name'=>'PT Berubah', 'company_director'=>'Direktur Baru', 'company_signature'=>'']);
        File::delete($this->directory.'/signatures/'.$signature);
        $this->assertSame($snapshot, $contract->fresh()->generated_html);
        $new = $this->generate(2);
        $this->assertStringContainsString('PT Berubah', $new->generated_html);
        $this->assertStringContainsString('Direktur Baru', $new->generated_html);
        $this->assertStringNotContainsString('data:image/png;base64,', $new->generated_html);
    }

    public function test_company_position_fills_new_contracts_and_existing_snapshot_keeps_original_position(): void
    {
        app(SystemSettings::class)->save(['company_position'=>'Direktur Operasional']);
        $contract = $this->generate();
        $this->assertGreaterThanOrEqual(2, substr_count($contract->generated_html, 'Direktur Operasional'));
        $this->assertStringNotContainsString('[PROVIDER_POSITION]', $contract->generated_html);
        $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($contract, false));
        app(SystemSettings::class)->save(['company_position'=>'Pimpinan Perusahaan']);
        $this->assertStringContainsString('Direktur Operasional', $contract->fresh()->generated_html);
        $new = $this->generate(2);
        $this->assertStringContainsString('Pimpinan Perusahaan', $new->generated_html);
        $this->assertStringNotContainsString('Direktur Operasional', $new->generated_html);
    }

    public function test_generate_form_saves_entered_split_in_contract_pdf_and_profile(): void
    {
        app(SystemSettings::class)->save(['aggregator_draft_personal' => $this->draft('Split')]);
        $this->post(route('admin.releases.contract.store'), [
            'profileid'=>1, 'type'=>'aggregator', 'number'=>1, 'month'=>10, 'year'=>2026,
            'aggregator_rate'=>82, 'system_rate'=>18,
        ])->assertSessionHasNoErrors();
        $contract = Contract::firstOrFail();
        $this->assertSame(82, $contract->user_percentage);
        $this->assertSame(18, $contract->system_percentage);
        $this->assertEquals(82, Profile::find(1)->aggregator_rate);
        $this->assertStringContainsString('82% / 18%', $contract->generated_html);
        $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($contract, false));
        DB::table('user_profiles')->where('id', 1)->update(['aggregator_rate'=>95]);
        $this->assertSame(82, $contract->fresh()->user_percentage);
        $this->assertStringContainsString('82% / 18%', $contract->fresh()->generated_html);
    }

    public function test_generate_form_accepts_whole_percentages_from_decimal_profile(): void
    {
        $this->post(route('admin.releases.contract.store'), [
            'profileid'=>1, 'type'=>'aggregator', 'number'=>1, 'month'=>10, 'year'=>2026,
            'aggregator_rate'=>'75.00', 'system_rate'=>'25.00',
        ])->assertSessionHasNoErrors();
        $contract = Contract::firstOrFail();
        $this->assertSame(75, $contract->user_percentage);
        $this->assertSame(25, $contract->system_percentage);
        $this->assertEquals(75, Profile::find(1)->aggregator_rate);
        $this->assertStringContainsString('75%', $contract->generated_html);
    }

    public function test_verification_accepts_whole_percentages_from_decimal_profile(): void
    {
        DB::table('user_profiles')->where('id', 1)->update(['status'=>'pending']);
        $this->put(route('admin.users.verify', 1), ['aggregator_rate'=>'75.00', 'system_rate'=>'25.00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('verified', Profile::find(1)->status);
        $this->assertEquals(75, Profile::find(1)->aggregator_rate);
    }

    public function test_generate_form_rejects_missing_or_invalid_split_without_changing_profile(): void
    {
        $base = ['profileid'=>1, 'type'=>'aggregator', 'number'=>1, 'month'=>10, 'year'=>2026];
        foreach ([[], ['aggregator_rate'=>80,'system_rate'=>30], ['aggregator_rate'=>-1,'system_rate'=>101], ['aggregator_rate'=>'82.5','system_rate'=>'17.5']] as $rates) {
            $this->post(route('admin.releases.contract.store'), $base + $rates)->assertSessionHasErrors('aggregator_rate');
            $this->assertSame(0, Contract::count());
            $this->assertEquals(85, Profile::find(1)->aggregator_rate);
        }
    }

    public function test_failed_generation_does_not_change_profile_split(): void
    {
        $this->generate();
        $this->post(route('admin.releases.contract.store'), [
            'profileid'=>1, 'type'=>'aggregator', 'number'=>1, 'month'=>10, 'year'=>2026,
            'aggregator_rate'=>90, 'system_rate'=>10,
        ])->assertSessionHasErrors();
        $this->assertEquals(85, Profile::find(1)->aggregator_rate);
        $this->assertSame(85, Contract::first()->user_percentage);
        $this->assertSame(1, Contract::count());
    }

    public function test_prefix_setting_applies_to_new_contracts_and_keeps_existing_numbers(): void
    {
        $old = $this->generate();
        $oldHtml = $old->generated_html;
        $this->assertSame('001/PKS-DMSV/X/2026', $old->docnum);
        $this->put(route('admin.system-settings.update'), ['section'=>'contract_prefix','aggregator_contract_prefix'=>' pks-sf '])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.system-settings.index',['tab'=>'contracts']));
        $this->app->forgetInstance(SystemSettings::class);
        $this->assertSame('PKS-SF',app(SystemSettings::class)->get('aggregator_contract_prefix'));
        $new = $this->generate(2);
        $this->assertSame('002/PKS-SF/X/2026',$new->docnum);
        $this->assertStringContainsString('002/PKS-SF/X/2026',$new->generated_html);
        $this->assertSame($oldHtml,$old->fresh()->generated_html);
        $this->assertSame('001/PKS-DMSV/X/2026',$old->fresh()->docnum);
        $old->generated_html = null;
        $this->assertStringContainsString('001/PKS-DMSV/X/2026',ContractService::renderAggregatorHtml($old,false));
    }

    public function test_stamped_pdf_is_separate_from_generated_and_manual_contract(): void
    {
        $this->app->useStoragePath($this->directory.'/storage');
        \Illuminate\Support\Facades\Storage::fake('local');
        $contract = $this->generate();
        $snapshot = $contract->generated_html;
        $manual = \Illuminate\Http\UploadedFile::fake()->createWithContent('manual.pdf', "%PDF-1.4\nManual contract");
        app(ContractService::class)->upload($contract, ['file' => $manual]);
        $manualId = $contract->fresh()->file->id;
        $pdf = "%PDF-1.4\nDigitally stamped contract";
        $this->post(route('admin.releases.contract.stamp_upload', $contract->id), [
            'stamped_pdf' => \Illuminate\Http\UploadedFile::fake()->createWithContent('bermaterai.pdf', $pdf),
        ])->assertSessionHasNoErrors();
        $contract = $contract->fresh();
        $this->assertSame($manualId, $contract->file->id);
        $this->assertSame($snapshot, $contract->generated_html);
        $this->assertSame('digital_stamp', $contract->digitalStamp->type);
        $this->assertSame(1, $contract->digitalStamp->profileid);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($contract->digitalStamp->identifier);
        $this->get(route('admin.releases.contract.stamp_view', $contract->id))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertStreamedContent($pdf);
    }

    public function test_replacing_stamped_pdf_removes_previous_file_and_delete_cleans_up(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $contract = $this->generate();
        $upload = fn () => \Illuminate\Http\UploadedFile::fake()->createWithContent('stamp.pdf', "%PDF-1.4\nStamped");
        $service = app(ContractService::class);
        $previous = $service->uploadDigitalStamp($contract, $upload());
        $current = $service->uploadDigitalStamp($contract, $upload());
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($previous->identifier);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($current->identifier);
        $this->assertSame(1, \App\Models\File::where('type', 'digital_stamp')->count());
        $this->assertNull($contract->fresh()->file);
        $contract->fresh()->delete();
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($current->identifier);
        $this->assertSame(0, \App\Models\File::count());
    }

    public function test_stamp_upload_rejects_missing_invalid_and_oversized_files(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $contract = $this->generate();
        File::ensureDirectoryExists($this->directory);
        File::put($this->directory.'/invalid.pdf', 'Not a PDF');
        foreach ([null,
            new \Illuminate\Http\UploadedFile($this->directory.'/invalid.pdf', 'fake.pdf', 'application/pdf', null, true),
            \Illuminate\Http\UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ] as $upload) {
            $this->postJson(route('admin.releases.contract.stamp_upload', $contract->id), ['stamped_pdf' => $upload])
                ->assertUnprocessable()->assertJsonValidationErrors('stamped_pdf');
        }
        $this->assertNull($contract->fresh()->digitalStamp);
        $this->getJson(route('admin.releases.contract.stamp_view', $contract->id))->assertNotFound();
        $this->postJson(route('admin.releases.contract.stamp_upload', 999))->assertNotFound();
    }

    public function test_stamp_routes_require_contract_permissions(): void
    {
        $contract = $this->generate();
        $restricted = new class extends User {
            public function hasPermission(string $permission): bool { return !in_array($permission, ['upload-contracts', 'view-contracts']); }
        };
        $restricted->forceFill(['id'=>98, 'name'=>'Restricted Admin', 'email_verified_at'=>now()]);
        $restricted->setRelation('profile', null);
        $this->actingAs($restricted);
        $this->postJson(route('admin.releases.contract.stamp_upload', $contract->id))->assertForbidden();
        $this->getJson(route('admin.releases.contract.stamp_view', $contract->id))->assertForbidden();
        $this->assertNull($contract->fresh()->digitalStamp);
    }

    public function test_signature_layout_reserves_center_and_includes_selected_date_for_both_drafts(): void
    {
        foreach ([false, true] as $index => $company) {
            DB::table('user_profiles')->where('id', 1)->update(['is_company'=>$company, 'companyname'=>'PT Contoh']);
            $contract = app(ContractService::class)->store([
                'profileid'=>1, 'type'=>'aggregator', 'number'=>$index+1, 'month'=>10, 'year'=>2026, 'contract_date'=>'2026-09-15',
            ]);
            $dom = new \DOMDocument();
            $dom->loadHTML($contract->generated_html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $tables = $xpath->query('//table[contains(@class,"contract-signatures")]');
            $this->assertSame(1, $tables->length);
            foreach ($xpath->query('.//tr', $tables->item(0)) as $row) {
                $cells = $xpath->query('./td', $row);
                $this->assertSame(3, $cells->length);
                $this->assertSame('', trim($cells->item(1)->textContent));
                $this->assertSame('digital-stamp-space', $cells->item(1)->getAttribute('class'));
            }
            $location = $xpath->query('//p[@class="contract-signing-date"]');
            $this->assertSame(1, $location->length);
            $this->assertStringContainsString('Kota Bogor', $location->item(0)->textContent);
            $this->assertStringContainsString('15 September 2026', $location->item(0)->textContent);
            $this->assertSame('Kota Bogor, 15 September 2026', $location->item(0)->textContent);
            $this->assertSame(0, $location->item(0)->getElementsByTagName('br')->length);
            $this->assertSame('td', $location->item(0)->parentNode->nodeName);
            $this->assertSame(0, $xpath->query('preceding-sibling::td', $location->item(0)->parentNode)->length);
            $this->assertSame(1, $xpath->query('./strong', $location->item(0))->length);
            $this->assertSame($contract->generated_html, app(AggregatorContractTemplates::class)->formatSignatureLayout($contract->generated_html, 'Other date'));
            $this->assertStringStartsWith('%PDF-', ContractService::generateAggregatorPdf($contract, false));
        }
    }

    public function test_signature_layout_preserves_saved_draft_content_and_handles_older_snapshots(): void
    {
        $html = '<p>Isi kontrak tersimpan</p><table><tr><td>PIHAK PERTAMA</td><td>PIHAK KEDUA</td></tr><tr><td>Perusahaan khusus</td><td>Nama user</td></tr><tr><td><div><img width="180" style="margin-left:30px" src="data:image/png;base64,AAA"></div></td><td><div>Signature</div></td></tr></table>';
        $formatted = app(AggregatorContractTemplates::class)->formatSignatureLayout($html, '30 Oktober 2026');
        foreach (['Isi kontrak tersimpan', 'Perusahaan khusus', 'Nama user', 'Kota Bogor', '30 Oktober 2026', 'data:image/png;base64,AAA'] as $text) {
            $this->assertStringContainsString($text, $formatted);
        }
        $this->assertStringNotContainsString('margin-left:30px', $formatted);
        $this->assertStringContainsString('width:200px', $formatted);
        $this->assertSame('<p>Custom tanpa tabel tanda tangan</p>', app(AggregatorContractTemplates::class)->formatSignatureLayout('<p>Custom tanpa tabel tanda tangan</p>'));
    }

    public function test_previous_centered_signature_date_is_moved_above_first_party_without_changing_date(): void
    {
        $html = '<p class="contract-signing-date">Kota Bogor<br>01 Oktober 2026</p><table class="contract-signatures"><tr><td>PIHAK PERTAMA</td><td class="digital-stamp-space"></td><td>PIHAK KEDUA</td></tr><tr><td><div><img width="150" src="data:image/png;base64,AAA"></div></td><td class="digital-stamp-space"></td><td>Signature</td></tr></table>';
        $formatted = app(AggregatorContractTemplates::class)->formatSignatureLayout($html, 'Different date');
        $dom = new \DOMDocument();
        $dom->loadHTML($formatted, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//table/tbody/tr/td/p[@class="contract-signing-date"] | //table/tr/td/p[@class="contract-signing-date"]')->length);
        $this->assertStringContainsString('01 Oktober 2026', $formatted);
        $this->assertStringNotContainsString('Different date', $formatted);
        $this->assertStringContainsString('width:200px', $formatted);
        $this->assertSame($formatted, app(AggregatorContractTemplates::class)->formatSignatureLayout($formatted));
    }

    public function test_existing_two_line_signing_date_becomes_one_line_without_changing_selected_date(): void
    {
        $html = '<table class="contract-signatures contract-signatures-v2"><tr><td><p class="contract-signing-date"><strong>Kota Bogor<br>30 Oktober 2026</strong></p></td><td></td><td></td></tr><tr><td>PIHAK PERTAMA</td><td></td><td>PIHAK KEDUA</td></tr></table>';
        $formatted = app(AggregatorContractTemplates::class)->formatSignatureLayout($html, 'Other date');
        $this->assertStringContainsString('<strong>Kota Bogor, 30 Oktober 2026</strong>', $formatted);
        $this->assertStringNotContainsString('<br', $formatted);
        $this->assertSame($formatted, app(AggregatorContractTemplates::class)->formatSignatureLayout($formatted));
    }

    public function test_embedded_canvas_logo_is_resolved_to_a_readable_file(): void
    {
        File::ensureDirectoryExists($this->directory);
        $image = imagecreatetruecolor(20, 10);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 50, 20, 100, 64));
        ob_start();
        imagepng($image);
        $uri = 'data:image/png;base64,'.base64_encode(ob_get_clean());
        imagedestroy($image);
        $options = new \Dompdf\Options;
        $options->setTempDir($this->directory);
        $dompdf = new \Dompdf\Dompdf($options);
        $canvas = \Mockery::mock();
        $canvas->shouldReceive('get_dompdf')->once()->andReturn($dompdf);
        $canvas->shouldReceive('image')->once()->withArgs(function ($path,$x,$y,$width,$height) use ($uri) {
            $this->assertFileIsReadable($path);
            $this->assertSame(realpath($this->directory),realpath(dirname($path)));
            $this->assertSame(base64_decode(substr($uri,strpos($uri,',')+1)),file_get_contents($path));
            return $x === 1.0 && $y === 2.0 && $width === 90.0 && $height === 40.0;
        });
        try {
            app(\App\Services\ContractPdfLayout::class)->drawImage($canvas,$uri,1,2,90,40);
        } finally {
            \Dompdf\Image\Cache::clear();
        }
        $this->assertSame([],glob($this->directory.'/*'));
    }

    public function test_pdf_renders_saved_transparent_logo_and_cleans_temporary_images(): void
    {
        File::ensureDirectoryExists($this->directory);
        config(['system-settings.branding_path'=>$this->directory]);
        $image = imagecreatetruecolor(20, 10);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image,0,0,imagecolorallocatealpha($image,50,20,100,64));
        imagepng($image,$this->directory.'/logo-abcd.png');
        imagedestroy($image);
        app(SystemSettings::class)->save(['logo'=>'branding/logo-abcd.png']);
        $contract = $this->generate();
        $this->assertStringContainsString('data:image/png;base64,',$contract->generated_html);
        app(SystemSettings::class)->save(['logo'=>null]);
        $this->assertStringStartsWith('%PDF-',ContractService::generateAggregatorPdf($contract->fresh(),false));
        $this->assertSame([],glob(storage_path('app/private/contract-pdf-temp/ca*')));
    }

    public function test_footer_identity_is_saved_encrypted_and_frozen_with_the_contract(): void
    {
        $settings = app(SystemSettings::class);
        $settings->save([
            'app_name'=>'Sistem Kontrak', 'company_name'=>'PT Musik & Karya',
            'company_address'=>'Jalan Musik 42, Bogor', 'company_email'=>'kontrak@example.test',
            'company_phone'=>'081234567890',
        ]);
        $contract = $this->generate();
        $layout = app(\App\Services\ContractPdfLayout::class);
        $snapshot = $contract->generated_html;
        $data = $layout->pageData($snapshot);
        $this->assertSame('SISTEM KONTRAK',$data['system']);
        $this->assertSame('PT Musik & Karya',$data['company']);
        $this->assertSame($contract->docnum,$data['number']);
        $this->assertStringContainsString('kontrak@example.test',implode(' ',array_column($data['rows'],'text')));
        $this->assertStringNotContainsString('kontrak@example.test',DB::table('contracts')->value('generated_html'));

        $settings->save(['app_name'=>'Nama Baru','company_name'=>'PT Baru','company_email'=>'baru@example.test']);
        $this->assertSame($snapshot,$contract->fresh()->generated_html);
        $this->assertSame($snapshot,$layout->decorate($snapshot));
        $this->assertSame($data,$layout->pageData($contract->fresh()->generated_html));
        $this->assertStringStartsWith('%PDF-',ContractService::generateAggregatorPdf($contract->fresh(),false));
    }

    public function test_long_contact_details_wrap_without_losing_content_or_changing_clauses(): void
    {
        $address = str_repeat('Alamat perusahaan panjang di Bogor ',12);
        app(SystemSettings::class)->save(['company_address'=>$address]);
        $layout = app(\App\Services\ContractPdfLayout::class);
        $html = $layout->decorate('<html><body><p>Isi pasal &amp; ketentuan asli.</p></body></html>');
        $data = $layout->pageData($html);
        $this->assertGreaterThan(3,count($data['rows']));
        $this->assertGreaterThan(42,$data['height']);
        $this->assertStringContainsString(trim($address),implode(' ',array_column($data['rows'],'text')));
        $this->assertStringContainsString('Isi pasal &amp; ketentuan asli.',$html);
        $this->assertSame($html,$layout->decorate($html));
    }

    public function test_invalid_prefix_does_not_change_saved_prefix(): void
    {
        foreach (['', 'BAD/CODE', '<script>', str_repeat('A',51)] as $prefix) {
            $this->put(route('admin.system-settings.update'), ['section'=>'contract_prefix','aggregator_contract_prefix'=>$prefix])->assertSessionHasErrors('aggregator_contract_prefix');
            $this->assertSame('PKS-DMSV',app(SystemSettings::class)->get('aggregator_contract_prefix'));
        }
    }
}
