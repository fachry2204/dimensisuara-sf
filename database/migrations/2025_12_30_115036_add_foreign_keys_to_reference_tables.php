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
        // First, make columns nullable (for drafts)
        Schema::table('releases', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->nullable()->change();
            $table->unsignedBigInteger('genre')->nullable()->change();
            $table->unsignedBigInteger('subgenre')->nullable()->change();
        });

        // Clean up invalid references (set to NULL if reference doesn't exist)
        DB::statement('
            UPDATE releases
            SET version = NULL
            WHERE version IS NOT NULL
            AND version NOT IN (SELECT id FROM _versions)
        ');

        DB::statement('
            UPDATE releases
            SET genre = NULL
            WHERE genre IS NOT NULL
            AND genre NOT IN (SELECT id FROM _genres)
        ');

        DB::statement('
            UPDATE releases
            SET subgenre = NULL
            WHERE subgenre IS NOT NULL
            AND subgenre NOT IN (SELECT id FROM _subgenres)
        ');

        // Now add foreign keys with RESTRICT
        // NULL values are allowed (for drafts)
        // RESTRICT prevents deletion of reference data if it's being used (non-NULL)
        Schema::table('releases', function (Blueprint $table) {
            $table->foreign('version')
                ->references('id')
                ->on('_versions')
                ->onDelete('restrict');

            $table->foreign('genre')
                ->references('id')
                ->on('_genres')
                ->onDelete('restrict');

            $table->foreign('subgenre')
                ->references('id')
                ->on('_subgenres')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('releases', function (Blueprint $table) {
            $table->dropForeign(['version']);
            $table->dropForeign(['genre']);
            $table->dropForeign(['subgenre']);
        });
    }
};
