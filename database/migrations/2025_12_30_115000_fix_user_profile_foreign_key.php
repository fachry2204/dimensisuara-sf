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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Drop existing RESTRICT constraint
            $table->dropForeign(['userid']);

            // Add CASCADE constraint
            $table->foreign('userid')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Restore RESTRICT constraint
            $table->dropForeign(['userid']);

            $table->foreign('userid')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }
};
