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
        Schema::create('report_aggregate_multi', function (Blueprint $table) {
            $table->id();

            // Core ownership
            $table->bigInteger('profileid')->unsigned()->index();
            $table->bigInteger('trackid')->unsigned()->nullable()->index(); // sometimes unknown ISRC

            // Dimensions
            $table->string('platform')->nullable()->index();
            $table->string('country')->nullable()->index();     // ISO country code
            $table->string('sales_type')->nullable()->index();  // e.g., subscription, download
            $table->string('artist_name')->nullable()->index();  // e.g., subscription, download

            // Time
            $table->date('reporting_month')->index();
            $table->date('sales_month')->index();

            // Metrics
            $table->bigInteger('quantity')->unsigned()->default(0);
            $table->decimal('rupiah_exact', 18, 4)->nullable()->default(0);
            $table->decimal('aggregator_rupiah_exact', 18, 4)->nullable()->default(0);
            $table->decimal('client_rupiah_exact', 18, 4)->nullable()->default(0);

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(
                [
                    'profileid',
                    'trackid',
                    'platform',
                    'country',
                    'sales_type',
                    'artist_name',
                    'reporting_month',
                    'sales_month'
                ],
                'uniq_multi_dim'
            );

            // Composite indexes for common queries
            $table->index(['profileid', 'reporting_month', 'sales_month'], 'idx_profile_months');
            $table->index(['profileid', 'platform', 'reporting_month'], 'idx_profile_platform');
            $table->index(['profileid', 'country', 'reporting_month'], 'idx_profile_country');
            $table->index(['profileid', 'sales_type', 'reporting_month'], 'idx_profile_sales_type');
            $table->index(['profileid', 'artist_name', 'reporting_month'], 'idx_profile_artist_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_aggregate_multi');
    }
};
