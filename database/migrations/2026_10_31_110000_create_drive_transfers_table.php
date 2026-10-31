<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drive_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id')->unique();
            $table->text('local_identifier');
            $table->text('remote_identifier')->nullable();
            $table->string('status', 40)->default('pending');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('md5', 32)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('error', 500)->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void { Schema::dropIfExists('drive_transfers'); }
};
