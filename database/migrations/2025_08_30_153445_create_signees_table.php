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
        Schema::create('publishing_signees', function (Blueprint $table) {
            $table->id();
            $table->uuid('token');
            $table->foreignId('publishingid')->constrained(
                table: 'publishings',
                column: 'id'
            );
            $table->bigInteger('profileid')->unsigned()->nullable();
            $table->bigInteger('songwriterid')->unsigned();
            $table->enum('role', ['author', 'composer', 'author_composer']);
            $table->decimal('share', 5, 2);
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->dateTime('expires_at');
            $table->bigInteger('contractid')->unsigned()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publishing_signees');
    }
};
