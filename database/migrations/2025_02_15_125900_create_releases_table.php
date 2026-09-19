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
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');

            $table->string('old_album_id')->nullable();
            $table->string('old_track_id')->nullable();
            $table->string('type', 50)->default('album');
            $table->integer('tracknum')->unsigned()->default(0);
            $table->string('title', 100)->nullable();
            $table->string('titlelang', 10)->nullable();
            $table->json('primaryartists')->nullable();
            $table->string('recordlabel', 100)->nullable();
            $table->string('version', 100)->nullable()->default('original');;
            $table->string('genre', 100)->nullable();
            $table->string('subgenre', 100)->nullable();
            $table->text('releasedir')->nullable();
            $table->boolean('released')->default(false);
            $table->date('releaseddate')->nullable();
            $table->string('upc', 100)->nullable();
            $table->enum('status', ['draft', 'pending', 'released','rejected'])->default('draft');

            $table->text('reason')->nullable();
            $table->date('releaseon')->nullable();
            $table->date('finalreleaseddate')->nullable();
            $table->integer('tracks_uploaded')->unsigned()->default(0);
            $table->string('upload_status', 20)->default('normal');
            $table->date('delete_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
