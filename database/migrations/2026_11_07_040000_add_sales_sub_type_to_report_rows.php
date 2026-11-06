<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('report_rows', 'sales_sub_type')) {
            Schema::table('report_rows', fn (Blueprint $table) => $table->string('sales_sub_type', 255)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('report_rows', 'sales_sub_type')) {
            Schema::table('report_rows', fn (Blueprint $table) => $table->dropColumn('sales_sub_type'));
        }
    }
};
