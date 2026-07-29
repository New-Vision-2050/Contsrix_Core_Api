<?php

return [
    'name' => 'Attendance',

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

    /*
    |--------------------------------------------------------------------------
    | Rules V2
    |--------------------------------------------------------------------------
    |
    | overtime_policy: 'segmented' (V2, five zones + flags) or 'standard' (V1
    | rollback — surplus over scheduled length only).
    |
    | exclude_overtime_from_work_hours: when true, total_work_hours is net of
    | overtime (V2 breaking change, accepted). Set false together with the
    | 'standard' policy to fully restore V1 behaviour.
    |
    */
    'overtime_policy' => env('ATTENDANCE_OVERTIME_POLICY', 'segmented'),
    'exclude_overtime_from_work_hours' => env('ATTENDANCE_EXCLUDE_OT_FROM_WORK_HOURS', true),

    // Rollout phase 5: automatic absence marking at the can_clock_in_before deadline.
    // Disable to run the sweep in report-only mode before enabling per tenant.
    'absence_marking_enabled' => env('ATTENDANCE_ABSENCE_MARKING_ENABLED', true),
];
