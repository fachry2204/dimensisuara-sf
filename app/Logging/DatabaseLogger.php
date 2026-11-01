<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DatabaseLogger extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        // Installation and isolated tests can run before the activity table exists.
        if (!\Illuminate\Support\Facades\Schema::hasTable('logs')) return;
        \App\Database\RelationalFields::insert('logs', [
            'level'         => $record->level->getName(),
            'message'       => $record->message,
            'context'       => json_encode($record->context),
            'userid'        => auth()->user() instanceof \App\Models\User\Admin ? null : auth()->id(),
            'admin_user_id' => auth()->user() instanceof \App\Models\User\Admin ? auth()->id() : null,
            'impersonate'   => session('impersonate') ?? null,
            'ip'            => request()->ip(),
            'url'           => request()->fullUrl(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
