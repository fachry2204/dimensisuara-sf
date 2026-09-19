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
            $table->decimal('rupiah_exact', 18, 4)->after('rupiah');
            $table->decimal('aggregator_rupiah', 18, 4)->after('rupiah_exact');
            $table->decimal('aggregator_rupiah_exact', 18, 4)->after('aggregator_rupiah');
            $table->decimal('client_rupiah', 18, 4)->after('aggregator_rupiah_exact');
            $table->decimal('client_rupiah_exact', 18, 4)->after('client_rupiah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_revenue_aggregates', function (Blueprint $table) {
            $table->dropColumn('rupiah_exact');
            $table->dropColumn('aggregator_rupiah_exact');
            $table->dropColumn('client_rupiah_exact');
        });
    }
};
