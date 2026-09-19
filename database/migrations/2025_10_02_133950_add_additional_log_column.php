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
        Schema::table('logs', function (Blueprint $table) {
            $table->bigInteger('impersonate')->nullable()->after('userid');
            $table->string('ip', 45)->nullable()->after('impersonate');
            $table->string('url')->nullable()->after('ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn('impersonate');
            $table->dropColumn('ip');
            $table->dropColumn('url');
        });
    }
};
