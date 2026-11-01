<?php

namespace Tests\Feature;

use App\Database\RelationalFields;
use App\Models\Announcement;
use App\Models\Broadcast;
use App\Models\DatabaseNotification;
use App\Models\LegacyImportRun;
use App\Models\Release;
use App\Models\StatusNotificationDelivery;
use App\Models\SystemSetting;
use App\Models\Track;
use App\Services\SystemSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RelationalStorageTest extends TestCase
{
    private $migration;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'relational_testing','database.connections.relational_testing'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]]);
        Log::setDefaultDriver('null');
        foreach (RelationalFields::MAP as $owner=>$map) {
            Schema::create($owner, function ($t) use ($owner,$map) {
                if ($owner === 'system_settings') $t->string('key')->primary();
                elseif ($map['string_key'] ?? false) $t->string('id')->primary();
                else $t->id();
                foreach ($map['fields'] as $field=>$format) $t->longText($field)->nullable();
                if (in_array($owner,['releases','tracks','announcements'],true)) $t->string('title')->nullable();
                if (in_array($owner,['releases','publishings','broadcasts','legacy_import_runs'],true)) $t->string('status')->nullable();
                if ($owner === 'broadcasts') $t->timestamp('deleted_at')->nullable();
                if ($owner === 'notifications') { $t->string('type')->nullable(); $t->string('notifiable_type')->nullable(); $t->integer('notifiable_id')->nullable(); $t->timestamp('read_at')->nullable(); }
                if ($owner === 'logs') { foreach (['level','message','userid','admin_user_id','impersonate','ip','url'] as $field) $t->text($field)->nullable(); }
                if ($owner === 'jobs') { $t->string('queue')->default('default'); $t->integer('attempts')->default(0); $t->integer('reserved_at')->nullable(); $t->integer('available_at')->default(0); }
                if ($owner === 'failed_jobs') { $t->string('uuid')->nullable(); $t->string('connection')->nullable(); $t->string('queue')->nullable(); $t->text('exception')->nullable(); $t->timestamp('failed_at')->nullable(); }
                if ($owner === 'job_batches') { $t->string('name')->default('Batch'); $t->integer('total_jobs')->default(0); $t->integer('pending_jobs')->default(0); $t->integer('failed_jobs')->default(0); $t->text('options')->nullable(); $t->integer('cancelled_at')->nullable(); $t->integer('finished_at')->nullable(); }
                $t->timestamps();
            });
        }
        $this->migration = require base_path('database/migrations/2026_11_01_130000_normalize_application_json_fields.php');
        $this->app->forgetInstance(SystemSettings::class);
    }

    protected function tearDown(): void { DB::purge('relational_testing'); parent::tearDown(); }

    private function insertLegacy(string $owner, array $values): int|string
    {
        $map = RelationalFields::MAP[$owner];
        $id = $owner === 'system_settings' ? 'audit.setting' : (($map['string_key'] ?? false) ? 'uuid-notification' : 1);
        $data = [($map['key'] ?? 'id')=>$id];
        foreach ($values as $field=>$value) {
            $raw = ($owner === 'system_settings' || $map['fields'][$field] === 'serialized')
                ? serialize($value) : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $data[$field] = ($map['encrypted'] ?? false) ? Crypt::encryptString($raw) : $raw;
        }
        DB::table($owner)->insert($data);
        return $id;
    }

    public function test_backfill_preserves_all_values_and_drops_every_old_application_payload_column(): void
    {
        $value = (object) ['name'=>'Artist "One"','list'=>[1,true,false,null,0.125,(object) []], 'object'=>(object) ['x'=>'é']];
        foreach (RelationalFields::MAP as $owner=>$map) {
            $fields = array_fill_keys(array_keys($map['fields']), in_array($owner,['system_settings','legacy_import_records'],true) ? ['private'=>'secret'] : $value);
            $this->insertLegacy($owner,$fields);
        }
        $this->migration->up();
        foreach (RelationalFields::MAP as $owner=>$map) {
            $id = $owner === 'system_settings' ? 'audit.setting' : (($map['string_key'] ?? false) ? 'uuid-notification' : 1);
            foreach ($map['fields'] as $field=>$format) {
                $this->assertFalse(Schema::hasColumn($owner,$field), $owner.'.'.$field);
                $expected = in_array($owner,['system_settings','legacy_import_records'],true) ? ['private'=>'secret'] : $value;
                $this->assertSame(serialize($expected),serialize(RelationalFields::read($owner,$id,$field)));
            }
            foreach (Schema::getColumns($map['table']) as $column) $this->assertNotSame('json',$column['type_name']);
        }
        $this->assertStringNotContainsString('secret', implode('',DB::table('system_setting_values')->pluck('scalar_value')->all()));
    }

    public function test_model_creates_updates_refreshes_and_serializes_credit_relations_without_json_columns(): void
    {
        $this->migration->up();
        $release = Release::create(['title'=>'First release','primaryartists'=>json_encode([['name'=>'Artist','social'=>'example.test']])]);
        $this->assertSame('Artist',$release->fresh()->primaryartists[0]->name);
        $this->assertArrayNotHasKey('primaryartists',DB::table('releases')->first() ? (array) DB::table('releases')->first() : []);
        $release->primaryartists = [['name'=>'Updated Artist','spotify'=>'spotify.test']];
        $release->save();
        $release->refresh();
        $this->assertSame('Updated Artist',$release->primaryartists[0]->name);
        $this->assertSame('spotify.test',$release->primaryartists[0]->spotify);
        $release->setAppends([]);
        $release->load('relationalFieldRows');
        $this->assertArrayNotHasKey('relational_field_rows',$release->toArray());
        $this->assertSame('Updated Artist',$release->toArray()['primaryartists'][0]->name);
        $track = Track::create(['title'=>'Track','primaryartists'=>[['name'=>'Primary']], 'featuredartists'=>[], 'contributors'=>[['name'=>'Writer','role'=>3]], 'productions'=>[], 'songwriters'=>[['name'=>'Composer','role'=>'composer']]]);
        $fresh = $track->fresh();
        $this->assertSame('Primary',$fresh->primaryartists[0]->name);
        $this->assertSame([],$fresh->featuredartists);
        $this->assertSame(3,$fresh->contributors[0]->role);
        $this->assertSame('composer',$fresh->songwriters[0]['role']);
        $fresh->setAppends([]);
        $this->assertSame('Primary',$fresh->toArray()['primaryartists'][0]->name);
        $this->assertSame(2,$release->relationalFieldRows()->where('key_name','name')->count() + $track->relationalFieldRows()->where('key_name','name')->where('field','primaryartists')->count());
    }

    public function test_settings_arrays_and_secrets_use_encrypted_relational_rows(): void
    {
        $this->insertLegacy('system_settings',['value'=>['nested'=>['items'=>[1,'two']]]]);
        $this->migration->up();
        $settings = app(SystemSettings::class);
        $this->assertSame(['nested'=>['items'=>[1,'two']]],$settings->get('audit.setting'));
        $settings->save(['app_name'=>'Relational App','smtp_password'=>'private-password','aggregator_names'=>['First','Second']]);
        $this->app->forgetInstance(SystemSettings::class);
        $settings = app(SystemSettings::class);
        $this->assertSame('Relational App',$settings->get('app_name'));
        $this->assertSame('private-password',$settings->get('smtp_password'));
        $this->assertSame(['First','Second'],$settings->aggregatorNames());
        $stored = implode('',DB::table('system_setting_values')->pluck('scalar_value')->all());
        $this->assertStringNotContainsString('private-password',$stored);
        $this->assertFalse(Schema::hasColumn('system_settings','value'));
    }

    public function test_notifications_announcements_broadcasts_and_stats_keep_their_public_shapes(): void
    {
        $this->migration->up();
        $announcement = Announcement::create(['title'=>'News','content'=>['type'=>'doc','content'=>[['type'=>'paragraph','text'=>'Hello']]]]);
        $this->assertSame('Hello',$announcement->fresh()->content['content'][0]['text']);
        $broadcast = Broadcast::create(['excluded_user_ids'=>[2,9]]);
        $this->assertSame([2,9],$broadcast->fresh()->excluded_user_ids);
        $run = LegacyImportRun::create(['stats'=>['users.imported'=>28,'files'=>['missing'=>2]]]);
        $this->assertSame(28,$run->fresh()->stats['users.imported']);
        $delivery = StatusNotificationDelivery::create(['payload'=>['body'=>'Private message','nested'=>['value'=>1]]]);
        $this->assertSame('Private message',$delivery->fresh()->payload['body']);
        $this->assertStringNotContainsString('Private message',implode('',DB::table('notification_payload_values')->pluck('scalar_value')->all()));
        $user = new \App\Models\User\User; $user->forceFill(['id'=>42]);
        $notice = $user->notifications()->create(['id'=>'notice-uuid','type'=>'Test','data'=>['title'=>'Saved in relations']]);
        $this->assertInstanceOf(DatabaseNotification::class,$notice);
        $this->assertSame('Saved in relations',$user->unreadNotifications()->first()->data['title']);
        $notice->markAsRead();
        $this->assertNotNull($notice->fresh()->read_at);
    }

    public function test_direct_import_and_logging_inserts_do_not_target_dropped_columns(): void
    {
        $this->migration->up();
        $id = RelationalFields::insert('releases',['title'=>'Imported','primaryartists'=>json_encode([['name'=>'Legacy artist']])]);
        $this->assertSame('Legacy artist',Release::find($id)->primaryartists[0]->name);
        $log = RelationalFields::insert('logs',['message'=>'Audit','context'=>json_encode(['target_id'=>$id])]);
        $this->assertSame($id,json_decode(\App\Models\LogActivity::find($log)->context,true)['target_id']);
        $legacy = RelationalFields::insert('legacy_import_records',['payload'=>Crypt::encryptString(serialize(['id'=>44,'ktp_doc_path'=>'private/document.jpg']))]);
        $record = DB::table('legacy_import_records')->find($legacy);
        $this->assertFalse(property_exists($record,'payload'));
        $this->assertSame(['id'=>44,'ktp_doc_path'=>'private/document.jpg'],RelationalFields::legacyPayload($record));
    }

    public function test_invalid_legacy_json_aborts_before_any_source_column_is_dropped(): void
    {
        DB::table('releases')->insert(['primaryartists'=>'not valid JSON']);
        try { $this->migration->up(); $this->fail('Invalid legacy data should stop conversion.'); }
        catch (\JsonException) { $this->assertTrue(Schema::hasColumn('releases','primaryartists')); }
        $this->assertSame('not valid JSON',DB::table('releases')->value('primaryartists'));
        $this->assertTrue(Schema::hasColumn('tracks','songwriters'));
    }

    public function test_foreign_keys_remove_child_values_when_parent_is_deleted(): void
    {
        $this->migration->up();
        $announcement = Announcement::create(['title'=>'Disposable','content'=>['text'=>'Hello']]);
        $this->assertGreaterThan(0,DB::table('announcement_content_values')->count());
        DB::table('announcements')->where('id',$announcement->id)->delete();
        $this->assertSame(0,DB::table('announcement_content_values')->count());
    }

    public function test_explicit_rollback_restores_legacy_values_including_encryption(): void
    {
        $this->insertLegacy('releases',['primaryartists'=>[['name'=>'Rollback artist']]]);
        $this->insertLegacy('system_settings',['value'=>['secret'=>'hidden']]);
        $this->insertLegacy('status_notification_deliveries',['payload'=>['body'=>'Private']]);
        $this->migration->up();
        $this->migration->down();
        $this->assertSame('Rollback artist',json_decode(DB::table('releases')->value('primaryartists'),true)[0]['name']);
        $this->assertSame(['secret'=>'hidden'],unserialize(Crypt::decryptString(DB::table('system_settings')->value('value'))));
        $this->assertSame(['body'=>'Private'],json_decode(Crypt::decryptString(DB::table('status_notification_deliveries')->value('payload')),true));
        $this->assertFalse(Schema::hasTable('release_artist_values'));
    }
    public function test_database_queue_push_pop_release_delete_and_bulk_use_only_relational_payloads(): void
    {
        $this->migration->up();
        $queue = new \App\Queue\RelationalDatabaseQueue(DB::connection(),'jobs','default',90,false);
        $queue->setContainer($this->app); $queue->setConnectionName('database');
        $payload = json_encode(['uuid'=>'queue-uuid','displayName'=>'Test','job'=>'Example@handle','data'=>['message'=>'Private queued message']],JSON_THROW_ON_ERROR);
        $id = $queue->pushRaw($payload);
        $this->assertFalse(Schema::hasColumn('jobs','payload'));
        $this->assertGreaterThan(0,DB::table('queue_job_values')->where('owner_id',$id)->count());
        $this->assertStringNotContainsString('Private queued message',implode('',DB::table('queue_job_values')->pluck('scalar_value')->all()));
        $job = $queue->pop();
        $this->assertSame('Private queued message',$job->payload()['data']['message']);
        $job->release(0);
        $again = $queue->pop();
        $this->assertSame(2,$again->attempts());
        $again->delete();
        $this->assertSame(0,DB::table('jobs')->count());
        $this->assertSame(0,DB::table('queue_job_values')->count());
        $queue->bulk(['Example@handle','Example@handle']);
        $this->assertSame(2,$queue->size());
        $queue->clear('default');
        $this->assertSame(0,DB::table('queue_job_values')->count());
    }

    public function test_failed_job_listing_and_retry_payloads_are_reconstructed_from_relations(): void
    {
        $this->migration->up();
        $provider = new \App\Queue\RelationalFailedJobs($this->app['db'],'relational_testing','failed_jobs');
        $payload = json_encode(['uuid'=>'failed-uuid','job'=>'Example@handle','data'=>['value'=>'retry-me']],JSON_THROW_ON_ERROR);
        $this->assertSame('failed-uuid',$provider->log('database','default',$payload,new \RuntimeException('Test')));
        $record = $provider->find('failed-uuid');
        $this->assertSame('retry-me',json_decode($record->payload,true)['data']['value']);
        $this->assertCount(1,$provider->all());
        $this->assertFalse(Schema::hasColumn('failed_jobs','payload'));
        $this->assertTrue($provider->forget('failed-uuid'));
        $this->assertSame(0,DB::table('failed_job_values')->count());
    }

    public function test_batch_failure_ids_use_relations_while_counts_and_retry_remain_consistent(): void
    {
        $this->migration->up();
        $repository = new \App\Queue\RelationalBatchRepository($this->app->make(\Illuminate\Bus\BatchFactory::class),DB::connection(),'job_batches');
        $pending = new \Illuminate\Bus\PendingBatch($this->app,collect());
        $batch = $repository->store($pending->name('Relational Batch'));
        $repository->incrementTotalJobs($batch->id,2);
        $repository->incrementFailedJobs($batch->id,'job-one');
        $this->assertSame(['job-one'],$repository->find($batch->id)->failedJobIds);
        $repository->decrementPendingJobs($batch->id,'job-one');
        $this->assertSame([],$repository->find($batch->id)->failedJobIds);
        $this->assertSame(1,$repository->find($batch->id)->pendingJobs);
        $this->assertFalse(Schema::hasColumn('job_batches','failed_job_ids'));
        $repository->delete($batch->id);
        $this->assertSame(0,DB::table('batch_failure_values')->count());
    }

    public function test_registered_queue_and_batch_drivers_use_relational_adapters(): void
    {
        $this->migration->up();
        config(['queue.batching.database'=>'relational_testing','queue.failed.database'=>'relational_testing']);
        $this->assertInstanceOf(\App\Queue\RelationalDatabaseQueue::class,app('queue')->connection('legacy-import'));
        $this->assertInstanceOf(\App\Queue\RelationalFailedJobs::class,app('queue.failer'));
        $this->assertInstanceOf(\App\Queue\RelationalBatchRepository::class,app(\Illuminate\Bus\DatabaseBatchRepository::class));
    }

    public function test_audit_is_read_only_and_reports_a_normalized_schema_successfully(): void
    {
        $this->migration->up();
        config(['session.driver'=>'database','cache.default'=>'database','queue.default'=>'database','logging.default'=>'db']);
        $before = DB::table('releases')->count();
        $this->artisan('system:audit-storage',['--integrity'=>true])->expectsOutputToContain('Native JSON columns: 0')->assertSuccessful();
        $this->assertSame($before,DB::table('releases')->count());
    }

}
