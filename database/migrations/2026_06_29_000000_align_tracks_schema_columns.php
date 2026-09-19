<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clean up tracks with invalid versions/genres/subgenres
        // Convert any empty/0 string values to NULL first to prevent foreign key errors
        DB::table('tracks')
            ->whereIn('version', ['0', 'original', ''])
            ->update(['version' => null]);

        DB::table('tracks')
            ->whereIn('genre', ['0', ''])
            ->update(['genre' => null]);

        DB::table('tracks')
            ->whereIn('subgenre', ['0', 'N/A', ''])
            ->update(['subgenre' => null]);

        // 2. Clean up any invalid subgenres pointing to deleted/legacy subgenres (specifically 'N/A')
        DB::statement("UPDATE tracks SET subgenre = NULL WHERE subgenre IN (SELECT id FROM _subgenres WHERE name = 'N/A')");
        DB::statement("UPDATE releases SET subgenre = NULL WHERE subgenre IN (SELECT id FROM _subgenres WHERE name = 'N/A')");
        DB::statement("DELETE FROM _subgenres WHERE name = 'N/A'");

        // 3. Alter columns on tracks table to unsignedBigInteger
        Schema::table('tracks', function (Blueprint $table) {
            $table->unsignedBigInteger('genre')->nullable()->change();
            $table->unsignedBigInteger('subgenre')->nullable()->change();
            $table->unsignedBigInteger('version')->nullable()->change();
        });

        // 4. Add foreign keys
        Schema::table('tracks', function (Blueprint $table) {
            $table->foreign('genre')->references('id')->on('_genres')->onDelete('restrict');
            $table->foreign('subgenre')->references('id')->on('_subgenres')->onDelete('restrict');
            $table->foreign('version')->references('id')->on('_versions')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropForeign(['genre']);
            $table->dropForeign(['subgenre']);
            $table->dropForeign(['version']);

            $table->string('genre', 100)->nullable()->change();
            $table->string('subgenre', 100)->nullable()->change();
            $table->string('version', 100)->nullable()->default('original')->change();
        });
    }
};
