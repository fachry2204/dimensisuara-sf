<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_records', function (Blueprint $table) {
            $table->id();
            $table->string('source', 80);
            $table->string('entity', 60);
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('status', 60);
            $table->longText('payload'); // Encrypted original row; never exposed in public endpoints.
            $table->string('source_sha256', 64);
            $table->timestamps();
            $table->unique(['source', 'entity', 'legacy_id'], 'legacy_import_identity');
        });
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->decimal('aggregator_rate', 5, 2)->default(70)->change();
            $table->decimal('publishing_rate', 5, 2)->default(70)->change();
        });
    }

    public function down(): void
    {
        // Keep decimal rates: narrowing back to integers would lose imported percentages.
        Schema::dropIfExists('legacy_import_records');
    }
};
