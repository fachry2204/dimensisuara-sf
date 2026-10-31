<?php

namespace Tests\Feature;

use App\Models\User\User;
use App\Services\SystemSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/system-settings-test-'.bin2hex(random_bytes(8));
        config([
            'database.default' => 'settings_testing',
            'database.connections.settings_testing' => ['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false],
            'system-settings.path' => $this->directory.'/settings.enc',
            'system-settings.company_signatures_path' => $this->directory.'/signatures',
            'mail.default' => 'array',
            'mail.mailers.smtp.password' => null,
            'system-settings.whatsapp.token' => null,
        ]);
        \Illuminate\Support\Facades\Schema::create('system_settings', function ($table) { $table->string('key')->primary(); $table->text('value'); $table->timestamps(); });
        \Illuminate\Support\Facades\Schema::create('access_roles', function ($table) { $table->id(); $table->string('name'); });
        $this->app->forgetInstance(SystemSettings::class);
        $this->app->usePublicPath($this->directory.'/public');
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        \Illuminate\Support\Facades\DB::purge('settings_testing');
        parent::tearDown();
    }

    private function signIn(bool $allowed = true): void
    {
        $user = new class extends User {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool
            {
                return $permission === 'admin' || ($this->allowed && $permission === 'manage-automation');
            }
        };
        $user->allowed = $allowed;
        $user->forceFill(['id' => 123, 'name' => 'Administrator', 'email_verified_at' => now()]);
        $user->setRelation('profile', null);
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }

    public function test_saved_form_values_roundtrip_through_database_without_a_settings_file(): void
    {
        $values = [
            'company_name'=>'PT Uji Database',
            'aggregator_names'=>['Distributor Satu', 'Distributor Dua'],
            'maintenance_enabled'=>true,
            'maintenance_roles'=>[1, 2],
            'primary_gradient_angle'=>120,
            'aggregator_draft_personal'=>'<p>[FULLNAME] [USER_PERCENT]% / [SYSTEM_PERCENT]%</p>',
        ];
        app(SystemSettings::class)->save($values);
        $this->app->forgetInstance(SystemSettings::class);
        foreach ($values as $key => $expected) {
            $stored = \Illuminate\Support\Facades\DB::table('system_settings')->where('key', $key)->value('value');
            $this->assertNotNull($stored);
            $this->assertSame($expected, unserialize(\Illuminate\Support\Facades\Crypt::decryptString($stored), ['allowed_classes'=>false]));
            $this->assertSame($expected, app(SystemSettings::class)->get($key));
        }
        $this->assertFileDoesNotExist(config('system-settings.path'));
        $this->assertSame([], File::exists($this->directory) ? File::allFiles($this->directory) : []);
    }

    public function test_guests_and_admins_without_server_permission_cannot_change_settings(): void
    {
        $this->put(route('admin.system-settings.update'), ['section' => 'branding', 'app_name' => 'Unauthorized'])->assertRedirect(route('login'));
        $this->signIn(false);
        $this->get(route('admin.system-settings.index'))->assertForbidden();
        $this->put(route('admin.system-settings.update'), ['section' => 'branding', 'app_name' => 'Unauthorized'])->assertForbidden();
        $this->assertFileDoesNotExist(config('system-settings.path'));
    }

    public function test_branding_uploads_persist_and_are_used_by_the_login_page(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), [
            'section' => 'branding', 'app_name' => 'Dimensi Suara',
            'logo' => UploadedFile::fake()->image('logo.png', 160, 80),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ])->assertRedirect(route('admin.system-settings.index'))->assertSessionHasNoErrors();
        $settings = app(SystemSettings::class);
        $logo = $settings->get('logo');
        $favicon = $settings->get('favicon');
        $this->assertFileExists(public_path($logo));
        $this->assertFileExists(public_path($favicon));
        $this->app->forgetInstance(SystemSettings::class);
        $this->assertSame('Dimensi Suara', app(SystemSettings::class)->get('app_name'));
        auth()->logout();
        $this->get(route('login'))->assertOk()->assertSee('Dimensi Suara')->assertSee(asset($logo), false)->assertSee(asset($favicon), false);
        $this->signIn();
        $this->put(route('admin.system-settings.update'), ['section' => 'branding', 'app_name' => 'Dimensi Suara', 'remove_logo' => 1, 'remove_favicon' => 1])->assertSessionHasNoErrors();
        $this->assertNull(app(SystemSettings::class)->get('logo'));
        $this->assertFileDoesNotExist(public_path($logo));
        $this->assertFileDoesNotExist(public_path($favicon));
    }

    public function test_smtp_secrets_are_encrypted_preserved_and_never_rendered_or_flashed(): void
    {
        $this->signIn();
        $values = [
            'section' => 'smtp', 'smtp_enabled' => 1, 'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587, 'smtp_encryption' => 'tls', 'smtp_username' => 'sender@gmail.com',
            'smtp_password' => 'secret-app-password', 'smtp_from_address' => 'sender@gmail.com',
            'smtp_from_name' => 'Dimensi Suara',
        ];
        $this->put(route('admin.system-settings.update'), $values)->assertSessionHasNoErrors();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame('secret-app-password', config('mail.mailers.smtp.password'));
        $this->assertStringNotContainsString('secret-app-password', \Illuminate\Support\Facades\DB::table('system_settings')->where('key','smtp_password')->value('value'));
        $this->get(route('admin.system-settings.index'))->assertOk()->assertSee('Setting System')->assertDontSee('secret-app-password');
        $values['smtp_password'] = '';
        $values['smtp_encryption'] = 'ssl';
        $values['smtp_port'] = 465;
        $this->put(route('admin.system-settings.update'), $values)->assertSessionHasNoErrors();
        $this->assertSame('secret-app-password', config('mail.mailers.smtp.password'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $values['smtp_port'] = 99999;
        $values['smtp_password'] = 'do-not-flash';
        $this->put(route('admin.system-settings.update'), $values)->assertSessionHasErrors('smtp_port');
        $this->assertArrayNotHasKey('smtp_password', session()->getOldInput());
    }

    public function test_whatsapp_settings_are_applied_and_blank_tokens_keep_the_existing_secret(): void
    {
        $this->signIn();
        $values = ['section' => 'whatsapp', 'whatsapp_url' => 'https://api.fonnte.com/', 'whatsapp_number' => '6281234567890', 'whatsapp_token' => 'secret-token'];
        $this->put(route('admin.system-settings.update'), $values)->assertSessionHasNoErrors();
        $this->assertSame('https://api.fonnte.com', config('system-settings.whatsapp.url'));
        $this->assertSame('secret-token', config('system-settings.whatsapp.token'));
        $this->assertStringNotContainsString('secret-token', \Illuminate\Support\Facades\DB::table('system_settings')->where('key','whatsapp_token')->value('value'));
        $this->get(route('admin.system-settings.index'))->assertOk()->assertDontSee('secret-token');
        $values['whatsapp_token'] = '';
        $this->put(route('admin.system-settings.update'), $values)->assertSessionHasNoErrors();
        $this->assertSame('secret-token', config('system-settings.whatsapp.token'));
        $values['whatsapp_url'] = 'javascript:bad';
        $values['whatsapp_token'] = 'never-flash';
        $this->put(route('admin.system-settings.update'), $values)->assertSessionHasErrors('whatsapp_url');
        $this->assertArrayNotHasKey('whatsapp_token', session()->getOldInput());
    }

    public function test_legacy_gateway_token_is_not_sent_to_fonnte_and_new_token_needs_no_sender(): void
    {
        $this->signIn();
        app(SystemSettings::class)->save(['whatsapp_url'=>'https://legacy-gateway.example.test','whatsapp_token'=>'legacy-private-token']);
        $this->assertNull(config('system-settings.whatsapp.token'));
        $this->assertFalse(app(SystemSettings::class)->publicValues()['has_whatsapp_token']);
        $this->put(route('admin.system-settings.update'), ['section'=>'whatsapp','whatsapp_token'=>''])->assertSessionHasErrors('whatsapp_token');
        $this->put(route('admin.system-settings.update'), ['section'=>'whatsapp','whatsapp_token'=>'new-fonnte-token'])->assertSessionHasNoErrors();
        $this->assertSame('new-fonnte-token',config('system-settings.whatsapp.token'));
        $this->assertSame('https://api.fonnte.com',config('system-settings.whatsapp.url'));
    }

    public function test_invalid_logo_files_do_not_change_branding(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), [
            'section' => 'branding', 'app_name' => 'Changed', 'logo' => UploadedFile::fake()->create('logo.php', 1, 'text/plain'),
        ])->assertSessionHasErrors('logo');
        $this->assertFileDoesNotExist(config('system-settings.path'));
    }

    public function test_queue_workers_refresh_settings_saved_by_another_process(): void
    {
        $worker = app(SystemSettings::class);
        $worker->save(['app_name' => 'First Brand']);
        $writer = new SystemSettings();
        $writer->save(['app_name' => 'Second Brand']);
        $worker->refresh();
        $this->assertSame('Second Brand', config('app.name'));
    }

    public function test_saving_branding_preserves_connection_settings_and_escapes_the_name(): void
    {
        $this->signIn();
        app(SystemSettings::class)->save(['smtp_password' => 'keep-password', 'whatsapp_token' => 'keep-token']);
        $this->put(route('admin.system-settings.update'), ['section' => 'branding', 'app_name' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->assertSame('keep-password', app(SystemSettings::class)->get('smtp_password'));
        $this->assertSame('keep-token', app(SystemSettings::class)->get('whatsapp_token'));
        auth()->logout();
        $this->get(route('login'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_aggregator_names_can_be_added_removed_and_used_for_release_review(): void
    {
        $this->signIn();
        $settings = app(SystemSettings::class);
        $this->assertSame(['Believe', 'SoundOn', 'Lokamusik'], $settings->aggregatorNames());
        $settings->save(['app_name' => 'Dimensi Suara', 'smtp_password' => 'keep-secret']);
        $this->put(route('admin.system-settings.update'), [
            'section' => 'aggregators', 'aggregator_names' => " Believe \r\n\r\nNew Distributor\r\n",
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.system-settings.index'));
        $this->app->forgetInstance(SystemSettings::class);
        $settings = app(SystemSettings::class);
        $this->assertSame(['Believe', 'New Distributor'], $settings->aggregatorNames());
        $this->assertSame('Dimensi Suara', $settings->get('app_name'));
        $this->assertSame('keep-secret', $settings->get('smtp_password'));
        $rules = (new \App\Http\Requests\Admin\ReviewReleaseRequest())->rules();
        $this->assertTrue(\Illuminate\Support\Facades\Validator::make(['aggregator' => 'New Distributor'], $rules)->passes());
        $this->assertFalse(\Illuminate\Support\Facades\Validator::make(['aggregator' => 'SoundOn'], $rules)->passes());
        $options = view('admin.aggregator.partials.aggregator-options', ['selectedAggregator' => 'New Distributor', 'legacyAggregator' => null])->render();
        $this->assertStringContainsString('value="New Distributor" selected', $options);
        $this->assertStringNotContainsString('SoundOn', $options);
        $legacyOptions = view('admin.aggregator.partials.aggregator-options', ['selectedAggregator' => 'SoundOn', 'legacyAggregator' => 'SoundOn'])->render();
        $this->assertStringContainsString('value="SoundOn" selected', $legacyOptions);
        $this->assertStringContainsString('SoundOn (tidak aktif)', $legacyOptions);
        $this->get(route('admin.system-settings.index'))->assertOk()->assertSee('Nama Aggregator')->assertSee('New Distributor');
    }

    public function test_aggregator_names_reject_duplicates_and_overlong_names(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), [
            'section' => 'aggregators', 'aggregator_names' => "Believe\nbelieve",
        ])->assertSessionHasErrors('aggregator_names.0');
        $this->put(route('admin.system-settings.update'), [
            'section' => 'aggregators', 'aggregator_names' => str_repeat('x', 101),
        ])->assertSessionHasErrors('aggregator_names.0');
        $this->assertFileDoesNotExist(config('system-settings.path'));
    }

    public function test_all_aggregator_names_can_be_removed(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), [
            'section' => 'aggregators', 'aggregator_names' => '',
        ])->assertSessionHasNoErrors();
        $this->assertSame([], app(SystemSettings::class)->aggregatorNames());
        $options = view('admin.aggregator.partials.aggregator-options', ['selectedAggregator' => null, 'legacyAggregator' => null])->render();
        $this->assertStringContainsString('-- Unassigned --', $options);
        $this->assertStringNotContainsString('Believe', $options);
    }

    public function test_aggregator_settings_require_server_permission(): void
    {
        $this->signIn(false);
        $this->put(route('admin.system-settings.update'), [
            'section' => 'aggregators', 'aggregator_names' => 'New Distributor',
        ])->assertForbidden();
        $this->assertFileDoesNotExist(config('system-settings.path'));
    }

    public function test_primary_colors_are_saved_and_rendered_globally(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), [
            'section' => 'colors', 'primary_color_mode' => 'gradient', 'primary_color' => '#1742ac',
            'primary_gradient_color' => '#6818a5', 'primary_gradient_angle' => 135,
        ])->assertSessionHasNoErrors();
        $this->assertSame('#1742ac', app(SystemSettings::class)->get('primary_color'));
        $html = view('layouts.primary-colors')->render();
        $this->assertStringContainsString('linear-gradient(135deg, #1742ac, #6818a5)', $html);
        $this->assertStringContainsString('--sf-on-primary: #ffffff', $html);
        \Illuminate\Support\Facades\Auth::logout();
        $this->get(route('login'))->assertOk()->assertSee('linear-gradient(135deg, #1742ac, #6818a5)', false);
    }

    public function test_invalid_colors_cannot_inject_css_or_overwrite_saved_settings(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), [
            'section' => 'colors', 'primary_color_mode' => 'unknown', 'primary_color' => '#fff;}</style><script>',
            'primary_gradient_color' => 'red', 'primary_gradient_angle' => 361,
        ])->assertSessionHasErrors(['primary_color_mode','primary_color','primary_gradient_color','primary_gradient_angle']);
        $this->assertSame('#c2410c', app(SystemSettings::class)->get('primary_color'));
        $this->assertFileDoesNotExist(config('system-settings.path'));
    }

    public function test_color_settings_require_server_permission(): void
    {
        $this->signIn(false);
        $this->put(route('admin.system-settings.update'), [
            'section' => 'colors', 'primary_color_mode' => 'solid', 'primary_color' => '#1742ac',
            'primary_gradient_color' => '#6818a5', 'primary_gradient_angle' => 135,
        ])->assertForbidden();
    }

    private function companyValues(): array
    {
        return [
            'section' => 'company', 'company_name' => 'PT Contoh & Mitra', 'company_director' => 'Direktur Contoh',
            'company_position' => 'Direktur Operasional',
            'company_address' => 'Alamat Kantor Utama', 'company_contact_name' => 'Kontak Contoh',
            'company_contact_address' => 'Alamat Korespondensi', 'company_phone' => '+62 812-1234-5678',
            'company_email' => 'contact@example.test',
        ];
    }

    public function test_company_fields_and_private_signature_can_be_saved_and_replaced(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), $this->companyValues() + [
            'company_signature' => UploadedFile::fake()->image('signature.png', 240, 90),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.system-settings.index', ['tab' => 'company']));
        $identity = app(\App\Services\CompanyIdentity::class);
        $first = $identity->signaturePath();
        $this->assertFileExists($first);
        $this->assertStringStartsWith($this->directory.'/signatures', $first);
        $this->assertSame('PT Contoh & Mitra', app(SystemSettings::class)->get('company_name'));
        $this->app->forgetInstance(SystemSettings::class);
        $this->assertSame('Direktur Operasional', app(SystemSettings::class)->get('company_position'));
        $stored = \Illuminate\Support\Facades\DB::table('system_settings')->where('key', 'company_position')->value('value');
        $this->assertNotNull($stored);
        $this->assertStringNotContainsString('Direktur Operasional', $stored);
        $signatureResponse = $this->get(route('admin.system-settings.company-signature'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('private', $signatureResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $signatureResponse->headers->get('Cache-Control'));
        $this->put(route('admin.system-settings.update'), $this->companyValues())->assertSessionHasNoErrors();
        $this->assertSame($first, $identity->signaturePath());
        $this->put(route('admin.system-settings.update'), $this->companyValues() + [
            'company_signature' => UploadedFile::fake()->image('new-signature.jpg', 160, 80),
        ])->assertSessionHasNoErrors();
        $second = $identity->signaturePath();
        $this->assertNotSame($first, $second);
        $this->assertFileDoesNotExist($first);
        $this->assertFileExists($second);
        $this->put(route('admin.system-settings.update'), $this->companyValues() + ['remove_company_signature' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($identity->signaturePath());
        $this->assertFileDoesNotExist($second);
        $this->get(route('admin.system-settings.company-signature'))->assertNotFound();
    }

    public function test_company_validation_and_permissions_protect_identity_and_signature(): void
    {
        $this->signIn();
        $invalid = array_replace($this->companyValues(), ['company_position'=>'', 'company_email' => 'invalid-email', 'company_signature' => UploadedFile::fake()->create('signature.pdf', 10, 'application/pdf')]);
        $this->put(route('admin.system-settings.update'), $invalid)->assertSessionHasErrors(['company_position','company_email','company_signature']);
        $this->assertFileDoesNotExist(config('system-settings.path'));
        $this->signIn(false);
        $this->put(route('admin.system-settings.update'), $this->companyValues())->assertForbidden();
        $this->get(route('admin.system-settings.company-signature'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.system-settings.company-signature'))->assertRedirect(route('login'));
    }

    public function test_company_name_is_escaped_in_terms_page(): void
    {
        $this->signIn();
        $this->put(route('admin.system-settings.update'), $this->companyValues())->assertSessionHasNoErrors();
        auth()->logout();
        $this->get(route('register.tnc'))->assertOk()->assertSee('PT Contoh &amp; Mitra', false)->assertDontSee('PT Digital Masif Suara Visual', false);
    }
}
