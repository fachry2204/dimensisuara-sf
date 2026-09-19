<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filestorage', function (Blueprint $table) {
            $table->id();

            // Primary location
            $table->string('disk')->default('local');
            $table->text('identifier');

            // Optional backup
            $table->text('backup_identifier')->nullable();
            $table->timestamp('backed_up_at')->nullable();

            // Metadata
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->unsigned()->nullable();
            $table->timestamp('uploaded_at')->nullable();

            // Usage
            $table->string('type')->nullable();
            $table->morphs('owner');
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');

            $table->unique(['owner_type', 'owner_id', 'type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filestorage');
    }
};
