<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_artists', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_countries', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_monthly', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_multi', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_platforms', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_releases', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_sales', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });

        Schema::table('report_aggregate_tracks', function (Blueprint $table) {
            $table->bigInteger('quantity')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('report_aggregate_artists', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_countries', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_monthly', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_multi', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_platforms', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_releases', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_sales', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });

        Schema::table('report_aggregate_tracks', function (Blueprint $table) {
            $table->bigInteger('quantity')->unsigned()->default(0)->change();
        });
    }
};
