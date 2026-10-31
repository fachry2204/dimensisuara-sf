<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('legacy_import_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('sql_path', 1000);
            $table->string('sql_sha256', 64);
            $table->string('media_root', 1000)->nullable();
            $table->string('status', 40)->default('trial_queued');
            $table->json('stats')->nullable();
            $table->unsignedBigInteger('media_cursor')->default(0);
            $table->unsignedBigInteger('media_processed')->default(0);
            $table->string('error', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('legacy_import_run_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_import_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('record_id');
            $table->unique(['run_id', 'record_id']);
        });
        Schema::create('legacy_import_media_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_import_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('record_id');
            $table->string('field', 40);
            $table->string('status', 40);
            $table->string('message', 500)->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->timestamps();
            $table->unique(['run_id', 'record_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_media_results');
        Schema::dropIfExists('legacy_import_run_records');
        Schema::dropIfExists('legacy_import_runs');
    }
};
