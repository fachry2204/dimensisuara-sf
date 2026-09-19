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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');

            $table->string('title', 100)->nullable();
            $table->string('titlelang', 10)->nullable();
            $table->string('version', 100)->nullable()->default('original');
            $table->string('genre', 100)->nullable();
            $table->string('subgenre', 100)->nullable();

            $table->json('primaryartists')->nullable();
            $table->json('featuredartists')->nullable();
            $table->json('contributors')->nullable();
            $table->json('productions')->nullable();
            $table->string('composer', 100)->nullable();

            $table->boolean('instrumental')->default(true);
            $table->text('lyrics')->nullable();
            $table->string('lyriclang', 100)->nullable();
            $table->string('explicit', 100)->nullable()->default('non-explicit');
            $table->string('songwriter', 100)->nullable();
            $table->string('isrc', 100)->nullable();

            $table->integer('duration')->unsigned()->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
