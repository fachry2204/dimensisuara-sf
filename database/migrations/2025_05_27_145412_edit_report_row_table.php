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
            $table->decimal('unit_price', 20, 12)->after('currency_code')->nullable();
            $table->decimal('mechanical_fee', 20, 12)->after('unit_price')->nullable();
            $table->decimal('gross_revenue', 20, 12)->after('mechanical_fee')->nullable();
            $table->decimal('client_share_rate', 10, 6)->after('gross_revenue');

            $table->decimal('rupiah_exact', 18, 4)->nullable()->after('rupiah');
            $table->integer('aggregator_rupiah')->after('rupiah_exact')->nullable();
            $table->decimal('aggregator_rupiah_exact', 18, 4)->nullable()->after('aggregator_rupiah');
            $table->integer('client_rupiah')->after('aggregator_rupiah_exact')->nullable();
            $table->decimal('client_rupiah_exact', 18, 4)->nullable()->after('client_rupiah');

            $table->integer('rupiah')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->dropColumn('unit_price');
            $table->dropColumn('mechanical_fee');
            $table->dropColumn('gross_revenue');
            $table->dropColumn('client_share_rate');
            $table->dropColumn('aggregator_rupiah');
            $table->dropColumn('client_rupiah');
            $table->integer('rupiah')->change();
        });
    }
};
