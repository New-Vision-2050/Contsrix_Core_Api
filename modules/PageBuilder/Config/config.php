<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | List of tables to exclude from the schema listing
    |
    */
    'excluded_tables' => [
        'migrations',
        'failed_jobs',
        'password_resets',
        'personal_access_tokens',
        'sessions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching schema information
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
];