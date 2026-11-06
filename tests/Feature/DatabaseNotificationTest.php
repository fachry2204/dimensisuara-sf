<?php

namespace Tests\Feature;

use App\Database\RelationalFields;
use App\Models\User\Admin;
use App\Models\User\User;
use App\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatabaseNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'database_notification_test', 'database.connections.database_notification_test'=>[
            'driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'', 'foreign_key_constraints'=>true,
        ]]);
        Schema::create('notifications', function ($table) {
            $table->string('id', 191)->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        RelationalFields::resetSchemaCache();
        Schema::create('user_admin', function ($table) {
            $table->id();
            $table->boolean('email_notifications_enabled')->default(false);
            $table->boolean('whatsapp_notifications_enabled')->default(false);
            $table->string('notification_phone')->nullable();
        });
        DB::table('user_admin')->insert(['id'=>42]);
    }

    protected function tearDown(): void
    {
        DB::purge('database_notification_test');
        RelationalFields::resetSchemaCache();
        parent::tearDown();
    }

    public function test_notification_ids_and_content_are_saved_for_admins_and_customers(): void
    {
        $this->assertNotificationsPersist();
    }

    public function test_notification_ids_and_content_are_saved_in_normalized_storage(): void
    {
        (require base_path('database/migrations/2026_11_01_130000_normalize_application_json_fields.php'))->up();
        $this->assertNotificationsPersist();
        $this->assertFalse(Schema::hasColumn('notifications', 'data'));
        $this->assertGreaterThan(0, DB::table('user_notification_values')->count());
    }

    private function assertNotificationsPersist(): void
    {
        foreach ([new Admin, new User] as $recipient) {
            // Equal IDs in different account tables must remain separate recipients.
            $recipient->forceFill(['id'=>42]);
            $notification = new class extends Notification {
                public function via(object $notifiable): array { return [DatabaseChannel::class]; }
                public function toDatabase(object $notifiable): array
                {
                    return ['subject'=>'Rilis diterima', 'route'=>['name'=>'releases.show', 'id'=>3710]];
                }
            };
            $notification->id = (string) Str::uuid();
            $recipient->notifyNow($notification);
            $stored = $recipient->notifications()->firstOrFail();
            $this->assertTrue(Str::isUuid($stored->id));
            $this->assertSame($notification->id, $stored->id);
            $this->assertSame($recipient->getMorphClass(), $stored->notifiable_type);
            $this->assertSame('Rilis diterima', $stored->data['subject']);
            $this->assertSame(3710, $stored->data['route']['id']);
            $this->assertNull($stored->read_at);
            $stored->markAsRead();
            $this->assertNotNull($stored->fresh()->read_at);
            $this->assertSame(0, $recipient->unreadNotifications()->count());
        }
        $this->assertSame(2, DB::table('notifications')->count());
        $this->assertSame(2, DB::table('notifications')->distinct()->count('id'));
    }
}
