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
        // First, make profileid nullable in both tables
        Schema::table('settlements', function (Blueprint $table) {
            $table->unsignedBigInteger('profileid')->nullable()->change();
        });

        Schema::table('settlement_items', function (Blueprint $table) {
            $table->unsignedBigInteger('profileid')->nullable()->change();
        });

        // Clean up orphaned profileid in settlements table
        DB::statement('
            UPDATE settlements
            SET profileid = NULL
            WHERE profileid IS NOT NULL
            AND profileid NOT IN (SELECT id FROM user_profiles)
        ');

        // Clean up orphaned profileid in settlement_items table
        DB::statement('
            UPDATE settlement_items
            SET profileid = NULL
            WHERE profileid IS NOT NULL
            AND profileid NOT IN (SELECT id FROM user_profiles)
        ');

        // Add foreign key to settlements table with SET NULL
        // This preserves settlement records for audit trail when profile is deleted
        Schema::table('settlements', function (Blueprint $table) {
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('set null');
        });

        // Add foreign key to settlement_items table with SET NULL
        Schema::table('settlement_items', function (Blueprint $table) {
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropForeign(['profileid']);
        });

        Schema::table('settlement_items', function (Blueprint $table) {
            $table->dropForeign(['profileid']);
        });
    }
};
