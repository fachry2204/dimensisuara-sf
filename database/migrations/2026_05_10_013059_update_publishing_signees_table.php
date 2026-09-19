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
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->boolean('accept_terms')->nullable()->after('publishers');
            $table->timestamp('accepted_at')->nullable()->after('accept_terms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dropColumn('accept_terms');
            $table->dropColumn('accepted_at');
        });
    }
};
