<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\Contract;
use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserContractSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'user_contract_testing','database.connections.user_contract_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        Schema::create('users',function ($t) { $t->id(); $t->string('email'); $t->timestamps(); });
        Schema::create('user_profiles',function ($t) { $t->id(); $t->integer('userid'); $t->string('firstname')->default('User'); $t->boolean('is_company')->default(false); $t->string('status')->default('verified'); });
        Schema::create('contracts',function ($t) { $t->id(); $t->integer('profileid'); $t->string('type'); });
        Schema::create('filestorage',function ($t) { $t->id(); $t->string('owner_type'); $t->integer('owner_id'); $t->string('type')->nullable(); });
        Schema::create('access_roles',function ($t) { $t->id(); $t->string('name'); });
        foreach (range(1,6) as $id) {
            DB::table('users')->insert(['id'=>$id,'email'=>'user'.$id.'@example.test']);
            DB::table('user_profiles')->insert(['id'=>$id,'userid'=>$id]);
        }
        // A customer without a profile is absent from the user list and its summaries.
        DB::table('users')->insert(['id'=>7,'email'=>'no-profile@example.test']);
        foreach ([[20,2,'aggregator'],[21,2,'aggregator'],[30,3,'aggregator'],[40,4,'aggregator'],[41,4,'aggregator'],[50,5,'publishing'],[60,6,'aggregator']] as [$id,$profile,$type]) {
            DB::table('contracts')->insert(['id'=>$id,'profileid'=>$profile,'type'=>$type]);
        }
        foreach ([[30,Contract::class,'digital_stamp'],[40,Contract::class,'digital_stamp'],[60,Contract::class,null],[60,Release::class,'digital_stamp']] as [$owner,$class,$type]) {
            DB::table('filestorage')->insert(['owner_id'=>$owner,'owner_type'=>$class,'type'=>$type]);
        }
    }

    protected function tearDown(): void { DB::purge('user_contract_testing'); parent::tearDown(); }

    public function test_cards_count_users_once_and_only_actual_contract_stamps_satisfy_the_stamp_check(): void
    {
        $view = app(UserController::class)->index();
        $data = $view->getData();
        $this->assertSame(6,$data['userSummary']['total']);
        $this->assertSame(4,$data['userSummary']['contract_generated']);
        $this->assertSame(2,$data['userSummary']['contract_missing']);
        $this->assertSame(3,$data['userSummary']['contract_unstamped']);
        $rows = $data['customers']->keyBy('id');
        foreach ([1=>[false,false],2=>[true,true],3=>[true,false],4=>[true,true],5=>[false,false],6=>[true,true]] as $id=>[$generated,$unstamped]) {
            $profile = $rows[$id]->profile;
            $this->assertSame($generated,$profile->has_generated_contract);
            $this->assertSame($unstamped,$profile->has_unstamped_contract);
            $this->assertSame($generated,$profile->toArray()['has_generated_contract']);
            $this->assertSame($unstamped,$profile->toArray()['has_unstamped_contract']);
        }
    }
}
