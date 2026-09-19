<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publishings', function (Blueprint $table) {
            $table->string('duration_minute')->default(0)->nullable()->change();
            $table->string('duration_second')->default(0)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishings', function (Blueprint $table) {
            $table->string('duration_minute')->default(0)->change();
            $table->string('duration_second')->default(0)->change();
        });
    }
};
