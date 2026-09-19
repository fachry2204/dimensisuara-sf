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
        Schema::create('report_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_import_id')->constrained(
                table: 'report_imports',
                column: 'id'
            )->onDelete('cascade');

            // Raw identifiers from Excel
            $table->date('reporting_month');
            $table->date('sales_month');
            $table->string('platform', 100);
            $table->string('region', 100)->nullable();
            $table->string('label_name', 100);
            $table->string('artist_name', 100);
            $table->string('release_title', 255)->nullable();
            $table->string('track_title', 255)->nullable();
            $table->string('upc', 100);
            $table->string('isrc', 100);
            $table->string('release_catalog', 255)->nullable();
            $table->string('subscription_type', 100)->nullable();
            $table->string('release_type', 100)->nullable();
            $table->string('sales_type', 100)->nullable();
            $table->unsignedInteger('quantity');
            $table->string('currency_code', 5);
            $table->decimal('net_revenue', 20, 12);
            $table->decimal('rate', 10, 6)->nullable();
            $table->unsignedInteger('rupiah');

            // Matched entities (nullable in case unmatched)
            $table->bigInteger('releaseid')->unsigned()->nullable();
            $table->bigInteger('trackid')->unsigned()->nullable();
            $table->bigInteger('profileid')->unsigned()->nullable();

            // Status tracking
            $table->enum('status', ['pending', 'match', 'missmatch', 'finalized'])->default('pending');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_rows');
    }
};
