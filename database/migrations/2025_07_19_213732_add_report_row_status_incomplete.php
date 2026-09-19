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
        Schema::table('report_rows', function (Blueprint $table) {
            $table->enum('status', ['pending', 'incomplete', 'finalized'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->enum('status', ['pending', 'match', 'missmatch', 'finalized'])->default('pending')->change();
        });
    }
};
