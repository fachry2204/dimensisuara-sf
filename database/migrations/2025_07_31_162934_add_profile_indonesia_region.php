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
            $table->string('provinceid')->nullable()->after('address2');
            $table->string('cityid')->nullable()->after('provinceid');
            $table->string('districtid')->nullable()->after('cityid');
            $table->string('villageid')->nullable()->after('districtid');

            $table->string('city', 100)->nullable()->change();
            $table->string('state', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('provinceid');
            $table->dropColumn('cityid');
            $table->dropColumn('districtid');
            $table->dropColumn('villageid');

            $table->string('city', 100)->change();
            $table->string('state', 100)->change();
        });
    }
};
