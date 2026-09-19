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
        Schema::create('report_aggregate_artists', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('profileid')->unsigned()->index();
            $table->string('artist_name');

            $table->date('reporting_month')->index();
            $table->date('sales_month')->index();

            $table->bigInteger('quantity')->unsigned()->default(0);
            $table->decimal('rupiah_exact', 18, 4)->nullable()->default(0);
            $table->decimal('aggregator_rupiah_exact', 18, 4)->nullable()->default(0);
            $table->decimal('client_rupiah_exact', 18, 4)->nullable()->default(0);
            $table->timestamps();

            $table->unique(
                [
                    'profileid',
                    'artist_name',
                    'reporting_month',
                    'sales_month'
                ],
                'uniq_profile_artist_report_sales'
            );

            $table->index(['profileid', 'sales_month'], 'idx_profile_sales');
            $table->index(['profileid', 'reporting_month'], 'idx_profile_reporting');
            $table->index(['profileid', 'reporting_month', 'sales_month'], 'idx_profile_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_aggregate_artists');
    }
};
