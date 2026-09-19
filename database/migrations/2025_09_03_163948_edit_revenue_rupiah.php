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
        Schema::table('report_revenue_aggregates', function (Blueprint $table) {
            $table->integer('rupiah')->default(0)->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_revenue_aggregates', function (Blueprint $table) {
            $table->bigInteger('rupiah')->unsigned()->default(0)->change();
        });
    }
};
