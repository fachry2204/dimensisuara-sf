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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->text('googlefileid');
            $table->bigInteger('profileid')->unsigned()->nullable();
            $table->string('type')->nullable();
            $table->morphs('owner');
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
