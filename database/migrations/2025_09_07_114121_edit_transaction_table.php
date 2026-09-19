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
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('rupiah_exact', 18, 4)->default(0)->after('year');
            $table->decimal('aggregator_rupiah_exact', 18, 4)->default(0)->after('rupiah_exact');
            $table->decimal('client_rupiah_exact', 18, 4)->default(0)->after('aggregator_rupiah_exact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('rupiah_exact');
            $table->dropColumn('aggregator_rupiah_exact');
            $table->dropColumn('client_rupiah_exact');
        });
    }
};
