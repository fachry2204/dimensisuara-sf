<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filestorage', function (Blueprint $table) {
            $table->unsignedSmallInteger('backup_attempts')->default(0)->after('backed_up_at');
            $table->text('backup_error')->nullable()->after('backup_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('filestorage', function (Blueprint $table) {
            $table->dropColumn(['backup_attempts', 'backup_error']);
        });
    }
};
