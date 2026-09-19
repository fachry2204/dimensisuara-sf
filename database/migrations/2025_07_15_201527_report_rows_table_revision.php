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
            $table->dropColumn(['releaseid', 'trackid', 'profileid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_rows', function (Blueprint $table) {
            $table->bigInteger('releaseid')->unsigned()->nullable()->after('rupiah');
            $table->bigInteger('trackid')->unsigned()->nullable()->after('releaseid');
            $table->bigInteger('profileid')->unsigned()->nullable()->after('trackid');
        });
    }
};
