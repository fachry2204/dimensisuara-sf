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
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['date','status', 'fields']);
            $table->string('sort', 15)->nullable()->after('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->date('date')->nullable()->after('number');
            $table->enum('status', ['pending', 'published'])->default('pending')->after('date');
            $table->json('fields')->nullable()->after('status');
        });
    }
};
