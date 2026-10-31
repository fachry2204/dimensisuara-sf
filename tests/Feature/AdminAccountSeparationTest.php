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

    public function test_wrong_password_and_revoked_admin_cannot_sign_in(): void
    {
        $this->post('/login',['email'=>'admin@example.test','password'=>'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        DB::table('access_user_roles')->delete();
        $this->post('/login',['email'=>'admin@example.test','password'=>'correct-password'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->assertGuest('web');
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
}
