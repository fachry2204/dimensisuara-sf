<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('status_notification_deliveries',function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id');
            $table->string('event',80);
            $table->string('channel',20);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('release_id')->nullable();
            $table->string('status',30)->default('pending');
            $table->longText('payload'); // Encrypted recipient + immutable message snapshot.
            $table->unsignedInteger('attempts')->default(0);
            $table->string('error',500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id','channel']);
            $table->index(['channel','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('status_notification_deliveries'); }
};
