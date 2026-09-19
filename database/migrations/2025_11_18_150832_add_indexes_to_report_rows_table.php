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
            // Add index on status for WHERE clause performance
            $table->index('status', 'idx_report_rows_status');

            // Add composite index for GROUP BY performance in aggregate query
            // Order: profileid, trackid, platform, country, artist_name, sales_type, reporting_month, sales_month
            $table->index(
                ['profileid', 'trackid', 'platform', 'country', 'artist_name', 'sales_type', 'reporting_month', 'sales_month'],
                'idx_report_rows_aggregate'
            );

            // Add index on status + aggregate dimensions for optimal query performance
            $table->index(
                ['status', 'profileid', 'reporting_month', 'sales_month'],
                'idx_report_rows_status_profile_months'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            // Drop indexes in reverse order
            $table->dropIndex('idx_report_rows_status_profile_months');
            $table->dropIndex('idx_report_rows_aggregate');
            $table->dropIndex('idx_report_rows_status');
        });
    }
};
