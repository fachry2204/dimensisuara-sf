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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_view_aggregates');
    }
};
