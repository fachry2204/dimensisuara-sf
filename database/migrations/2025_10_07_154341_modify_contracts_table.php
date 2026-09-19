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
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('request_type')->nullable()->after('number');
            $table->bigInteger('request_id')->unsigned()->nullable()->after('request_type');

            $table->index(['request_type', 'request_id'], 'contracts_request_type_request_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropMorphs('request');
        });
    }
};
