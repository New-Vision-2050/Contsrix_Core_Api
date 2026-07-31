<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable pCloud archive sync
    |--------------------------------------------------------------------------
    |
    | When enabled, Archive Library media uploads are mirrored to pCloud.
    |
    */
    'enabled' => filter_var(
        env('PCLOUD_ENABLED', filled(env('PCLOUD_EMAIL')) && filled(env('PCLOUD_PASSWORD'))),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Credentials (email / password digest auth)
    |--------------------------------------------------------------------------
    |
    | Business accounts often do not return an auth token from getauth=1.
    | The client uses getdigest + passworddigest on every request:
    | https://docs.pcloud.com/methods/intro/authentication.html
    |
    */
    'email' => env('PCLOUD_EMAIL'),
    'password' => env('PCLOUD_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | API hosts
    |--------------------------------------------------------------------------
    |
    | locationid 1 → US (api.pcloud.com), locationid 2 → EU (eapi.pcloud.com)
    |
    */
    'api_hosts' => [
        1 => env('PCLOUD_API_HOST_US', 'https://api.pcloud.com'),
        2 => env('PCLOUD_API_HOST_EU', 'https://eapi.pcloud.com'),
    ],

    'default_api_host' => env('PCLOUD_API_HOST', 'https://api.pcloud.com'),

    /*
    |--------------------------------------------------------------------------
    | Remote folder layout
    |--------------------------------------------------------------------------
    |
    | Files land under: /{root_folder}/{company_or_tenant}/{archive_path}/
    |
    */
    'root_folder' => env('PCLOUD_ROOT_FOLDER', 'Constrix Archive'),

    /*
    |--------------------------------------------------------------------------
    | Dispatch mode
    |--------------------------------------------------------------------------
    |
    | sync  = upload to pCloud in the same request (reliable; no queue worker)
    | queue = push SyncMediaToPCloudJob to the queue (needs queue:work)
    |
    | Prefer sync locally and when using Octane — afterResponse can be dropped.
    |
    */
    'dispatch' => env('PCLOUD_DISPATCH', 'sync'),

    'auth_cache_ttl' => (int) env('PCLOUD_AUTH_CACHE_TTL', 3500),

    'timeout' => (int) env('PCLOUD_TIMEOUT', 120),
];
