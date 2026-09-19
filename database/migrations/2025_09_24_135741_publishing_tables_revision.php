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
            $table->dropColumn('expires_at');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired', 'cancelled'])->default('pending')->change();
        });

        Schema::table('publishings', function (Blueprint $table) {
            $table->bigInteger('rejectorid')->unsigned()->nullable()->after('status');
            $table->dateTime('finished_at')->nullable()->after('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishing_signees', function (Blueprint $table) {
            $table->dateTime('expires_at')->after('status');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->change();
        });

        Schema::table('publishings', function (Blueprint $table) {
            $table->dropColumn('rejectorid');
            $table->dropColumn('finished_at');
        });
    }
};
