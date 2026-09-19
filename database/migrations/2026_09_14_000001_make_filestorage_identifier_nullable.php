<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filestorage', function (Blueprint $table) {
            $table->text('identifier')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('filestorage', function (Blueprint $table) {
            $table->text('identifier')->nullable(false)->change();
        });
    }
};
