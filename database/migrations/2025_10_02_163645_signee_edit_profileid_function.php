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
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropColumn('profileid');
            $table->bigInteger('profileid')->unsigned()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropColumn('profileid');
            $table->bigInteger('profileid')->unsigned()->after('publishingid');
        });
    }
};
