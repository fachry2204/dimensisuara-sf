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
        Schema::table('user_profile_identities', function (Blueprint $table) {
            $table->dropForeign('user_profile_identities_profileid_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profile_identities', function (Blueprint $table) {
            $table->foreign('profileid', 'user_profile_identities_profileid_foreign')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('cascade');
        });
    }
};
