<?php

namespace Tests\Feature;

use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/LegacyImportTest.php';

class BulkReviewUploadTest extends LegacyImportTest
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Log::setDefaultDriver('null');
        Schema::create('release_histories',function ($t) {
            $t->id(); $t->integer('release_id'); $t->integer('user_id')->nullable(); $t->integer('admin_user_id')->nullable();
            $t->string('action'); $t->string('message'); $t->string('type'); $t->timestamps();
        });
        $pdo=DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('CONCAT_WS',fn ($separator,...$values)=>implode($separator,array_filter($values,fn ($v)=>$v!==null)));
        $this->loginAdmin();
    }

    protected function loginAdmin(array $denied=[]): void
    {
        $admin=new class extends \App\Models\User\Admin {
            public array $denied=[];
            public function hasPermission(string $permission): bool { return !in_array($permission,$this->denied,true); }
        };
        $admin->denied=$denied;
        $admin->forceFill(['id'=>91,'name'=>'Review Admin','email_verified_at'=>now()]);
        $admin->setRelation('unreadNotifications',collect());
        $this->actingAs($admin,'admin');
    }

    protected function release(string $title,string $status='review'): Release
    {
        return Release::create(['title'=>$title,'status'=>$status,'primaryartists'=>json_encode([['name'=>'Artist']])]);
    }

    public function test_selected_reviews_change_once_and_record_admin_history(): void
    {
        $first=$this->release('First'); $second=$this->release('Second'); $published=$this->release('Published','released');
        $payload=['all'=>false,'ids'=>[$first->id,$published->id,999]];
        $this->postJson(route('admin.releases.upload-reviewed'),$payload)->assertOk()->assertJsonPath('updated',1);
        $this->assertSame('uploading',$first->fresh()->status);
        $this->assertSame('review',$second->fresh()->status);
        $this->assertSame('released',$published->fresh()->status);
        $this->assertDatabaseHas('release_histories',['release_id'=>$first->id,'action'=>'adminUpload','admin_user_id'=>91]);
        $this->postJson(route('admin.releases.upload-reviewed'),$payload)->assertOk()->assertJsonPath('updated',0);
        $this->assertSame(1,DB::table('release_histories')->count());
    }

    public function test_only_submitted_page_ids_change_even_with_all_flag(): void
    {
        $first=$this->release('Garden One'); $excluded=$this->release('Garden Two');
        $other=$this->release('Ocean'); $pending=$this->release('Garden pending','pending');
        $this->postJson(route('admin.releases.upload-reviewed'),['all'=>true,'ids'=>[$first->id]])
            ->assertOk()->assertJsonPath('updated',1);
        $this->assertSame('uploading',$first->fresh()->status);
        foreach ([$excluded,$other] as $release) $this->assertSame('review',$release->fresh()->status);
        $this->assertSame('pending',$pending->fresh()->status);
        $this->postJson(route('admin.releases.upload-reviewed'),['all'=>true])->assertUnprocessable();
    }

    public function test_bulk_upload_requires_permissions_and_valid_selection(): void
    {
        $this->release('Review');
        $this->postJson(route('admin.releases.upload-reviewed'),['all'=>false,'ids'=>[]])->assertUnprocessable();
        $this->postJson(route('admin.releases.upload-reviewed'),['all'=>false,'ids'=>[1,1]])->assertUnprocessable();
        $this->loginAdmin(['manage-releases-status']);
        $this->postJson(route('admin.releases.upload-reviewed'),['ids'=>[1]])->assertForbidden();
        $this->loginAdmin(['view-releases-all']);
        $this->postJson(route('admin.releases.upload-reviewed'),['ids'=>[1]])->assertForbidden();
        $this->assertSame(0,DB::table('release_histories')->count());
    }

    public function test_batch_failure_rolls_back_statuses_and_histories(): void
    {
        $first=$this->release('First'); $second=$this->release('Second');
        DB::unprepared("CREATE TRIGGER reject_second_upload BEFORE UPDATE OF status ON releases WHEN NEW.id=".$second->id." BEGIN SELECT RAISE(ABORT,'test failure'); END");
        $this->postJson(route('admin.releases.upload-reviewed'),['ids'=>[$first->id,$second->id]])->assertStatus(500);
        $this->assertSame('review',$first->fresh()->status);
        $this->assertSame('review',$second->fresh()->status);
        $this->assertSame(0,DB::table('release_histories')->count());
    }
}
