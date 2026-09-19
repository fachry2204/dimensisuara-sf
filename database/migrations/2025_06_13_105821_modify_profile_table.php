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
            $table->enum('position', ['direktur_utama','direktur'])->nullable()->after('companyname');
            $table->string('npwpcompany_num')->nullable()->after('npwppersonal_num');
            $table->string('nibcompany_num')->nullable()->after('npwpcompany_num');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('position');
            $table->dropColumn('npwpcompany_num');
            $table->dropColumn('nibcompany_num');
        });
    }
};
