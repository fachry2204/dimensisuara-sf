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
        Schema::create('release_track', function (Blueprint $table) {
            $table->id();

            $table->foreignId('release_id')->constrained(
                table: 'releases',
                column: 'id'
            )->onDelete('cascade');

            $table->foreignId('track_id')->constrained(
                table: 'tracks',
                column: 'id'
            )->onDelete('cascade');
            
            $table->string('upc', 100)->nullable();
            $table->string('isrc', 100)->nullable();
            $table->integer('track_order')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_track');
    }
};
