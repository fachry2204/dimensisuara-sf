<?php

namespace Tests\Feature;

use App\Models\User\User;
use App\Services\NotificationHealth;
use App\Services\SystemSettings;
use Carbon\Carbon;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GatewayHealthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'gateway_testing', 'database.connections.gateway_testing'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'', 'foreign_key_constraints'=>false,
        ], 'mail.mailers.smtp.transport'=>'array', 'mail.mailers.smtp.password'=>null, 'system-settings.whatsapp.token'=>null]);
        Schema::create('system_settings', function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('access_roles', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('notify_whatsapp', function ($t) { $t->id(); foreach (['gwid','deviceid','phone','message','added_on'] as $field) $t->text($field)->nullable(); });
        Schema::create('status_notification_deliveries', function ($t) { $t->id(); $t->string('channel'); $t->string('status'); $t->timestamp('sent_at')->nullable(); });
        $this->app->forgetInstance(SystemSettings::class);
        Carbon::setTestNow('2026-11-06 12:00:00');
        Http::preventStrayRequests();
        $this->withoutVite();
        $this->signIn();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('gateway_testing');
        parent::tearDown();
    }

    private function signIn(bool $allowed = true): void
    {
        $user = new class extends User {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission === 'admin' || ($this->allowed && $permission === 'manage-automation'); }
        };
        $user->allowed = $allowed;
        $user->forceFill(['id'=>1, 'name'=>'Admin', 'email_verified_at'=>now()]);
        $user->setRelation('profile', null)->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }

    public function test_email_test_uses_saved_smtp_and_reports_provider_acceptance(): void
    {
        app(SystemSettings::class)->save(['smtp_enabled'=>true, 'smtp_host'=>'smtp.example.test', 'smtp_from_address'=>'sender@example.test']);
        $messages = [];
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Mail\Events\MessageSent::class, function ($event) use (&$messages) { $messages[] = $event->message; });
        $this->post(route('admin.system-settings.gateway.test', 'email'), ['recipient'=>'test@example.test'])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertCount(1, $messages);
        $this->assertSame('test@example.test', $messages[0]->getTo()[0]->getAddress());
        $this->assertSame('accepted', (new SystemSettings)->get('gateway_test_email_status'));
    }

    public function test_tests_require_valid_recipient_and_enabled_gateway(): void
    {
        $url = route('admin.system-settings.gateway.test', 'email');
        $this->post($url, ['recipient'=>'invalid'])->assertSessionHasErrors('recipient');
        $this->post($url, ['recipient'=>'test@example.test'])->assertSessionHasErrors('recipient');
        $this->post(route('admin.system-settings.gateway.test', 'whatsapp'), ['recipient'=>'6281234567890'])->assertSessionHasErrors('recipient');
        $this->assertNull((new SystemSettings)->get('gateway_test_email_status'));
    }

    public function test_whatsapp_test_normalizes_number_and_records_acceptance(): void
    {
        app(SystemSettings::class)->save(['whatsapp_token'=>'test-device-token', 'whatsapp_provider'=>'fonnte']);
        Http::fake(['https://api.fonnte.com/send'=>Http::response(['status'=>true, 'id'=>['test-id']])]);
        $this->post(route('admin.system-settings.gateway.test', 'whatsapp'), ['recipient'=>'081234567890'])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        Http::assertSent(fn ($r) => $r['target'] === '6281234567890' && $r->hasHeader('Authorization', 'test-device-token'));
        $this->assertSame('accepted', (new SystemSettings)->get('gateway_test_whatsapp_status'));
    }

    public function test_provider_failure_is_visible_without_leaking_response_or_token(): void
    {
        app(SystemSettings::class)->save(['whatsapp_token'=>'secret-token', 'whatsapp_provider'=>'fonnte']);
        Http::fake(['https://api.fonnte.com/send'=>Http::response(['status'=>false, 'reason'=>'secret-token'], 401)]);
        $this->post(route('admin.system-settings.gateway.test', 'whatsapp'), ['recipient'=>'6281234567890'])->assertSessionHasErrors('gateway_test');
        $this->assertStringNotContainsString('secret-token', session('errors')->first('gateway_test'));
        $this->assertSame('failed', (new SystemSettings)->get('gateway_test_whatsapp_status'));
    }

    public function test_smtp_failure_does_not_expose_transport_credentials(): void
    {
        app(SystemSettings::class)->save(['smtp_enabled'=>true]);
        Mail::shouldReceive('purge')->zeroOrMoreTimes();
        Mail::shouldReceive('mailer')->with('smtp')->andThrow(new \RuntimeException('smtp-private-password'));
        $this->post(route('admin.system-settings.gateway.test', 'email'), ['recipient'=>'test@example.test'])->assertSessionHasErrors('gateway_test');
        $this->assertStringNotContainsString('smtp-private-password', session('errors')->first('gateway_test'));
        $this->assertSame('failed', (new SystemSettings)->get('gateway_test_email_status'));
    }

    public function test_only_server_administrators_can_send_tests_and_requests_are_limited(): void
    {
        $url = route('admin.system-settings.gateway.test', 'email');
        $this->signIn(false);
        $this->post($url, ['recipient'=>'test@example.test'])->assertForbidden();
        $this->signIn();
        for ($i=0; $i<3; $i++) $this->post($url, ['recipient'=>'invalid']);
        $this->post($url, ['recipient'=>'invalid'])->assertStatus(429);
    }

    public function test_health_distinguishes_unknown_fresh_and_stale_and_ignores_other_queues(): void
    {
        $health = app(NotificationHealth::class);
        $this->assertSame('unknown', $health->snapshot()['scheduler']['state']);
        $health->record('scheduler');
        event(new Looping('database', 'drive-archive'));
        $this->assertSame('unknown', $health->snapshot()['worker']['state']);
        config(['queue.connections.database.connection'=>null]);
        event(new Looping('database', 'default'));
        $this->assertSame('active', $health->snapshot()['scheduler']['state']);
        $this->assertSame('active', $health->snapshot()['worker']['state']);
        $this->travel(4)->minutes();
        $this->assertSame('stale', $health->snapshot()['scheduler']['state']);
        $this->assertSame('stale', $health->snapshot()['worker']['state']);
        $health->record('worker');
        $this->assertSame('active', $health->snapshot()['worker']['state']);
    }

    public function test_settings_show_tests_and_health_without_marking_a_page_visit_as_activity(): void
    {
        $this->get(route('admin.system-settings.index'))->assertOk()->assertSee('Belum terdeteksi')->assertSee('Kirim tes Email')->assertSee('Kirim tes WhatsApp')->assertSee('schedule:run');
        $this->assertSame('unknown', app(NotificationHealth::class)->snapshot()['scheduler']['state']);
    }

    public function test_heartbeat_supports_normalized_relational_settings_storage(): void
    {
        (require database_path('migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        $health = app(NotificationHealth::class);
        $health->record('scheduler');
        $this->assertFalse(Schema::hasColumn('system_settings', 'value'));
        $this->assertSame('active', $health->snapshot()['scheduler']['state']);
    }

    public function test_scheduler_registers_real_heartbeat_and_explicit_notification_worker(): void
    {
        $schedule = new \Illuminate\Console\Scheduling\Schedule;
        \Illuminate\Support\Facades\Schedule::swap($schedule);
        require base_path('routes/console.php');
        $events = collect($schedule->events());
        $heartbeat = $events->firstWhere('description', 'notification-scheduler-heartbeat');
        $this->assertNotNull($heartbeat);
        $this->assertSame('* * * * *', $heartbeat->expression);
        $heartbeat->run($this->app);
        $this->assertSame('active', app(NotificationHealth::class)->snapshot()['scheduler']['state']);
        $worker = $events->first(fn ($event) => str_contains($event->command ?? '', 'queue:work status-notifications'));
        $this->assertNotNull($worker);
        $this->assertSame('* * * * *', $worker->expression);
        $this->assertTrue($worker->withoutOverlapping);
        $this->assertTrue($worker->runInBackground);
        $driveWorker = $events->first(fn ($event) => str_contains($event->command ?? '', 'queue:work drive-archive'));
        $this->assertNotNull($driveWorker);
        $this->assertSame('* * * * *', $driveWorker->expression);
        $this->assertTrue($driveWorker->withoutOverlapping);
        $this->assertTrue($driveWorker->runInBackground);
        app(SystemSettings::class)->save(['drive_enabled'=>false]);
        $this->assertFalse($driveWorker->filtersPass($this->app));
        app(SystemSettings::class)->save(['drive_enabled'=>true]);
        $this->assertTrue($driveWorker->filtersPass($this->app));
        $this->assertSame('unknown', app(NotificationHealth::class)->snapshot()['worker']['state']);
    }

    public function test_drive_test_identifies_missing_config_without_exposing_credentials(): void
    {
        app(SystemSettings::class)->save(['drive_binary'=>'rclone', 'drive_config'=>sys_get_temp_dir().'/'.\Illuminate\Support\Str::uuid().'/rclone.conf']);
        $this->post(route('admin.system-settings.drive.test'))->assertSessionHasErrors('drive');
        $this->assertStringContainsString('rclone.conf tidak ditemukan', session('errors')->first('drive'));
        $this->assertSame('failed', app(SystemSettings::class)->get('drive_connection_status'));
    }

    public function test_drive_test_identifies_missing_binary(): void
    {
        app(SystemSettings::class)->save(['drive_binary'=>sys_get_temp_dir().'/'.\Illuminate\Support\Str::uuid().'/rclone']);
        $this->post(route('admin.system-settings.drive.test'))->assertSessionHasErrors('drive');
        $this->assertStringContainsString('Lokasi rclone tidak ditemukan', session('errors')->first('drive'));
    }

    public function test_drive_provider_errors_stay_private(): void
    {
        $drive = \Mockery::mock(\App\Services\RcloneDrive::class);
        $drive->shouldReceive('testConnection')->andThrow(new \RuntimeException('private-refresh-token'));
        $this->app->instance(\App\Services\RcloneDrive::class, $drive);
        $this->post(route('admin.system-settings.drive.test'))->assertSessionHasErrors('drive');
        $this->assertStringNotContainsString('private-refresh-token', session('errors')->first('drive'));
    }
}
