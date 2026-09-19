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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('profileid')->unsigned();
            $table->date('date')->nullable();
            $table->string('type');
            $table->morphs('recipient');
            $table->enum('quarter', [1,2,3,4])->nullable();
            $table->smallInteger('year')->unsigned()->nullable();
            $table->decimal('gross', 20, 2);
            $table->decimal('aggregator', 20, 2);
            $table->decimal('subtotal', 20, 2);
            $table->decimal('tax', 20, 2);
            $table->decimal('tax2', 20, 2);
            $table->decimal('total', 20, 2);
            $table->decimal('taxrate', 6, 3);
            $table->decimal('taxrate2', 6, 3);
            $table->enum('status', ['carried_over','pending','transferred','cancelled']);
            $table->boolean('skip_carryover_check');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
