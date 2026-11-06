<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_admin', function (Blueprint $table) {
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('whatsapp_notifications_enabled')->default(true);
            $table->string('notification_phone', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_admin', fn (Blueprint $table) => $table->dropColumn(['email_notifications_enabled', 'whatsapp_notifications_enabled', 'notification_phone']));
    }
};
