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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->integer('aggregator_rate')->after('nibcompany_num')->default(70);
            $table->integer('mcn_rate')->after('aggregator_rate')->default(70);
            $table->integer('publishing_rate')->after('mcn_rate')->default(70);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('aggregator_rate');
            $table->dropColumn('mcn_rate');
            $table->dropColumn('publishing_rate');
        });
    }
};
