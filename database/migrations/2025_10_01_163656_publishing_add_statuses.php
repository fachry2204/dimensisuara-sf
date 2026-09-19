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
        Schema::table('publishings', function (Blueprint $table) {
            $table->renameColumn('duration', 'duration_custom');
            $table->bigInteger('duration')->unsigned()->nullable()->after('performer');
            
            $table->enum('status', ['draft','pending','rejected','expired','cancelled','review','completed'])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishings', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->renameColumn('duration_custom', 'duration');
            $table->enum('status', ['draft','pending','completed','rejected','expired','cancelled'])->default('draft')->change();
        });
    }
};
