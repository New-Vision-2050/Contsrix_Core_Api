<?php

return [
    'name' => 'Attendance',

    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Attendance module
    |
    */

    'constraints' => [
        'max_violations_per_day' => 10,
        'auto_resolve_minor_violations' => false,
        'notification_channels' => ['email', 'database'],
    ],

    'working_hours' => [
        'default_start_time' => '09:00',
        'default_end_time' => '17:00',
        'break_duration_minutes' => 60,
        'overtime_threshold_hours' => 8,
    ],

    'location' => [
        'allowed_radius_meters' => 100,
        'require_location_validation' => true,
    ],

];
