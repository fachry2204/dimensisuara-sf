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
        Schema::table('broadcasts', function (Blueprint $table) {
            // Tracking fields for queued broadcast sending
            $table->string('batch_id')->nullable()->after('status');
            $table->unsignedInteger('total_chunks')->default(0)->after('recipient_count');
            $table->unsignedInteger('completed_chunks')->default(0)->after('total_chunks');
            $table->unsignedInteger('failed_chunks')->default(0)->after('completed_chunks');

            // Extend status to allow failure states if Doctrine DBAL is available
            // Existing values: draft, pending, sent
            if (Schema::getColumnType('broadcasts', 'status') === 'enum') {
                $table->enum('status', ['draft', 'pending', 'sent', 'failed'])
                    ->default('draft')
                    ->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            if (Schema::hasColumn('broadcasts', 'batch_id')) {
                $table->dropColumn('batch_id');
            }
            if (Schema::hasColumn('broadcasts', 'total_chunks')) {
                $table->dropColumn('total_chunks');
            }
            if (Schema::hasColumn('broadcasts', 'completed_chunks')) {
                $table->dropColumn('completed_chunks');
            }
            if (Schema::hasColumn('broadcasts', 'failed_chunks')) {
                $table->dropColumn('failed_chunks');
            }

            // Best-effort rollback of status enum to original values
            if (Schema::getColumnType('broadcasts', 'status') === 'enum') {
                $table->enum('status', ['draft', 'pending', 'sent'])
                    ->default('draft')
                    ->change();
            }
        });
    }
};

