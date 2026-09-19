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
        Schema::create('publishing_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publishingid')->nullable()->constrained(
                table: 'publishings',
                column: 'id'
            )->onDelete('set null');
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');
            $table->enum('type', ['statement', 'nuon']);
            $table->date('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['publishingid', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_documents');
    }
};
