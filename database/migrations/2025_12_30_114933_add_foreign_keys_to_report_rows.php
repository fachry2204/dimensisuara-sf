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
        // Clean up orphaned trackid references (trackid that don't exist in tracks table)
        DB::statement('
            UPDATE report_rows
            SET trackid = NULL
            WHERE trackid IS NOT NULL
            AND trackid NOT IN (SELECT id FROM tracks)
        ');

        // Clean up orphaned profileid references (profileid that don't exist in user_profiles table)
        DB::statement('
            UPDATE report_rows
            SET profileid = NULL
            WHERE profileid IS NOT NULL
            AND profileid NOT IN (SELECT id FROM user_profiles)
        ');

        // Now add foreign keys
        Schema::table('report_rows', function (Blueprint $table) {
            $table->foreign('trackid')
                ->references('id')
                ->on('tracks')
                ->onDelete('set null');

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
        Schema::table('report_rows', function (Blueprint $table) {
            $table->dropForeign(['trackid']);
            $table->dropForeign(['profileid']);
        });
    }
};
