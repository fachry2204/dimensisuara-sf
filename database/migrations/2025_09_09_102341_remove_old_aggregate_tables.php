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
        Schema::dropIfExists('report_revenue_aggregates');
        Schema::dropIfExists('report_view_aggregates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('report_revenue_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');

            $table->bigInteger('trackid')->unsigned()->nullable();
            $table->string('track_title', 100)->nullable();
            $table->string('release_title', 100)->nullable();
            $table->date('reporting_month');
            $table->integer('rupiah')->default(0);
            $table->decimal('rupiah_exact', 18, 4);
            $table->decimal('aggregator_rupiah', 18, 4);
            $table->decimal('aggregator_rupiah_exact', 18, 4);
            $table->decimal('client_rupiah', 18, 4);
            $table->decimal('client_rupiah_exact', 18, 4);
            $table->timestamps();

            $table->unique(['trackid', 'reporting_month']); // Or adjust based on your aggregation logic
        });

        Schema::create('report_view_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profileid')->nullable()->constrained(
                table: 'user_profiles',
                column: 'id'
            )->onDelete('set null');

            $table->bigInteger('trackid')->unsigned()->nullable();
            $table->string('track_title', 100)->nullable();
            $table->string('release_title', 100)->nullable();
            $table->date('sales_month');
            $table->bigInteger('views')->unsigned()->default(0);

            $table->timestamps();

            $table->unique(['trackid', 'sales_month']); // Or adjust based on your aggregation logic
        });
    }
};
