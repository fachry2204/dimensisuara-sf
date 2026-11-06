<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('drive_transfers')) return;
        if (!Schema::hasColumn('drive_transfers','layout_version')) Schema::table('drive_transfers',fn (Blueprint $t) => $t->unsignedInteger('layout_version')->default(0));
        if (!Schema::hasColumn('drive_transfers','previous_remote_identifier')) Schema::table('drive_transfers',fn (Blueprint $t) => $t->text('previous_remote_identifier')->nullable());
    }
    public function down(): void
    {
        foreach (['layout_version','previous_remote_identifier'] as $field) if (Schema::hasColumn('drive_transfers',$field)) Schema::table('drive_transfers',fn (Blueprint $t) => $t->dropColumn($field));
    }
};
