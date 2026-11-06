<?php

namespace Tests\Feature;

use App\Models\StatusNotificationDelivery;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutomationDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'automation_delete_test', 'database.connections.automation_delete_test'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'', 'foreign_key_constraints'=>true,
        ]]);
        Schema::create('system_settings', function ($t) { $t->string('key')->primary(); $t->text('value'); $t->timestamps(); });
        Schema::create('jobs', function ($t) { $t->id(); $t->string('queue'); $t->longText('payload'); $t->integer('attempts')->default(0); $t->integer('reserved_at')->nullable(); $t->integer('available_at'); $t->integer('created_at'); });
        Schema::create('failed_jobs', function ($t) { $t->id(); $t->string('uuid'); $t->string('connection'); $t->string('queue'); $t->longText('payload'); $t->text('exception'); $t->timestamp('failed_at'); });
        Schema::create('job_batches', function ($t) { $t->string('id')->primary(); $t->string('name'); $t->integer('total_jobs'); $t->integer('pending_jobs'); $t->integer('failed_jobs'); $t->text('failed_job_ids'); $t->text('options'); $t->integer('created_at'); $t->integer('cancelled_at')->nullable(); $t->integer('finished_at')->nullable(); });
        (require database_path('migrations/2026_10_31_120000_create_status_notification_deliveries.php'))->up();
        $this->withoutVite();
        $this->signIn();
    }

    protected function tearDown(): void { DB::purge('automation_delete_test'); parent::tearDown(); }

    private function signIn(bool $allowed = true): void
    {
        $user = new class extends User {
            public bool $allowed = true;
            public function hasPermission(string $permission): bool { return $permission === 'admin' || ($this->allowed && $permission === 'manage-automation'); }
        };
        $user->allowed = $allowed;
        $user->forceFill(['id'=>99, 'name'=>'Admin', 'email_verified_at'=>now()]);
        $user->setRelation('profile', null)->setRelation('unreadNotifications', collect());
        $this->actingAs($user);
    }

    private function delivery(string $channel = 'email', string $status = 'sent'): StatusNotificationDelivery
    {
        return StatusNotificationDelivery::create(['event_id'=>(string) \Illuminate\Support\Str::uuid(), 'event'=>'release.pending', 'channel'=>$channel, 'status'=>$status, 'user_id'=>1, 'payload'=>['recipient'=>'private@example.test']]);
    }

    private function queued(string $table = 'jobs', ?string $batch = null, $reserved = null, $command = null): int
    {
        $command ??= (object) ['batchId'=>$batch];
        $payload = json_encode(['displayName'=>'DemoJob', 'secret'=>'private-job-data', 'data'=>['command'=>serialize($command)]]);
        $values = ['queue'=>'default', 'payload'=>$payload];
        $values += $table === 'jobs' ? ['reserved_at'=>$reserved, 'available_at'=>time(), 'created_at'=>time()]
            : ['uuid'=>(string) \Illuminate\Support\Str::uuid(), 'connection'=>'database', 'exception'=>'private-exception', 'failed_at'=>now()];
        return DB::table($table)->insertGetId($values);
    }

    private function batch(): string
    {
        $id = (string) \Illuminate\Support\Str::uuid();
        DB::table('job_batches')->insert(['id'=>$id, 'name'=>'Batch Test', 'total_jobs'=>2, 'pending_jobs'=>2, 'failed_jobs'=>0, 'failed_job_ids'=>'[]', 'options'=>serialize([]), 'created_at'=>time()]);
        return $id;
    }

    private function destroyUrl(string $kind, $id): string { return route('admin.automation.records.destroy', compact('kind','id')); }
    private function historyUrl(string $channel, $id): string { return route('admin.automation.notifications.destroy', ['channel'=>$channel,'delivery'=>$id]); }

    private function destroyAllUrl(string $kind): string { return route('admin.automation.records.destroy-all', compact('kind')); }

    public function test_bulk_deletion_is_scoped_and_protects_running_jobs_and_batch_members(): void
    {
        $batch = $this->batch();
        $member = $this->queued(batch: $batch);
        $busy = $this->queued(reserved: time());
        $delivery = $this->delivery('email', 'pending');
        $pending = $this->queued(command: new \App\Jobs\SendStatusNotification($delivery->id));
        // More than a page of records must be removed, regardless of table pagination.
        for ($i=0; $i<15; $i++) $this->queued();
        $failed = $this->queued('failed_jobs');
        $failedMember = $this->queued('failed_jobs', $batch);
        $this->delete($this->destroyAllUrl('job'))->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('jobs', ['id'=>$pending]);
        $this->assertSame(2, DB::table('jobs')->count());
        $this->assertDatabaseHas('jobs', ['id'=>$busy]);
        $this->assertDatabaseHas('jobs', ['id'=>$member]);
        $this->assertDatabaseHas('failed_jobs', ['id'=>$failed]);
        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->delete($this->destroyAllUrl('failed-job'))->assertRedirect();
        $this->assertDatabaseMissing('failed_jobs', ['id'=>$failed]);
        $this->assertDatabaseHas('failed_jobs', ['id'=>$failedMember]);
        $this->assertDatabaseHas('job_batches', ['id'=>$batch]);

        $activeBatch = $this->batch();
        $active = $this->queued(batch: $activeBatch, reserved: time());
        $waiting = $this->queued(batch: $activeBatch);
        $this->delete($this->destroyAllUrl('batch'))->assertRedirect();
        $this->assertDatabaseMissing('job_batches', ['id'=>$batch]);
        $this->assertDatabaseMissing('jobs', ['id'=>$member]);
        $this->assertDatabaseMissing('failed_jobs', ['id'=>$failedMember]);
        $this->assertDatabaseHas('job_batches', ['id'=>$activeBatch]);
        $this->assertDatabaseHas('jobs', ['id'=>$active]);
        $this->assertDatabaseHas('jobs', ['id'=>$waiting]);
        $this->assertDatabaseHas('jobs', ['id'=>$busy]);
    }

    public function test_bulk_deletion_requires_permission_and_valid_kind_and_accepts_empty_sections(): void
    {
        $job = $this->queued();
        $this->signIn(false);
        foreach (['job','failed-job','batch'] as $kind) $this->deleteJson($this->destroyAllUrl($kind))->assertForbidden();
        $this->assertDatabaseHas('jobs', ['id'=>$job]);
        $this->signIn();
        $this->deleteJson($this->destroyAllUrl('other'))->assertNotFound();
        foreach (['job','failed-job','batch'] as $kind) $this->delete($this->destroyAllUrl($kind))->assertRedirect();
        $this->delete($this->destroyAllUrl('job'))->assertRedirect()->assertSessionHas('success', '0 data Pending Jobs dihapus.');
        $this->get(route('admin.automation.index'))->assertOk()->assertSee('data-automation-delete-all="Job Batches"', false)->assertSee('data-automation-delete-all="Pending Jobs"', false)->assertSee('data-automation-delete-all="Failed Jobs"', false);
    }

    public function test_notification_deletion_is_channel_scoped_and_preserves_processing_deliveries(): void
    {
        $email = $this->delivery(); $wa = $this->delivery('whatsapp'); $busy = $this->delivery('email', 'processing');
        $this->deleteJson($this->historyUrl('whatsapp', $email->id))->assertNotFound();
        $this->deleteJson($this->historyUrl('email', $busy->id))->assertStatus(409);
        $this->delete($this->historyUrl('email', $email->id))->assertRedirect(route('admin.automation.index', ['tab'=>'email']));
        $this->delete($this->historyUrl('whatsapp', $wa->id))->assertRedirect(route('admin.automation.index', ['tab'=>'whatsapp']));
        $this->assertFalse(StatusNotificationDelivery::whereKey($email->id)->exists());
        $this->assertTrue(StatusNotificationDelivery::whereKey($busy->id)->exists());
    }

    public function test_pending_notification_deleted_from_history_is_not_sent_by_its_queued_job(): void
    {
        $delivery = $this->delivery('email', 'pending');
        \Illuminate\Support\Facades\Mail::fake();
        $this->delete($this->historyUrl('email', $delivery->id))->assertRedirect();
        (new \App\Jobs\SendStatusNotification($delivery->id))->handle();
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_monitor_deletes_only_unreserved_jobs_and_keeps_related_delivery_consistent(): void
    {
        $delivery = $this->delivery('email', 'pending');
        $pending = $this->queued(command: new \App\Jobs\SendStatusNotification($delivery->id));
        $busy = $this->queued(reserved: time()); $failed = $this->queued('failed_jobs');
        $this->deleteJson($this->destroyUrl('job', $busy))->assertStatus(409);
        $this->delete($this->destroyUrl('job', $pending))->assertRedirect();
        $this->delete($this->destroyUrl('failed-job', $failed))->assertRedirect();
        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->assertDatabaseMissing('jobs', ['id'=>$pending]);
        $this->assertDatabaseHas('jobs', ['id'=>$busy]);
        $this->assertDatabaseMissing('failed_jobs', ['id'=>$failed]);
    }

    public function test_batch_deletion_is_atomic_with_active_members_and_cancels_only_its_waiting_members(): void
    {
        $batch = $this->batch();
        $pending = $this->queued(batch: $batch); $busy = $this->queued(batch: $batch, reserved: time());
        $failed = $this->queued('failed_jobs', $batch); $unrelated = $this->queued();
        $this->deleteJson($this->destroyUrl('batch', $batch))->assertStatus(409);
        $this->deleteJson($this->destroyUrl('job', $pending))->assertStatus(409);
        $this->deleteJson($this->destroyUrl('failed-job', $failed))->assertStatus(409);
        $this->delete($this->destroyUrl('batch', $batch))->assertRedirect(route('admin.automation.index', ['tab'=>'jobs']))->assertSessionHasErrors('automation');
        $this->assertDatabaseHas('jobs', ['id'=>$pending]);
        $this->assertDatabaseHas('job_batches', ['id'=>$batch]);
        DB::table('jobs')->where('id',$busy)->update(['reserved_at'=>null]);
        $this->delete($this->destroyUrl('batch', $batch))->assertRedirect();
        $this->assertDatabaseMissing('jobs', ['id'=>$pending]);
        $this->assertDatabaseMissing('jobs', ['id'=>$busy]);
        $this->assertDatabaseMissing('failed_jobs', ['id'=>$failed]);
        $this->assertDatabaseMissing('job_batches', ['id'=>$batch]);
        $this->assertDatabaseHas('jobs', ['id'=>$unrelated]);
    }

    public function test_monitor_reads_do_not_delete_invalid_payloads_or_expose_job_secrets(): void
    {
        $id = $this->queued();
        $invalid = $this->queued();
        DB::table('jobs')->where('id',$invalid)->update(['payload'=>'invalid']);
        $this->get(route('admin.automation.index'))->assertOk()->assertSee('DemoJob')->assertSee('Payload tidak dapat dibaca')->assertDontSee('private-job-data')->assertSee('delete-automation-modal');
        $this->assertDatabaseHas('jobs', ['id'=>$id]);
        $this->assertDatabaseHas('jobs', ['id'=>$invalid]);
    }

    public function test_deletion_requires_server_admin_permission_and_unknown_records_return_404(): void
    {
        $row = $this->delivery();
        $job = $this->queued();
        $this->signIn(false);
        $this->deleteJson($this->historyUrl('email', $row->id))->assertForbidden();
        $this->deleteJson($this->destroyUrl('job', $job))->assertForbidden();
        $this->signIn();
        $this->deleteJson($this->destroyUrl('job', 99999))->assertNotFound();
        $this->deleteJson($this->destroyUrl('job', $job.'abc'))->assertNotFound();
        $this->deleteJson($this->historyUrl('email', 99999))->assertNotFound();
        $this->assertDatabaseHas('jobs', ['id'=>$job]);
    }

    public function test_deletion_cleans_relational_payloads_for_notifications_jobs_failures_and_batches(): void
    {
        $delivery = $this->delivery(); $job = $this->queued(); $failed = $this->queued('failed_jobs'); $batch = $this->batch();
        // Legacy queue payloads were encrypted before normalization into typed rows.
        foreach (['jobs','failed_jobs'] as $table) {
            foreach (DB::table($table)->get() as $row) DB::table($table)->where('id',$row->id)->update(['payload'=>\Illuminate\Support\Facades\Crypt::encryptString($row->payload)]);
        }
        (require database_path('migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        $this->delete($this->historyUrl('email', $delivery->id))->assertRedirect();
        $this->delete($this->destroyAllUrl('job'))->assertRedirect();
        $this->delete($this->destroyAllUrl('failed-job'))->assertRedirect();
        $this->delete($this->destroyAllUrl('batch'))->assertRedirect();
        foreach (['notification_payload_values','queue_job_values','failed_job_values','batch_failure_values'] as $table) $this->assertSame(0, DB::table($table)->count());
    }
}
