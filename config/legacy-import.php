<?php

return [
    // Limit server file selection to the hosting account, not the entire machine.
    'allowed_root' => env('LEGACY_IMPORT_ALLOWED_ROOT', dirname(base_path())),
    'inbox' => storage_path('app/private/legacy-import/inbox'),
    'max_sql_kb' => 102400,
];
