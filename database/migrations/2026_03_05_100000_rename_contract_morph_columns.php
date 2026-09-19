<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Drop old index
            $table->dropIndex('contracts_request_type_request_id_index');

            // Rename morph columns
            $table->renameColumn('request_type', 'owner_type');
            $table->renameColumn('request_id', 'owner_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            // Add new index (separate call due to doctrine limitations)
            $table->index(['owner_type', 'owner_id'], 'contracts_owner_type_owner_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_owner_type_owner_id_index');

            $table->renameColumn('owner_type', 'request_type');
            $table->renameColumn('owner_id', 'request_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['request_type', 'request_id'], 'contracts_request_type_request_id_index');
        });
    }
};
