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
        Schema::table('publishings', function (Blueprint $table) {
            $table->unsignedBigInteger('release_id')->nullable()->after('profileid');
            $table->unsignedBigInteger('track_id')->nullable()->after('release_id');

            $table->foreign('release_id')->references('id')->on('releases')->nullOnDelete();
            $table->foreign('track_id')->references('id')->on('tracks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishings', function (Blueprint $table) {
            $table->dropForeign(['release_id']);
            $table->dropForeign(['track_id']);
            $table->dropColumn(['release_id', 'track_id']);
        });
    }
};

