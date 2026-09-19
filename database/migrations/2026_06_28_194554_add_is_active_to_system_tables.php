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
        foreach (['_contributors', '_productions', '_versions', '_genres', '_subgenres'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['_contributors', '_productions', '_versions', '_genres', '_subgenres'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
