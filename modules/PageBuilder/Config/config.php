<?php

return [
    'name' => 'PageBuilder',
    'excluded_tables' => [
        'migrations',
        'failed_jobs',
        'password_resets',
        'personal_access_tokens',
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
];
