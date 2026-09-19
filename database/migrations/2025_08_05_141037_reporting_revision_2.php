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
        Schema::table('report_imports', function (Blueprint $table) {
            $table->bigInteger('idr_rate')->unsigned()->after('sheet')->nullable();
            $table->dropColumn('matched_rows');
            $table->dropColumn('unmatched_rows');
            $table->dropColumn('completed_on');
            $table->enum('status', ['pending_import','pending_review','pending_rate','pending_calculation', 'completed', 'failed'])->default('pending_import')->change();
        });
        
        Schema::table('report_rows', function (Blueprint $table) {
            $table->float('net_revenue')->change();
            $table->bigInteger('trackid')->unsigned()->after('id')->nullable();
            $table->bigInteger('profileid')->unsigned()->after('trackid')->nullable();
            $table->bigInteger('idr_rate')->unsigned()->after('rate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_imports', function (Blueprint $table) {
            $table->dropColumn('idr_rate');
            $table->bigInteger('matched_rows')->unsigned()->after('sheet')->nullable();
            $table->bigInteger('unmatched_rows')->unsigned()->after('matched_rows')->nullable();
            $table->timestamp('completed_on')->after('unmatched_rows')->nullable();
            $table->enum('status', ['pending_import', 'pending_review', 'completed', 'failed'])->default('pending_import')->change();
        });
        
        Schema::table('report_rows', function (Blueprint $table) {
            $table->float('net_revenue')->nullable()->change();
            $table->dropColumn('trackid');
            $table->dropColumn('profileid');
            $table->dropColumn('idr_rate');
        });
    }
};
