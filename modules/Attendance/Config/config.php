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

    // Clocked-in employees must keep sending GPS. After this many minutes with no
    // ping (clock-in counts as the first heartbeat), auto clock-out.
    'stale_location_minutes' => (int) env('ATTENDANCE_STALE_LOCATION_MINUTES', 45),

    // Location auto clock-out is off: staying outside a geofence, or sending no
    // GPS for 45 minutes, must not close the shift. Clock-in still requires GPS
    // inside an allowed location. Scheduled auto_max_ot / next-shift close is separate.
    'out_zone_auto_clock_out_enabled' => (bool) env('ATTENDANCE_OUT_ZONE_AUTO_CLOCK_OUT', false),
    'stale_location_auto_clock_out_enabled' => (bool) env('ATTENDANCE_STALE_LOCATION_AUTO_CLOCK_OUT', false),

    // When track-location is outside allowed sites, ask the employee to open the
    // app and POST /attendance/out-zone-warning/confirm-location. Sends this many
    // FCM pushes once per warning. Does not clock them out.
    'out_zone_confirm_enabled' => (bool) env('ATTENDANCE_OUT_ZONE_CONFIRM', true),
    'out_zone_confirm_notification_count' => (int) env('ATTENDANCE_OUT_ZONE_CONFIRM_NOTIFICATION_COUNT', 3),
];
