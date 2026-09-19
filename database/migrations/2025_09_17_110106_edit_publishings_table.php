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
            $table->string('duration')->nullable()->change();
            $table->enum('status', ['draft','pending','completed','rejected','expired','cancelled'])->default('draft')->change();
            $table->string('performer')->nullable()->change();
            $table->string('genre')->nullable()->change();
            $table->string('region')->nullable()->change();
            $table->string('iswc')->nullable()->change();
            $table->string('isrc')->nullable()->change();
            $table->date('due_date')->nullable()->change();
            $table->json('songwriters')->nullable()->after('isrc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishings', function (Blueprint $table) {
            $table->string('duration')->nullable()->change();
            $table->enum('status', ['pending','completed','rejected','expired','cancelled'])->default('pending')->change();
            $table->string('performer')->change();
            $table->string('genre')->change();
            $table->string('region')->change();
            $table->string('iswc')->change();
            $table->string('isrc')->change();
            $table->date('due_date')->change();
            $table->dropColumn('songwriters');
        });
    }
};
