<?php

namespace Tests\Feature;

use App\Models\User\User;
use App\Models\Access\Role;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'maintenance_testing','database.connections.maintenance_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>false]]);
        Schema::create('system_settings', function ($table) { $table->string('key')->primary(); $table->text('value'); $table->timestamps(); });
        Schema::create('access_roles', function ($table) { $table->id(); $table->string('name'); });
        DB::table('access_roles')->insert([['id'=>1,'name'=>'Operator'],['id'=>2,'name'=>'Reviewer']]);
        $this->app->forgetInstance(SystemSettings::class);
        $this->withoutVite();
        Route::middleware('web')->get('/maintenance-probe', fn () => 'Available')->name('maintenance.probe');
        Route::middleware('web')->post('/maintenance-probe', fn () => 'Saved');
    }

    protected function tearDown(): void { DB::purge('maintenance_testing'); parent::tearDown(); }

    private function signIn(array $roles = [], bool $manager = false): void
    {
        $user = new class extends User {
            public bool $manager = false;
            public function hasPermission(string $permission): bool { return $permission === 'admin' || ($this->manager && $permission === 'manage-automation'); }
        };
        $user->manager = $manager;
        $user->forceFill(['id'=>123,'name'=>'Test User','email_verified_at'=>now()]);
        $user->setRelation('roles', Role::whereIn('id',$roles)->get());
        $user->setRelation('profile', null);
        $user->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }

    public function test_only_selected_roles_are_blocked_and_reason_is_escaped(): void
    {
        app(SystemSettings::class)->save(['maintenance_enabled'=>true,'maintenance_roles'=>[1],'maintenance_reason'=>'Pembaruan <script>alert(1)</script>']);
        $this->signIn([1,2]);
        $this->get('/maintenance-probe')->assertStatus(503)->assertSee('Pembaruan &lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)->assertHeader('Retry-After','300');
        $this->getJson('/maintenance-probe')->assertStatus(503)->assertJson(['maintenance'=>true]);
        $this->post('/maintenance-probe')->assertStatus(503)->assertDontSee('Saved');
        $this->signIn([2]);
        $this->get('/maintenance-probe')->assertOk()->assertSee('Available');
    }

    public function test_customers_guests_and_login_recovery_are_handled(): void
    {
        app(SystemSettings::class)->save(['maintenance_enabled'=>true,'maintenance_customers'=>true]);
        $this->signIn();
        $this->get('/maintenance-probe')->assertStatus(503);
        auth()->logout();
        $this->get('/maintenance-probe')->assertOk();
        app(SystemSettings::class)->save(['maintenance_guests'=>true]);
        $this->get('/maintenance-probe')->assertStatus(503);
        $this->get(route('login'))->assertOk();
        $this->post(route('login'), [])->assertStatus(302)->assertSessionHasErrors('email');
        $this->get(route('password.request'))->assertOk();
    }

    public function test_server_manager_can_always_disable_maintenance(): void
    {
        app(SystemSettings::class)->save(['maintenance_enabled'=>true,'maintenance_roles'=>[1],'maintenance_customers'=>true,'maintenance_guests'=>true]);
        $this->signIn([1], true);
        $this->get('/maintenance-probe')->assertOk();
        $this->get(route('admin.system-settings.index',['tab'=>'maintenance']))->assertOk()->assertSee('Mode Maintenance');
        $this->put(route('admin.system-settings.update'), ['section'=>'maintenance','maintenance_reason'=>'Selesai'])->assertSessionHasNoErrors();
        $this->assertFalse(app(SystemSettings::class)->get('maintenance_enabled'));
        $this->assertSame([], app(SystemSettings::class)->get('maintenance_roles'));
        $this->get(route('admin.system-settings.maintenance-preview'))->assertOk()->assertSee('Selesai');
    }

    public function test_settings_persist_and_validate_roles_and_target_selection(): void
    {
        $this->signIn([],true);
        $this->put(route('admin.system-settings.update'), ['section'=>'maintenance','maintenance_enabled'=>1,'maintenance_reason'=>'Upgrade database','maintenance_roles'=>[1]])->assertSessionHasNoErrors();
        $this->app->forgetInstance(SystemSettings::class);
        $this->assertSame([1],app(SystemSettings::class)->get('maintenance_roles'));
        $this->assertSame('Upgrade database',app(SystemSettings::class)->get('maintenance_reason'));
        $this->put(route('admin.system-settings.update'), ['section'=>'maintenance','maintenance_enabled'=>1,'maintenance_reason'=>'No groups'])->assertSessionHasErrors('maintenance_roles');
        $this->put(route('admin.system-settings.update'), ['section'=>'maintenance','maintenance_enabled'=>1,'maintenance_reason'=>'Unknown','maintenance_roles'=>[999]])->assertSessionHasErrors('maintenance_roles.0');
        $this->assertSame('Upgrade database',app(SystemSettings::class)->get('maintenance_reason'));
        $this->signIn([2]);
        $this->put(route('admin.system-settings.update'), ['section'=>'maintenance','maintenance_reason'=>'Not allowed'])->assertForbidden();
    }

    public function test_database_settings_update_independently_and_do_not_write_files(): void
    {
        $path = sys_get_temp_dir().'/unused-settings-'.bin2hex(random_bytes(5)).'.enc';
        config(['system-settings.path'=>$path]);
        $writer = app(SystemSettings::class);
        $writer->save(['app_name'=>'Database App','smtp_password'=>'private-secret','aggregator_names'=>['One','Two']]);
        $other = new SystemSettings(); $other->save(['company_name'=>'Database Company']);
        $writer->refresh();
        $this->assertSame('Database App',$writer->get('app_name'));
        $this->assertSame('Database Company',$writer->get('company_name'));
        $this->assertSame(['One','Two'],$writer->get('aggregator_names'));
        $this->assertStringNotContainsString('private-secret',DB::table('system_settings')->where('key','smtp_password')->value('value'));
        $this->assertFileDoesNotExist($path);
    }

    public function test_migration_imports_old_settings_to_encrypted_database_rows(): void
    {
        Schema::drop('system_settings');
        $path = sys_get_temp_dir().'/legacy-settings-'.bin2hex(random_bytes(5)).'.enc';
        $legacy = ['app_name'=>'Imported App','smtp_password'=>'secret-import','aggregator_names'=>['One','Two']];
        file_put_contents($path, \Illuminate\Support\Facades\Crypt::encryptString(json_encode($legacy)));
        config(['system-settings.path'=>$path]);
        try {
            $migration = require database_path('migrations/2026_10_30_140000_create_system_settings_table.php');
            $migration->up();
            $this->app->forgetInstance(SystemSettings::class);
            foreach ($legacy as $key=>$value) $this->assertSame($value,app(SystemSettings::class)->get($key));
            $this->assertStringNotContainsString('secret-import',DB::table('system_settings')->where('key','smtp_password')->value('value'));
        } finally { unlink($path); }
    }
}
