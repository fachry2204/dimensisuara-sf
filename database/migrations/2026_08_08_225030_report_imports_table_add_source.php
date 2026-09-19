<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_imports', function (Blueprint $table) {
            $table->enum('source', ['believe', 'soundon'])->nullable()->after('userid');
        });

        DB::table('report_imports')->update([
            'source' => 'believe',
        ]);

        Schema::table('report_imports', function (Blueprint $table) {
            $table->enum('source', ['believe', 'soundon'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_imports', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
