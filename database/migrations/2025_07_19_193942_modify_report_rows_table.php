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
            $table->string('region', 100)->change();
            $table->string('release_title', 255)->change();
            $table->string('track_title', 255)->change();
            $table->string('currency_code', 5)->nullable()->change();
            $table->decimal('net_revenue', 20, 12)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->string('region', 100)->nullable()->change();
            $table->string('release_title', 255)->nullable()->change();
            $table->string('track_title', 255)->nullable()->change();
            $table->string('currency_code', 5)->change();
            $table->decimal('net_revenue', 20, 12)->change();
        });
    }
};
