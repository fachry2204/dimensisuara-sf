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
        // Make songwriterid nullable first to allow SET NULL constraint
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->unsignedBigInteger('songwriterid')->nullable()->change();
        });

        // Drop existing CASCADE constraint
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropForeign(['songwriterid']);
        });

        // Add new SET NULL constraint
        // This preserves signee records and approval history when songwriter is deleted
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->foreign('songwriterid')
                ->references('id')
                ->on('publishing_songwriters')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropForeign(['songwriterid']);
        });

        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->foreign('songwriterid')
                ->references('id')
                ->on('publishing_songwriters')
                ->onDelete('cascade');
        });
    }
};
