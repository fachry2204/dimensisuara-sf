<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix foreign key constraints for proper cascade behavior:
     * - User deletion → CASCADE delete all their data
     * - Publishing deletion → CASCADE delete signees, PRESERVE documents
     * - Songwriter deletion → CASCADE delete signees (if no pending publishings)
     */
    public function up(): void
    {
        // Fix publishing_signees table foreign keys
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropForeign(['publishingid']);

            // Add publishingid FK with CASCADE (delete signees when publishing deleted)
            $table->foreign('publishingid')
                ->references('id')
                ->on('publishings')
                ->onDelete('cascade');

            // Add songwriterid FK with CASCADE (delete signees when songwriter deleted)
            $table->foreign('songwriterid')
                ->references('id')
                ->on('publishing_songwriters')
                ->onDelete('cascade');

            // Add profileid FK with CASCADE (delete user's data completely)
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('cascade');
        });

        // Fix publishings table foreign keys
        Schema::table('publishings', function (Blueprint $table) {
            // Drop existing profileid FK and recreate with CASCADE
            $table->dropForeign(['profileid']);
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('cascade');

            // Add rejectorid FK with SET NULL (can reference deleted signee)
            $table->foreign('rejectorid')
                ->references('id')
                ->on('publishing_signees')
                ->onDelete('set null');
        });

        // Fix publishing_songwriters table foreign key
        Schema::table('publishing_songwriters', function (Blueprint $table) {
            // Drop existing profileid FK and recreate with CASCADE
            $table->dropForeign(['profileid']);
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('cascade');
        });

        // Fix contracts table foreign key
        Schema::table('contracts', function (Blueprint $table) {
            // Drop existing profileid FK and recreate with CASCADE
            $table->dropForeign(['profileid']);
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restore original foreign key constraints
     */
    public function down(): void
    {
        // Restore publishing_signees table
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropForeign(['publishingid']);
            $table->dropForeign(['songwriterid']);
            $table->dropForeign(['profileid']);
        });

        // Restore publishings table
        Schema::table('publishings', function (Blueprint $table) {
            $table->dropForeign(['rejectorid']);
            $table->dropForeign(['profileid']);

            // Restore original profileid FK with SET NULL
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('set null');
        });

        // Restore publishing_songwriters table
        Schema::table('publishing_songwriters', function (Blueprint $table) {
            $table->dropForeign(['profileid']);

            // Restore original profileid FK with SET NULL
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('set null');
        });

        // Restore contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['profileid']);

            // Restore original profileid FK with SET NULL
            $table->foreign('profileid')
                ->references('id')
                ->on('user_profiles')
                ->onDelete('set null');
        });
    }
};
