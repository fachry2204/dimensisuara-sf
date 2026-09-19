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
        // Add foreign keys to access_user_roles table
        Schema::table('access_user_roles', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('access_roles')
                ->onDelete('cascade');
        });

        // Add foreign keys to access_role_permissions table
        Schema::table('access_role_permissions', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('access_roles')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('access_permissions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_user_roles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);
        });

        Schema::table('access_role_permissions', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['permission_id']);
        });
    }
};
