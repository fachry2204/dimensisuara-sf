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
        Schema::create('publishings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');
            $table->string('title');
            $table->string('other_title')->nullable();
            $table->string('sample')->nullable();
            $table->boolean('synchronization_rights')->nullable();
            $table->boolean('mechanical_rights')->nullable();
            $table->boolean('performing_rights')->nullable();
            $table->boolean('print_rights')->nullable();
            $table->boolean('other_rights')->nullable();
            $table->string('performer');
            $table->integer('duration')->nullable();
            $table->string('genre');
            $table->string('language')->default('ID');
            $table->string('region');
            $table->string('iswc');
            $table->string('isrc');
            $table->text('note')->nullable();
            $table->date('due_date');
            $table->enum('status', ['pending','completed','rejected','expired','cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishings');
    }
};
