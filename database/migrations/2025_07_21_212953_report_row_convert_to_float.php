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
            $table->float('net_revenue')->nullable()->change();
            $table->float('unit_price')->nullable()->change();
            $table->float('mechanical_fee')->nullable()->change();
            $table->float('gross_revenue')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->decimal('net_revenue', 20, 12)->nullable()->change();
            $table->decimal('unit_price', 20, 12)->nullable()->change();
            $table->decimal('mechanical_fee', 20, 12)->nullable()->change();
            $table->decimal('gross_revenue', 20, 12)->nullable()->change();
        });
    }
};
