<?php

declare(strict_types=1);

return [
    'client_id' => env('INTER_CLIENT_ID', ''),
    'client_secret' => env('INTER_CLIENT_SECRET', ''),
    'certificate' => env('INTER_CERTIFICATE', ''),
    'private_key' => env('INTER_PRIVATE_KEY', ''),
    'environment' => env('INTER_ENVIRONMENT', 'sandbox'),
    'scopes' => env('INTER_SCOPES', ''),
    'timeout' => (int) env('INTER_TIMEOUT', 30),
    'retry' => (int) env('INTER_RETRY', 3),
    'user_agent' => env('INTER_USER_AGENT', 'laravel-inter'),
    'cache_prefix' => env('INTER_CACHE_PREFIX', 'inter_token'),
    'webhook_token' => env('INTER_WEBHOOK_TOKEN', ''),
];
