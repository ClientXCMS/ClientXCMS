<?php

$appUrl = rtrim((string) config('app.url'), '/');
$extraOrigins = array_filter(array_map('trim', explode(',', (string) env('PASSKEYS_ALLOWED_ORIGINS', ''))));

return [
    'relying_party_id' => env('PASSKEYS_RELYING_PARTY_ID', parse_url($appUrl, PHP_URL_HOST)),
    'allowed_origins' => array_values(array_unique([$appUrl, ...$extraOrigins])),
    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),
    'timeout' => 60000,
    'guard' => 'web',
    'middleware' => ['web'],
    'management_middleware' => ['password.confirm'],
    'throttle' => 'throttle:6,1',
    'redirect' => '/client',
];
