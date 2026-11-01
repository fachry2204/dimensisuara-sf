<?php

namespace Tests\Feature;

use App\Models\User\Admin;
use App\Models\User\Profile;
use App\Services\StatusNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkUserUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'bulk_testing','database.connections.bulk_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        Schema::create('system_settings',function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('users',function ($t) { $t->id(); $t->string('email'); $t->timestamp('email_verified_at')->nullable(); });
        Schema::create('user_admin',function ($t) { $t->id(); $t->string('email'); });
        Schema::create('user_profiles',function ($t) {
            $t->id(); $t->foreignId('userid')->unique()->constrained('users');
            $t->string('status')->default('pending'); $t->string('reason')->nullable(); $t->timestamp('delete_by')->nullable();
            foreach (['feature_aggregator','feature_publishing','feature_mcn','whitelabel_enabled'] as $field) $t->boolean($field)->default(false);
            $t->integer('aggregator_rate')->default(70); $t->timestamps();
        });
        foreach ([1,2,3] as $id) {
            DB::table('users')->insert(['id'=>$id,'email'=>'user'.$id.'@example.test']);
            DB::table('user_profiles')->insert(['userid'=>$id]);
        }
        DB::table('user_admin')->insert(['id'=>4,'email'=>'admin@example.test']);
        $this->mock(StatusNotificationDispatcher::class)->shouldReceive('profile')->zeroOrMoreTimes();
        $this->admin();
    }

    protected function tearDown(): void { DB::purge('bulk_testing'); parent::tearDown(); }

    private function admin(array $denied = []): void
    {
        $admin = new class extends Admin {
            public array $denied = [];
            public function hasPermission(string $permission): bool { return !in_array($permission,$this->denied,true); }
        };
        $admin->denied = $denied;
        $admin->forceFill(['id'=>4,'email_verified_at'=>now()]);
        $this->actingAs($admin,'admin');
    }

    private function updateUsers(array $changes, array $ids = [1,2], array $extra = [])
    {
        return $this->putJson(route('admin.users.bulk-update'),array_merge(['ids'=>$ids,'changes'=>$changes],$extra));
    }

    public function test_only_selected_users_and_requested_fields_are_updated_and_repeating_is_a_noop(): void
    {
        $this->updateUsers(['feature_aggregator'=>1,'whitelabel_enabled'=>1])->assertOk()->assertJsonPath('updated',2);
        foreach ([1,2] as $id) {
            $profile = Profile::where('userid',$id)->first();
            $this->assertTrue($profile->whitelabel_enabled);
            $this->assertSame(1,(int) $profile->feature_aggregator);
            $this->assertSame(0,(int) $profile->feature_publishing);
            $this->assertSame('pending',$profile->status);
            $this->assertSame(70,$profile->aggregator_rate);
        }
        $this->assertSame(0,(int) Profile::where('userid',3)->value('feature_aggregator'));
        $this->updateUsers(['feature_aggregator'=>1,'whitelabel_enabled'=>1])->assertOk()->assertJsonPath('updated',0);
        $this->updateUsers(['feature_aggregator'=>0,'feature_publishing'=>1,'feature_mcn'=>1,'whitelabel_enabled'=>0])->assertOk();
        $this->assertSame(2,Profile::where('feature_publishing',1)->where('feature_mcn',1)->where('whitelabel_enabled',0)->count());
    }

    public function test_status_updates_use_model_observers_and_do_not_verify_email_or_schedule_deletion(): void
    {
        $dispatcher = $this->mock(StatusNotificationDispatcher::class);
        $dispatcher->shouldReceive('profile')->times(4);
        $this->updateUsers(['status'=>'blocked'],[1,2],['reason'=>'Review akun'])->assertOk();
        $this->assertSame(2,Profile::where('status','blocked')->where('reason','Review akun')->whereNull('delete_by')->count());
        $this->updateUsers(['status'=>'verified'])->assertOk();
        $this->assertSame(2,Profile::where('status','verified')->whereNull('reason')->whereNull('delete_by')->count());
        $this->assertSame(0,DB::table('users')->whereNotNull('email_verified_at')->count());
    }

    public function test_status_and_feature_permissions_are_checked_independently_and_mixed_updates_are_atomic(): void
    {
        $this->admin(['manage-user-features']);
        $this->updateUsers(['status'=>'verified','feature_mcn'=>1])->assertForbidden();
        $this->assertSame(0,Profile::where('status','verified')->count());
        $this->updateUsers(['status'=>'verified'])->assertOk();
        $this->admin(['manage-user-status']);
        $this->updateUsers(['status'=>'pending'])->assertForbidden();
        $this->updateUsers(['whitelabel_enabled'=>1])->assertOk();
        $this->admin(['view-users']);
        $this->updateUsers(['feature_mcn'=>1])->assertForbidden();
    }

    public function test_invalid_targets_and_values_never_partially_update_profiles(): void
    {
        $this->updateUsers(['feature_mcn'=>1],[1,4])->assertUnprocessable()->assertJsonValidationErrors('ids');
        $this->assertSame(0,Profile::where('feature_mcn',1)->count());
        foreach ([[],['status'=>'invalid'],['feature_mcn'=>'yes'],['aggregator_rate'=>100]] as $changes) {
            $this->updateUsers($changes)->assertUnprocessable();
        }
        $this->updateUsers(['status'=>'blocked'])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->updateUsers(['feature_mcn'=>1],[])->assertUnprocessable();
        $this->updateUsers(['feature_mcn'=>1],[1,1])->assertUnprocessable();
    }
}
