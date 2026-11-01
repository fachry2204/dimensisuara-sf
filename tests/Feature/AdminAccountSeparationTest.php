<?php

namespace Tests\Feature;

use App\Models\User\Admin;
use App\Models\User\User;
use App\Models\ReleaseHistory;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAccountSeparationTest extends TestCase
{
    private $migration;
    private string $originalHash;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'account_testing','database.connections.account_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]]);
        Schema::create('system_settings', function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        $this->app->forgetInstance(SystemSettings::class);
        Schema::create('users', function ($t) { $t->id(); $t->string('name'); $t->string('email')->unique(); $t->timestamp('email_verified_at')->nullable(); $t->string('password'); $t->bigInteger('avatarid')->nullable(); $t->rememberToken(); $t->timestamps(); });
        Schema::create('user_profiles', function ($t) { $t->id(); $t->foreignId('userid')->constrained('users'); $t->string('status')->default('verified'); });
        Schema::create('access_roles', function ($t) { $t->id(); $t->string('name'); });
        Schema::create('access_permissions', function ($t) { $t->id(); $t->string('slug'); });
        Schema::create('access_role_permissions', function ($t) { $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('permission_id'); });
        Schema::create('access_user_roles', function ($t) { $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $t->foreignId('role_id')->constrained('access_roles'); });
        foreach (['announcements'=>'user_id','broadcasts'=>'created_by'] as $table=>$column) Schema::create($table, function ($t) use ($column) { $t->id(); $t->foreignId($column)->nullable()->constrained('users'); });
        Schema::create('report_imports', function ($t) { $t->id(); $t->unsignedBigInteger('userid'); });
        foreach (['logs'=>'userid','release_histories'=>'user_id'] as $table=>$column) Schema::create($table, function ($t) use ($column) { $t->id(); $t->foreignId($column)->nullable()->constrained('users')->nullOnDelete(); $t->timestamps(); });
        Schema::create('notifications', function ($t) { $t->id(); $t->string('notifiable_type'); $t->unsignedBigInteger('notifiable_id'); });
        Schema::create('password_reset_tokens', function ($t) { $t->string('email')->primary(); $t->string('token'); $t->timestamp('created_at')->nullable(); });
        $this->originalHash = Hash::make('correct-password');
        DB::table('users')->insert([
            ['id'=>20,'name'=>'Administrator','email'=>'admin@example.test','password'=>$this->originalHash,'email_verified_at'=>now(),'remember_token'=>'original-admin-token'],
            ['id'=>21,'name'=>'Customer','email'=>'customer@example.test','password'=>Hash::make('customer-password'),'email_verified_at'=>now(),'remember_token'=>null],
        ]);
        DB::table('user_profiles')->insert(['userid'=>21]);
        DB::table('access_roles')->insert(['id'=>1,'name'=>'Administrator']);
        DB::table('access_permissions')->insert(['id'=>1,'slug'=>'manage-admins']);
        DB::table('access_role_permissions')->insert(['role_id'=>1,'permission_id'=>1]);
        DB::table('access_permissions')->insert(['id'=>2,'slug'=>'view-roles']);
        DB::table('access_role_permissions')->insert(['role_id'=>1,'permission_id'=>2]);
        DB::table('access_user_roles')->insert(['user_id'=>20,'role_id'=>1]);
        foreach (['announcements'=>'user_id','broadcasts'=>'created_by','report_imports'=>'userid','logs'=>'userid','release_histories'=>'user_id'] as $table=>$column) DB::table($table)->insert([$column=>20]);
        DB::table('release_histories')->insert(['user_id'=>21]);
        DB::table('notifications')->insert(['notifiable_type'=>User::class,'notifiable_id'=>20]);
        $this->migration = require base_path('database/migrations/2026_10_30_170000_separate_admin_accounts.php');
        $this->migration->up();
        Route::middleware(['web','auth','can:admin'])->get('/admin-account-probe', fn () => ['id'=>auth()->id(),'model'=>get_class(auth()->user())]);
    }

    protected function tearDown(): void { DB::purge('account_testing'); parent::tearDown(); }

    public function test_migration_preserves_credentials_roles_authors_and_customer_data(): void
    {
        $this->assertNull(User::find(20));
        $admin = Admin::findOrFail(20);
        $this->assertSame($this->originalHash, $admin->getRawOriginal('password'));
        $this->assertSame('original-admin-token', $admin->remember_token);
        $this->assertTrue($admin->hasPermission('manage-admins'));
        $this->assertSame(21, User::first()->id);
        $this->assertSame(21, (int) DB::table('user_profiles')->value('userid'));
        $this->assertSame(Admin::class, DB::table('notifications')->value('notifiable_type'));
        $this->assertNull(DB::table('logs')->value('userid'));
        $this->assertSame(20, (int) DB::table('logs')->value('admin_user_id'));
        $this->assertSame('Administrator', \App\Models\Announcement::first()->creator->name);
        $this->assertCount(1, User::getByPermission('manage-admins'));
    }

    public function test_admin_uses_separate_session_and_can_reauthenticate_on_following_request(): void
    {
        $this->post('/login', ['email'=>'admin@example.test','password'=>'correct-password','remember'=>true])->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs(Admin::find(20), 'admin');
        $this->assertGuest('web');
        Auth::shouldUse('web');
        Auth::forgetGuards();
        $this->get('/admin-account-probe')->assertOk()->assertJson(['id'=>20,'model'=>Admin::class]);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest('admin');
        $this->get('/admin-account-probe')->assertRedirect(route('login'));
    }

    public function test_customer_login_does_not_gain_roles_when_numeric_ids_overlap(): void
    {
        DB::table('users')->insert(['id'=>20,'name'=>'Another Customer','email'=>'other@example.test','password'=>Hash::make('customer-password'),'email_verified_at'=>now()]);
        DB::table('user_profiles')->insert(['userid'=>20]);
        $customer = User::find(20);
        $this->assertFalse($customer->hasPermission('manage-admins'));
        $this->assertFalse($customer->is_admin);
        $this->assertNull(Admin::find(20)->profile);
        $this->post('/login',['email'=>'other@example.test','password'=>'customer-password'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($customer,'web');
        $this->assertGuest('admin');
        $this->getJson('/admin-account-probe')->assertForbidden();
    }

    public function test_admin_login_ignores_customer_or_external_intended_urls(): void
    {
        foreach ([route('home'),route('profile'),route('releases.index'),'https://outside.example.test/'] as $intended) {
            $this->withSession(['url.intended'=>$intended])
                ->post('/login',['email'=>'admin@example.test','password'=>'correct-password'])
                ->assertRedirect(route('admin.home'))->assertSessionMissing('url.intended');
            $this->assertAuthenticated('admin');
            Auth::guard('admin')->logout();
        }
    }

    public function test_authenticated_admin_login_and_customer_home_links_go_to_admin_home(): void
    {
        $this->actingAs(Admin::find(20),'admin');
        $this->get('/login')->assertRedirect(route('admin.home'));
        $this->get('/register')->assertRedirect(route('admin.home'));
        $this->get(route('home'))->assertRedirect(route('admin.home'));
    }

    public function test_customer_login_preserves_customer_destination_and_existing_session_redirect(): void
    {
        $this->withSession(['url.intended'=>route('releases.index')])
            ->post('/login',['email'=>'customer@example.test','password'=>'customer-password'])
            ->assertRedirect(route('releases.index'));
        $this->get('/login')->assertRedirect(route('home'));
    }

    public function test_admin_can_still_open_customer_home_when_impersonating_with_permission(): void
    {
        DB::table('access_permissions')->insert(['id'=>3,'slug'=>'impersonate-users']);
        DB::table('access_role_permissions')->insert(['role_id'=>1,'permission_id'=>3]);
        $controller = \Mockery::mock(\App\Http\Controllers\HomeController::class)->makePartial();
        $controller->shouldReceive('index')->once()->andReturn(response('Customer overview'));
        $this->app->instance(\App\Http\Controllers\HomeController::class,$controller);
        $this->actingAs(Admin::find(20),'admin');
        $this->withSession(['impersonate'=>21])->get(route('home'))->assertOk()->assertSee('Customer overview');
    }

    public function test_wrong_password_and_revoked_admin_cannot_sign_in(): void
    {
        $this->post('/login',['email'=>'admin@example.test','password'=>'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        DB::table('access_user_roles')->delete();
        $this->post('/login',['email'=>'admin@example.test','password'=>'correct-password'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_provisioned_admins_are_verified_without_email_notifications(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
        $this->actingAs(Admin::find(20),'admin');
        $this->post(route('admin.user-admin.store'),['type'=>'new','name'=>'Provisioned Admin','email'=>'provisioned@example.test',
            'password'=>'admin-password','password_confirmation'=>'admin-password','role'=>1])->assertSessionHasNoErrors();
        $this->assertNotNull(Admin::where('email','provisioned@example.test')->firstOrFail()->email_verified_at);
        $existing = Admin::create(['name'=>'Existing Admin','email'=>'existing@example.test','password'=>'admin-password']);
        $this->post(route('admin.user-admin.store'),['type'=>'existing','userid'=>$existing->id,'role'=>1])->assertSessionHasNoErrors();
        $this->assertNotNull($existing->fresh()->email_verified_at);
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_existing_admin_bypasses_email_verification_after_login_and_on_verification_routes(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        DB::table('user_admin')->where('id',20)->update(['email_verified_at'=>null]);
        $this->post('/login',['email'=>'admin@example.test','password'=>'correct-password'])->assertRedirect(route('admin.home'));
        $this->assertNotNull(Admin::find(20)->email_verified_at);
        foreach (['show','resend'] as $action) {
            DB::table('user_admin')->where('id',20)->update(['email_verified_at'=>null]);
            $this->actingAs(Admin::find(20),'admin');
            ($action === 'show' ? $this->get(route('verification.notice')) : $this->post(route('verification.resend')))
                ->assertRedirect(route('admin.home'));
            $this->assertNotNull(Admin::find(20)->email_verified_at);
        }
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_customer_still_requires_email_verification_and_sees_informative_notice(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        DB::table('users')->where('id',21)->update(['email_verified_at'=>null]);
        $customer = User::find(21); $this->actingAs($customer,'web'); $this->withoutVite();
        $this->get(route('home'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('Verifikasi email Anda')
            ->assertSee('customer@example.test')->assertSee('Kirim ulang email verifikasi')
            ->assertSee('Spam atau Promosi')->assertSee(route('verification.resend'),false);
        $this->assertNull($customer->fresh()->email_verified_at);
        \Illuminate\Support\Facades\Notification::assertSentTo($customer,\Illuminate\Auth\Notifications\VerifyEmail::class);
        $this->from(route('verification.notice'))->post(route('verification.resend'))->assertSessionHas('resent',true);
        $this->withSession(['resent'=>true])->get(route('verification.notice'))->assertSee('Tautan verifikasi baru telah dikirim');
        $this->assertNull($customer->fresh()->email_verified_at);
    }

    public function test_password_reset_tokens_are_isolated_between_account_types(): void
    {
        $admin = Admin::find(20);
        $adminToken = Password::broker('admins')->createToken($admin);
        $customer = User::find(21);
        $customerToken = Password::broker('users')->createToken($customer);
        $this->assertTrue(Password::broker('admins')->tokenExists($admin,$adminToken));
        $this->assertFalse(Password::broker('users')->tokenExists($admin,$adminToken));
        $this->assertFalse(Password::broker('admins')->tokenExists($customer,$customerToken));
        $this->post('/password/reset',['email'=>$admin->email,'token'=>$adminToken,'password'=>'new-admin-password','password_confirmation'=>'new-admin-password'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-admin-password',Admin::find(20)->password));
        $this->assertAuthenticated('admin');
    }

    public function test_eager_loaded_history_resolves_admin_and_customer_independently(): void
    {
        DB::table('users')->insert(['id'=>20,'name'=>'Unrelated Customer','email'=>'overlap@example.test','password'=>$this->originalHash]);
        DB::table('release_histories')->insert(['user_id'=>20]);
        $history = ReleaseHistory::with('user')->orderBy('id')->get();
        $this->assertInstanceOf(Admin::class, $history[0]->user);
        $this->assertSame('Administrator', $history[0]->user->name);
        $this->assertSame('Customer', $history[1]->user->name);
        $this->assertSame('Unrelated Customer', $history[2]->user->name);
    }

    public function test_rollback_restores_admin_rows_and_references_without_losing_credentials(): void
    {
        $this->migration->down();
        $this->assertFalse(Schema::hasTable('user_admin'));
        $this->assertSame($this->originalHash, User::find(20)->getRawOriginal('password'));
        $this->assertSame(20, (int) DB::table('logs')->value('userid'));
        $this->assertSame(User::class, DB::table('notifications')->value('notifiable_type'));
    }

    public function test_staff_customer_profile_survives_as_an_independent_customer_account(): void
    {
        $this->migration->down();
        DB::table('user_profiles')->insert(['userid'=>20]);
        $this->migration->up();
        $this->assertNotNull(User::find(20)->profile);
        $this->assertFalse(User::find(20)->hasPermission('manage-admins'));
        $this->assertTrue(Admin::find(20)->hasPermission('manage-admins'));
        $this->assertNull(Admin::find(20)->profile);
        $this->assertSame($this->originalHash, User::find(20)->getRawOriginal('password'));
    }

    public function test_create_and_revoke_admin_never_change_customer_records(): void
    {
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
        $this->actingAs(Admin::find(20), 'admin');
        $this->post(route('admin.user-admin.store'), ['type'=>'new','name'=>'New Admin','email'=>'new-admin@example.test','password'=>'new-admin-password','password_confirmation'=>'new-admin-password','role'=>1])->assertSessionHasNoErrors();
        $admin = Admin::where('email','new-admin@example.test')->firstOrFail();
        $this->assertNull(User::where('email',$admin->email)->first());
        $this->assertTrue($admin->hasPermission('manage-admins'));
        $this->delete(route('admin.user-admin.revoke', $admin->id))->assertSessionHasNoErrors();
        $this->assertNotNull(Admin::find($admin->id));
        $this->assertFalse($admin->fresh()->hasPermission('manage-admins'));
        $this->assertSame('Customer', User::find(21)->name);
    }
    public function test_manual_admin_verification_is_isolated_and_idempotent(): void
    {
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
        \Illuminate\Support\Facades\Notification::fake();
        $target = Admin::create(['name'=>'Staff', 'email'=>'staff@example.test', 'password'=>'staff-password']);
        $target->roles()->attach(1);
        $this->assertSame(21, $target->id);
        DB::table('users')->where('id', $target->id)->update(['email_verified_at'=>null]);
        $this->actingAs(Admin::find(20), 'admin');
        $url = route('admin.user-admin.verify-email', $target->id);
        $this->put($url)->assertRedirect()->assertSessionHasNoErrors();
        $verifiedAt = $target->fresh()->email_verified_at;
        $this->assertNotNull($verifiedAt);
        $this->assertNull(User::find($target->id)->email_verified_at);
        $this->travel(10)->minutes();
        $this->put($url)->assertSessionHasNoErrors();
        $this->assertTrue($verifiedAt->equalTo($target->fresh()->email_verified_at));
        $this->put(route('admin.user-admin.verify-email', 99999))->assertNotFound();
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_manual_customer_verification_works_for_pending_profiles_without_changing_approval(): void
    {
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
        \Illuminate\Support\Facades\Notification::fake();
        foreach (['view-users', 'manage-user-status'] as $slug) {
            $id = DB::table('access_permissions')->insertGetId(['slug'=>$slug]);
            DB::table('access_role_permissions')->insert(['role_id'=>1, 'permission_id'=>$id]);
        }
        DB::table('users')->where('id',21)->update(['email_verified_at'=>null]);
        DB::table('user_profiles')->where('userid',21)->update(['status'=>'pending']);
        $this->actingAs(Admin::find(20), 'admin');
        $url = route('admin.users.verify_email', 21);
        $this->put($url)->assertRedirect()->assertSessionHasNoErrors();
        $verifiedAt = User::find(21)->email_verified_at;
        $this->assertNotNull($verifiedAt);
        $this->assertSame('pending', DB::table('user_profiles')->where('userid',21)->value('status'));
        $this->travel(10)->minutes();
        $this->put($url)->assertSessionHasNoErrors();
        $this->assertTrue($verifiedAt->equalTo(User::find(21)->email_verified_at));
        \Illuminate\Support\Facades\Notification::assertNothingSent();
    }

    public function test_manual_verification_requires_the_correct_management_permission(): void
    {
        DB::table('users')->where('id',21)->update(['email_verified_at'=>null]);
        DB::table('access_role_permissions')->where('permission_id',1)->delete();
        $viewUsers = DB::table('access_permissions')->insertGetId(['slug'=>'view-users']);
        DB::table('access_role_permissions')->insert(['role_id'=>1, 'permission_id'=>$viewUsers]);
        $this->actingAs(Admin::find(20), 'admin');
        $this->put(route('admin.user-admin.verify-email',20))->assertForbidden();
        $this->put(route('admin.users.verify_email',21))->assertForbidden();
        $this->assertNull(User::find(21)->email_verified_at);
    }

    public function test_email_status_component_shows_manual_action_only_for_authorized_unverified_accounts(): void
    {
        $account = User::find(21);
        $render = fn ($canManage) => \Illuminate\Support\Facades\Blade::render(
            '<x-account-email-status :account="$account" action="/verify-target" :can-manage="$canManage" />',
            ['account'=>$account, 'canManage'=>$canManage]
        );
        $this->assertStringContainsString('Sudah Verifikasi', $render(true));
        $this->assertStringNotContainsString('<form', $render(true));
        $account->email_verified_at = null;
        $this->assertStringContainsString('Belum Verifikasi', $render(false));
        $this->assertStringNotContainsString('<form', $render(false));
        $this->assertStringContainsString('action="/verify-target"', $render(true));
        $this->assertStringContainsString('value="PUT"', $render(true));
    }

    private function prepareWhitelabelProfiles(): void
    {
        (require base_path('database/migrations/2026_11_01_120000_add_whitelabel_to_user_profiles.php'))->up();
        Schema::table('user_profiles', fn ($t) => $t->timestamps());
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
    }

    public function test_admin_can_enable_and_disable_whitelabel_without_rewriting_existing_releases(): void
    {
        $this->prepareWhitelabelProfiles();
        $this->assertFalse(User::find(21)->profile->whitelabel_enabled);
        foreach (['view-users', 'manage-user-features'] as $slug) {
            $id = DB::table('access_permissions')->insertGetId(['slug'=>$slug]);
            DB::table('access_role_permissions')->insert(['role_id'=>1, 'permission_id'=>$id]);
        }
        Schema::create('releases', function ($t) { $t->id(); $t->string('recordlabel'); });
        DB::table('releases')->insert(['recordlabel'=>'Existing Label']);
        $this->actingAs(Admin::find(20), 'admin');
        $url = route('admin.users.whitelabel.update',21);
        foreach ([true, false] as $enabled) {
            $this->put($url, ['whitelabel_enabled'=>(int) $enabled])->assertRedirect()->assertSessionHasNoErrors();
            $profile = User::find(21)->profile;
            $this->assertSame($enabled, $profile->whitelabel_enabled);
            $this->assertSame($enabled ? 'My Label' : 'Dimensi Suara', $profile->recordLabelFor('My Label'));
            $this->assertSame('Existing Label', DB::table('releases')->value('recordlabel'));
        }
        $this->putJson($url, ['whitelabel_enabled'=>'invalid'])->assertUnprocessable()->assertJsonValidationErrors('whitelabel_enabled');
        $this->assertFalse(User::find(21)->profile->whitelabel_enabled);
    }

    public function test_whitelabel_setting_requires_feature_management_and_cannot_be_mass_assigned(): void
    {
        $this->prepareWhitelabelProfiles();
        $viewUsers = DB::table('access_permissions')->insertGetId(['slug'=>'view-users']);
        DB::table('access_role_permissions')->insert(['role_id'=>1, 'permission_id'=>$viewUsers]);
        $this->actingAs(Admin::find(20), 'admin');
        $url = route('admin.users.whitelabel.update',21);
        $this->putJson($url, ['whitelabel_enabled'=>1])->assertForbidden();
        $profile = User::find(21)->profile;
        $this->assertFalse($profile->isFillable('whitelabel_enabled'));
        $this->assertFalse($profile->fresh()->whitelabel_enabled);
        $this->actingAs(User::find(21), 'web');
        Auth::guard('admin')->logout();
        $this->putJson($url, ['whitelabel_enabled'=>1])->assertForbidden();
        $this->assertFalse($profile->fresh()->whitelabel_enabled);
    }

    public function test_customer_draft_creation_and_editing_enforce_whitelabel_even_with_forged_input(): void
    {
        $this->prepareWhitelabelProfiles();
        Schema::create('releases', function ($t) { $t->id(); $t->integer('profileid'); $t->string('status'); });
        $customer = User::find(21);
        DB::table('releases')->insert(['id'=>50, 'profileid'=>$customer->profile->id, 'status'=>'draft']);
        $result = new \App\Models\Release(['title'=>'Draft']);
        $result->id = 50;
        $result->setRelation('tracks', collect());
        $result->setRelation('ipl', null);
        $service = \Mockery::mock(\App\Services\Releases\ReleaseService::class);
        $controller = new \App\Http\Controllers\ReleasesController($service);
        foreach ([false, true] as $enabled) {
            DB::table('user_profiles')->where('userid',21)->update(['whitelabel_enabled'=>$enabled]);
            $this->actingAs($customer->fresh(), 'web');
            foreach ([[], ['recordlabel'=>'Custom Label', 'whitelabel_enabled'=>1]] as $input) {
                $expected = $enabled ? ($input['recordlabel'] ?? null) : 'Dimensi Suara';
                $service->shouldReceive('store')->twice()->withArgs(function ($release, $profileid, $status, $data) use ($expected, $customer) {
                    $this->assertSame($customer->profile->id, $profileid);
                    $this->assertSame($expected, $data['recordlabel']);
                    $this->assertArrayNotHasKey('whitelabel_enabled', $data);
                    return true;
                })->andReturn($result);
                $request = \Illuminate\Http\Request::create('/releases', 'POST', $input);
                $this->assertSame(200, $controller->store($request)->getStatusCode());
                $this->assertSame(200, $controller->update(50, $request)->getStatusCode());
            }
        }
    }

}
