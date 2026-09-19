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
        Schema::create('release_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained(
                table: 'releases',
                column: 'id'
            )->onDelete('cascade');
            $table->string('action');
            $table->text('message');
            $table->enum('type', ['waiting', 'accepted', 'rejected'])->default('waiting');
            $table->foreignId('user_id')->nullable()->constrained(
                table: 'users',
                column: 'id'
            )->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_histories');
    }
};
