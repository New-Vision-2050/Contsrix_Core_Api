<?php

return [

    'enabled' => env('API_REQUEST_LOG_ENABLED', true),

    'max_payload_bytes'  => (int) env('API_REQUEST_LOG_MAX_PAYLOAD_BYTES', 65536),
    'max_response_bytes' => (int) env('API_REQUEST_LOG_MAX_RESPONSE_BYTES', 65536),

    'excluded_paths' => [
        'up',
        'api/health',
        'telescope/*',
    ],

    'sensitive_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'otp',
        'pin',
    ],

    'sensitive_headers' => [
        'authorization',
        'cookie',
        'x-api-key',
    ],

];
