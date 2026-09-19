<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, make userid nullable if it isn't already
        Schema::table('logs', function (Blueprint $table) {
            $table->unsignedBigInteger('userid')->nullable()->change();
        });

        // Clean up orphaned userid references
        DB::statement('
            UPDATE logs
            SET userid = NULL
            WHERE userid IS NOT NULL
            AND userid NOT IN (SELECT id FROM users)
        ');

        // Now add foreign key
        Schema::table('logs', function (Blueprint $table) {
            $table->foreign('userid')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropForeign(['userid']);
        });
    }
};
