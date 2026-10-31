<?php

namespace Tests\Feature;

use App\Models\User\Profile;
use App\Models\User\User;
use App\Models\User\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default'=>'navigation_testing',
            'database.connections.navigation_testing'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],
            'session.driver'=>'array',
        ]);
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id(); $table->integer('userid'); $table->string('firstname'); $table->string('status');
            $table->boolean('feature_aggregator'); $table->boolean('feature_publishing'); $table->boolean('feature_mcn');
        });
        Gate::define('admin', fn ($user) => (bool) $user->navigation_admin);
        app('request')->setLaravelSession(app('session.store'));
    }

    protected function tearDown(): void
    {
        DB::purge('navigation_testing');
        parent::tearDown();
    }

    private function signIn(bool $admin): void
    {
        $user = $admin ? new class extends Admin {
            public function hasPermission(string $permission): bool { return $permission === 'impersonate-users'; }
        } : new class extends User {
            public function hasPermission(string $permission): bool { return false; }
        };
        $user->forceFill(['id'=>1, 'name'=>'Navigation Test', 'navigation_admin'=>$admin]);
        $user->setRelation('profile', new Profile([
            'status'=>'verified', 'feature_aggregator'=>true, 'feature_publishing'=>true, 'feature_mcn'=>true,
        ]));
        $this->actingAs($user, $admin ? 'admin' : 'web');
    }

    public function test_admin_with_customer_profile_only_sees_admin_navigation(): void
    {
        $this->signIn(true);
        $html = view('layouts.sidebar')->render();
        $this->assertStringContainsString('Admin Home', $html);
        $this->assertStringNotContainsString('My Profile', $html);
        $this->assertStringNotContainsString('My Transactions', $html);
        $this->assertStringNotContainsString('href="'.route('analytics.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('home').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.home').'"', $html);
    }

    public function test_customer_sees_customer_navigation_without_admin_tools(): void
    {
        $this->signIn(false);
        $html = view('layouts.sidebar')->render();
        $this->assertStringContainsString('My Profile', $html);
        $this->assertStringContainsString('My Transactions', $html);
        $this->assertStringContainsString('href="'.route('releases.index').'"', $html);
        $this->assertStringNotContainsString('Administrative Tools', $html);
        $this->assertStringNotContainsString('Admin Home', $html);
    }

    public function test_impersonating_admin_sees_customer_menu_then_admin_menu_after_stopping(): void
    {
        $this->signIn(true);
        DB::table('users')->insert(['id'=>2, 'name'=>'Customer']);
        DB::table('user_profiles')->insert([
            'userid'=>2, 'firstname'=>'Customer', 'status'=>'verified',
            'feature_aggregator'=>true, 'feature_publishing'=>false, 'feature_mcn'=>false,
        ]);
        session(['impersonate'=>2]);
        $html = view('layouts.sidebar')->render();
        $this->assertStringContainsString('My Profile', $html);
        $this->assertStringContainsString('href="'.route('releases.index').'"', $html);
        $this->assertStringNotContainsString('Admin Home', $html);
        $this->assertStringNotContainsString('href="'.route('mcn.index').'"', $html);
        session()->forget('impersonate');
        $html = view('layouts.sidebar')->render();
        $this->assertStringContainsString('Admin Home', $html);
        $this->assertStringNotContainsString('My Profile', $html);
    }
}
