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
        Schema::table('publishing_songwriters', function (Blueprint $table) {
            $table->string('lmk')->nullable();
            $table->string('publisher')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_songwriters', function (Blueprint $table) {
            $table->dropColumn(['lmk', 'publisher']);
        });
    }
};
