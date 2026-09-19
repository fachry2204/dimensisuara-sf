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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');
            $table->tinyInteger('quarter')->unsigned();
            $table->smallInteger('year')->unsigned();
            $table->decimal('subtotal', 18, 4);
            $table->decimal('carryover', 18, 4);
            $table->decimal('tax', 18, 4);
            $table->decimal('total', 18, 4);
            $table->enum('status', ['pending','completed','carried_over'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
