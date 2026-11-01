<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Targeted deployments may have normalized storage before installing notifications.
        if (!Schema::hasTable('status_notification_deliveries')) {
            $create = require __DIR__.'/2026_10_31_120000_create_status_notification_deliveries.php';
            $create->up();
        }

        // Reuse the verified backfill, FK repair, and legacy-column removal path.
        $normalize = require __DIR__.'/2026_11_01_130000_normalize_application_json_fields.php';
        $normalize->up();
    }

    public function down(): void
    {
        // This repairs missing storage; rollback must retain notification history.
    }
};
