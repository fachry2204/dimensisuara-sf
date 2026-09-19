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
        DB::table('logs')->insert([
            'level'         => $record->level->getName(),
            'message'       => $record->message,
            'context'       => json_encode($record->context),
            'userid'        => auth()->check() ? auth()->user()->id : null,
            'impersonate'   => session('impersonate') ?? null,
            'ip'            => request()->ip(),
            'url'           => request()->fullUrl(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}