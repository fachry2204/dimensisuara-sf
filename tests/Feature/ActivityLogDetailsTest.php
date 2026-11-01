<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\LogActivity;
use App\Models\User\Admin;
use App\Models\User\Profile;
use App\Services\ActivityLogDetails;
use App\Services\StatusNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivityLogDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'activity_testing','database.connections.activity_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        Schema::create('system_settings',function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('users',function ($t) { $t->id(); $t->string('name'); });
        Schema::create('user_admin',function ($t) { $t->id(); $t->string('name'); });
        Schema::create('user_profiles',function ($t) {
            $t->id(); $t->integer('userid'); $t->string('firstname'); $t->string('lastname')->nullable();
            $t->string('status'); $t->integer('aggregator_rate'); $t->boolean('whitelabel_enabled')->default(false);
            $t->string('accountnumber')->nullable(); $t->timestamps();
        });
        Schema::create('contracts',function ($t) {
            $t->id(); $t->integer('profileid'); $t->string('type'); $t->string('docnum');
            $t->date('contract_date'); $t->integer('user_percentage'); $t->integer('system_percentage'); $t->timestamps();
        });
        Schema::create('logs',function ($t) {
            $t->id(); foreach (['level','message','context','impersonate','ip','url'] as $field) $t->text($field)->nullable();
            $t->integer('userid')->nullable(); $t->integer('admin_user_id')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        DB::table('users')->insert(['id'=>1,'name'=>'Client']);
        DB::table('user_admin')->insert(['id'=>9,'name'=>'Admin Helen']);
        DB::table('user_profiles')->insert(['id'=>1,'userid'=>1,'firstname'=>'Client','lastname'=>'Sample','status'=>'pending','aggregator_rate'=>70,'accountnumber'=>'123456789']);
        DB::table('contracts')->insert(['id'=>460,'profileid'=>1,'type'=>'aggregator','docnum'=>'001/PKS/XI/2026','contract_date'=>'2026-11-01','user_percentage'=>70,'system_percentage'=>30]);
        $this->mock(StatusNotificationDispatcher::class)->shouldReceive('profile')->zeroOrMoreTimes();
        $admin = new class extends Admin { public function hasPermission(string $permission): bool { return true; } };
        $admin->forceFill(['id'=>9,'name'=>'Admin Helen','email_verified_at'=>now()]);
        $this->actingAs($admin,'admin');
        Log::setDefaultDriver('db');
    }

    protected function tearDown(): void { Log::setDefaultDriver('null'); DB::purge('activity_testing'); parent::tearDown(); }

    public function test_profile_changes_are_persisted_relationally_with_old_and_new_values_and_private_values_hidden(): void
    {
        $profile = Profile::find(1);
        $profile->status = 'verified';
        $profile->whitelabel_enabled = true;
        $profile->aggregator_rate = 80;
        $profile->accountnumber = '987654321';
        $profile->save();
        Log::info('User ID 1 has been updated by Admin ID 9');
        $log = LogActivity::latest('id')->firstOrFail();
        $details = app(ActivityLogDetails::class)->present($log);
        $this->assertSame('Admin Helen',$details['actor']);
        $this->assertSame('Client Sample',$details['subject']['name']);
        $changes = collect($details['changes'])->keyBy('label');
        $this->assertSame('pending',$changes['Status']['before']);
        $this->assertSame('verified',$changes['Status']['after']);
        $this->assertSame('Nonaktif',$changes['Whitelabel']['before']);
        $this->assertSame('Aktif',$changes['Whitelabel']['after']);
        $this->assertSame('70',$changes['Bagian Aggregator user (%)']['before']);
        $this->assertSame('80',$changes['Bagian Aggregator user (%)']['after']);
        $this->assertStringNotContainsString('123456789',$log->context);
        $this->assertStringNotContainsString('987654321',$log->context);
        $this->assertFalse(Schema::hasColumn('logs','context'));
        $count = LogActivity::count();
        $profile->save();
        $this->assertSame($count,LogActivity::count());
    }

    public function test_saved_contract_description_survives_later_edits_and_deletion(): void
    {
        $subject = app(ActivityLogDetails::class)->snapshot(Contract::find(460));
        Log::info('Contract created by admin.',['model_id'=>460,'model_type'=>Contract::class,'audit_subject'=>$subject]);
        DB::table('contracts')->where('id',460)->delete();
        DB::table('user_profiles')->where('id',1)->update(['firstname'=>'Changed']);
        $details = app(ActivityLogDetails::class)->present(LogActivity::latest('id')->first());
        $this->assertSame('Admin membuat kontrak.',$details['summary']);
        $this->assertSame('001/PKS/XI/2026',$details['subject']['name']);
        $values = collect($details['subject']['details'])->keyBy('label');
        $this->assertSame('Client Sample',$values['Pemilik kontrak']['value']);
        $this->assertSame('70',$values['Bagian user (%)']['value']);
    }

    public function test_old_log_resolves_current_contract_and_never_invents_previous_values(): void
    {
        Log::info('Contract created by admin.',['model_id'=>460,'model_type'=>Contract::class]);
        $log = LogActivity::latest('id')->first();
        $details = app(ActivityLogDetails::class)->present($log);
        $this->assertSame('001/PKS/XI/2026',$details['subject']['name']);
        $this->assertSame([],$details['changes']);
        $this->assertStringContainsString('data saat ini',$details['note']);
        DB::table('contracts')->delete();
        $this->assertNull(app(ActivityLogDetails::class)->present($log)['subject']);
        $this->getJson(route('admin.logactivity.show',$log->id))->assertOk()->assertJsonPath('data.details.actor','Admin Helen');
        $this->getJson(route('admin.logactivity.show',99999))->assertNotFound();
    }
}
