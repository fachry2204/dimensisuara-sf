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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');
            
            $table->string('type', 100);
            $table->integer('year')->unsigned();
            $table->integer('month')->unsigned();
            $table->integer('number')->unsigned();
            $table->date('date')->nullable();
            $table->enum('status', ['pending', 'published'])->default('pending');
            $table->json('fields')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['type', 'year', 'month', 'number']);
            $table->index(['type', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
