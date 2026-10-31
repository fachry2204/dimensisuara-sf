<?php

return [
    // Legacy source used only by the one-time database migration, never by runtime settings.
    'path' => storage_path('app/private/system-settings.enc'),
    'company_signatures_path' => storage_path('app/private/company-signatures'),
    'whatsapp' => [
        'url' => 'https://api.fonnte.com',
        'token' => env('FONNTE_TOKEN'),
        'number' => env('WHATSAPP_API_NUMBER'),
    ],
];
