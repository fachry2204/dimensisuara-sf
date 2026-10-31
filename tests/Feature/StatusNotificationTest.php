<?php

namespace Tests\Feature;

use App\Jobs\SendStatusNotification;
use App\Mail\StatusUpdateMail;
use App\Models\Release;
use App\Models\StatusNotificationDelivery;
use App\Models\User\Profile;
use App\Models\User\User;
use App\Services\StatusNotificationTemplates;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StatusNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'status_test','database.connections.status_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]]);
        foreach (['system_settings'=>'key value','users'=>'name email password email_verified_at','user_profiles'=>'userid phone status reason firstname lastname','releases'=>'profileid title status reason primaryartists upc releaseon releaseddate finalreleaseddate','notify_whatsapp'=>'gwid deviceid phone message added_on','access_roles'=>'name'] as $name=>$columns) {
            Schema::create($name,function ($table) use ($columns,$name) {
                if ($name !== 'system_settings') $table->id();
                foreach (explode(' ',$columns) as $column) { if ($column === 'key') $table->string('key')->primary(); else $table->text($column)->nullable(); }
                $table->timestamps();
            });
        }
        (require base_path('database/migrations/2026_10_31_120000_create_status_notification_deliveries.php'))->up();
        Schema::create('tracks',function ($table) { $table->id(); $table->string('isrc')->nullable(); });
        Schema::create('release_track',function ($table) {
            $table->unsignedBigInteger('release_id'); $table->unsignedBigInteger('track_id');
            $table->unsignedInteger('track_order'); $table->timestamps();
        });
        $this->app->forgetInstance(SystemSettings::class);
        app(SystemSettings::class)->save(['smtp_enabled'=>true,'app_name'=>'Test Music']);
        Bus::fake(); Mail::fake(); Http::preventStrayRequests(); $this->withoutVite();
    }

    protected function tearDown(): void { DB::purge('status_test'); parent::tearDown(); }

    private function profile(): Profile
    {
        $user = User::create(['name'=>'Pemilik Musik','email'=>'owner@example.test','password'=>'test-secret']);
        $profile = new Profile;
        $profile->forceFill(['userid'=>$user->id,'phone'=>'081234567890','status'=>'pending','firstname'=>'Pemilik','lastname'=>'Musik'])->save();
        return $profile;
    }

    private function release(Profile $profile): Release
    {
        return Release::create(['profileid'=>$profile->id,'title'=>'Lagu Uji','status'=>'pending','primaryartists'=>'[{"name":"Artis Uji"}]','releaseon'=>'2026-11-20','upc'=>'8991111222233']);
    }

    private function signIn(bool $allowed=true): void
    {
        $user = new class extends User {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission === 'admin' || ($this->allowed && $permission === 'manage-automation'); }
        };
        $user->allowed=$allowed;
        $user->forceFill(['id'=>99,'name'=>'Administrator','email_verified_at'=>now()]);
        $user->setRelation('profile',null); $user->setRelation('unreadNotifications',collect());
        $this->actingAs($user);
    }

    private function form(): array
    {
        return ['section'=>'notifications-email','event'=>'release.released','channel_enabled'=>1,'event_enabled'=>1,
            'subject'=>'{aplikasi} · {judul_rilis}','heading'=>'Musik Anda: {status}','body'=>'Halo {nama}, rilis {judul_rilis} sudah {status}.',
            'button'=>'Lihat Rilis','footer'=>'Terima kasih, {aplikasi}.','accent'=>'#6d28d9'];
    }

    public function test_profile_events_are_transactional_encrypted_and_not_repeated_on_unrelated_saves(): void
    {
        $profile=$this->profile();
        $this->assertSame(1,StatusNotificationDelivery::count());
        $raw=DB::table('status_notification_deliveries')->first();
        $this->assertStringNotContainsString('owner@example.test',$raw->payload);
        $this->assertSame('user.pending',$raw->event);
        $profile->firstname='Nama Baru'; $profile->save();
        $this->assertSame(1,StatusNotificationDelivery::count());
        DB::beginTransaction();
        $profile->status='verified'; $profile->save();
        $this->assertSame(2,StatusNotificationDelivery::count());
        DB::rollBack();
        $this->assertSame(1,StatusNotificationDelivery::count());
        $profile->refresh(); $profile->status='verified'; $profile->save();
        $row=StatusNotificationDelivery::latest('id')->first();
        $this->assertSame('user.verified',$row->event);
        $this->assertSame('Menunggu verifikasi',$row->payload['message']['context']['status_sebelumnya']);
        Bus::assertDispatched(SendStatusNotification::class);
    }

    public function test_release_statuses_capture_immutable_templates_once_per_actual_transition(): void
    {
        $release=$this->release($this->profile());
        foreach (['review','uploading','released','rejected','revision'] as $status) {
            $release->status=$status; $release->reason='Catatan uji'; $release->save();
            $release->title='Judul '.$status; $release->save();
        }
        $this->assertSame(6,StatusNotificationDelivery::whereNotNull('release_id')->count());
        $row=StatusNotificationDelivery::where('event','release.released')->first();
        $this->assertSame('20 November 2026',$row->payload['message']['context']['tanggal_rilis']);
        $this->assertSame('Artis Uji',$row->payload['message']['context']['artis']);
        $this->assertSame('8991111222233',$row->payload['message']['context']['upc']);
        $config=app(StatusNotificationTemplates::class)->configuration('email');
        $config['templates']['release.released']['body']='TEMPLATE BARU';
        app(SystemSettings::class)->save(['status_notifications_email'=>$config]);
        $this->assertStringNotContainsString('TEMPLATE BARU',$row->fresh()->payload['message']['body']);
        $legacy=new \App\Notifications\Customer\Release\Verified($release);
        $this->assertSame([\App\Notifications\Channels\DatabaseChannel::class],$legacy->via($release->profile->user));
    }

    public function test_email_and_whatsapp_deliver_independently_and_duplicate_jobs_do_not_resend(): void
    {
        $wa=app(StatusNotificationTemplates::class)->configuration('whatsapp'); $wa['enabled']=true;
        app(SystemSettings::class)->save(['status_notifications_whatsapp'=>$wa,'whatsapp_token'=>'fake-test-token','whatsapp_provider'=>'fonnte']);
        Http::fake(['https://api.fonnte.com/send'=>Http::response(['status'=>true,'id'=>['test']],200)]);
        $this->profile();
        $this->assertSame(2,StatusNotificationDelivery::count());
        foreach (StatusNotificationDelivery::all() as $row) {
            (new SendStatusNotification($row->id))->handle();
            (new SendStatusNotification($row->id))->handle();
            $this->assertSame('sent',$row->fresh()->status);
            $this->assertSame(1,$row->fresh()->attempts);
        }
        Mail::assertSent(StatusUpdateMail::class,1);
        Http::assertSentCount(1);
        $this->assertSame(1,DB::table('notify_whatsapp')->count());
    }

    public function test_disabled_statuses_channels_and_smtp_are_respected(): void
    {
        $config=app(StatusNotificationTemplates::class)->configuration('email'); $config['templates']['user.pending']['enabled']=false;
        app(SystemSettings::class)->save(['status_notifications_email'=>$config]);
        $profile=$this->profile();
        $this->assertSame(0,StatusNotificationDelivery::count());
        $profile->status='verified';$profile->save();
        $row=StatusNotificationDelivery::first();
        app(SystemSettings::class)->save(['smtp_enabled'=>false]);
        (new SendStatusNotification($row->id))->handle();
        $this->assertSame('skipped',$row->fresh()->status);
        Mail::assertNothingSent();
        $config['enabled']=false;app(SystemSettings::class)->save(['status_notifications_email'=>$config]);
        $profile->status='blocked';$profile->save();
        $this->assertSame(1,StatusNotificationDelivery::count());
    }

    public function test_failed_gateway_does_not_expose_tokens_and_does_not_automatically_retry(): void
    {
        $wa=app(StatusNotificationTemplates::class)->configuration('whatsapp');$wa['enabled']=true;
        app(SystemSettings::class)->save(['status_notifications_whatsapp'=>$wa,'whatsapp_token'=>'secret-test-token','whatsapp_provider'=>'fonnte']);
        Http::fake(['https://api.fonnte.com/send'=>Http::response(['status'=>false,'message'=>'secret-test-token'],500)]);
        $profile=$this->profile();
        $row=StatusNotificationDelivery::where('channel','whatsapp')->first();
        (new SendStatusNotification($row->id))->handle();
        (new SendStatusNotification($row->id))->handle();
        $this->assertSame('failed',$row->fresh()->status);
        $this->assertStringNotContainsString('secret-test-token',$row->fresh()->error);
        $this->assertSame('pending',$profile->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_settings_are_authorized_persisted_and_preview_escapes_html_without_sending(): void
    {
        $form=$this->form(); $route=route('admin.system-settings.notifications.save','email');
        $this->post($route,$form)->assertRedirect();
        $this->signIn(false);$this->post($route,$form)->assertForbidden();
        $this->signIn();$this->post($route,$form)->assertRedirect();
        $this->assertSame($form['body'],app(StatusNotificationTemplates::class)->configuration('email')['templates']['release.released']['body']);
        $this->post($route,array_replace($form,['body'=>'Halo {token_salah}']))->assertSessionHasErrors('body');
        $preview=route('admin.system-settings.notifications.preview','email');
        $this->post($preview,array_replace($form,['body'=>'Halo {nama} <script>alert(1)</script>']))->assertOk()->assertSee('Kreator Contoh')->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        $this->get(route('admin.system-settings.index',['tab'=>'notifications-email']))->assertOk()->assertSee('Notifikasi Email')->assertSee('Notifikasi WhatsApp')->assertSee('Pratinjau Perubahan')->assertSee('value="release.released" selected',false);
        Mail::assertNothingSent();Http::assertNothingSent();
    }

    public function test_isrc_placeholder_can_be_saved_and_previewed_for_both_channels(): void
    {
        $this->signIn();
        app(SystemSettings::class)->save(['whatsapp_token'=>'fake-test-token','whatsapp_provider'=>'fonnte']);
        foreach (['email','whatsapp'] as $channel) {
            $form = array_replace($this->form(),['section'=>'notifications-'.$channel,'body'=>'UPC: {upc}\nISRC: {isrc}']);
            $this->post(route('admin.system-settings.notifications.save',$channel),$form)->assertSessionHasNoErrors()->assertRedirect();
            $this->assertSame($form['body'],app(StatusNotificationTemplates::class)->configuration($channel)['templates']['release.released']['body']);
            $this->post(route('admin.system-settings.notifications.preview',$channel),$form)->assertOk()->assertSee('IDA1B2600001')->assertDontSee('{isrc}');
        }
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_release_notifications_capture_all_track_isrc_codes_in_order_and_keep_the_snapshot(): void
    {
        foreach (['email','whatsapp'] as $channel) {
            $config = app(StatusNotificationTemplates::class)->configuration($channel);
            $config['enabled'] = true;
            $config['templates']['release.released']['body'] = 'ISRC: {isrc}';
            app(SystemSettings::class)->save(['status_notifications_'.$channel=>$config]);
        }
        $release = $this->release($this->profile());
        $pending = StatusNotificationDelivery::where('event','release.pending')->first();
        $this->assertSame('Belum tersedia',$pending->payload['message']['context']['isrc']);
        foreach ([2=>'IDA1B2600002',1=>'IDA1B2600001',3=>'IDA1B2600001',4=>' ',5=>null] as $order=>$isrc) {
            $id = DB::table('tracks')->insertGetId(['isrc'=>$isrc]);
            DB::table('release_track')->insert(['release_id'=>$release->id,'track_id'=>$id,'track_order'=>$order]);
        }
        $release->status = 'released'; $release->save();
        $rows = StatusNotificationDelivery::where('event','release.released')->get();
        $this->assertCount(2,$rows);
        DB::table('tracks')->update(['isrc'=>'CHANGED']);
        foreach ($rows as $row) {
            $message = $row->fresh()->payload['message'];
            $this->assertSame('IDA1B2600001, IDA1B2600002',$message['context']['isrc']);
            $this->assertSame('ISRC: IDA1B2600001, IDA1B2600002',$message['body']);
            $this->assertStringContainsString('ISRC: IDA1B2600001, IDA1B2600002',$message['whatsapp']);
        }
    }

    public function test_manual_retry_requires_provider_review_and_never_resends_sent_rows(): void
    {
        $this->profile();$row=StatusNotificationDelivery::first();$row->update(['status'=>'failed']);
        $this->signIn();$url=route('admin.system-settings.notifications.retry',$row->id);
        $this->post($url)->assertSessionHasErrors('provider_checked');
        $this->post($url,['provider_checked'=>1])->assertRedirect();
        $this->assertSame('pending',$row->fresh()->status);
        $row->update(['status'=>'sent']);
        $this->postJson($url,['provider_checked'=>1])->assertStatus(409);
    }

    public function test_email_verification_is_a_distinct_event_and_missing_phone_is_skipped(): void
    {
        $wa=app(StatusNotificationTemplates::class)->configuration('whatsapp');$wa['enabled']=true;
        app(SystemSettings::class)->save(['status_notifications_whatsapp'=>$wa,'whatsapp_token'=>'fake-token','whatsapp_provider'=>'fonnte']);
        $profile=$this->profile();
        $profile->phone=null;$profile->save();
        $profile->user->email_verified_at=now();$profile->user->save();
        $this->assertSame('pending',$profile->fresh()->status);
        $this->assertSame(2,StatusNotificationDelivery::where('event','user.email_verified')->count());
        $row=StatusNotificationDelivery::where('event','user.email_verified')->where('channel','whatsapp')->first();
        (new SendStatusNotification($row->id))->handle();
        $this->assertSame('skipped',$row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_preview_is_available_without_delivery_migration_and_blocked_for_non_admins(): void
    {
        Schema::drop('status_notification_deliveries');
        $preview=route('admin.system-settings.notifications.preview',['channel'=>'email','event'=>'release.released']);
        $this->get($preview)->assertRedirect();
        $this->signIn(false);$this->getJson($preview)->assertForbidden();
        $this->signIn();$this->get($preview)->assertOk()->assertSee('Cerita di Dalam Musik');
        $this->get(route('admin.system-settings.index',['tab'=>'notifications-whatsapp']))->assertOk()->assertSee('Template tetap dapat disiapkan');
    }
}
