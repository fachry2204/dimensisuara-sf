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
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlementid')->references('id')->on('settlements')->onDelete('cascade');
            $table->bigInteger('profileid')->unsigned();
            $table->string('type');
            $table->bigInteger('relid')->unsigned()->nullable();
            $table->text('description');
            $table->decimal('gross', 20, 2);
            $table->decimal('aggregator', 20, 2);
            $table->decimal('amount', 20, 2);
            $table->boolean('taxed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
    }
};
