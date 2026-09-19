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
        Schema::create('report_imports', function (Blueprint $table) {
            $table->id();
            
            $table->bigInteger('userid')->unsigned();

            $table->string('original_filename');
            $table->enum('status', ['pending_import', 'pending_review', 'completed'])->default('pending_import');
            $table->bigInteger('total_rows')->unsigned()->default(0);
            $table->bigInteger('matched_rows')->unsigned()->default(0);
            $table->bigInteger('unmatched_rows')->unsigned()->default(0);
            $table->timestamp('completed_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_imports');
    }
};
