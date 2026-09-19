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
        Schema::create('_subgenres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genreid')->constrained(
                table: '_genres',
                column: 'id'
            )->onDelete('cascade');
            $table->string('name', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_subgenres');
    }
};
