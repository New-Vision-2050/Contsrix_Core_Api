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

    // Rule-based auto clock-out (replaces the parked auto_close_grace_enabled).
    // When the employee never punches out, the shift is auto-closed at
    // expected_clock_out + extension_minutes (the constraint's extension rule) and
    // the stored clock_out_time is expected_clock_out minus a penalty of
    // auto_clock_out_penalty_percent % of the required shift minutes.
    // Example: 9h shift ending 20:00, extension 120, penalty 25% → fires 22:00,
    // stores 17:45 (20:00 − 2h15m), so the day pays 6.75h.
    'rule_based_auto_clock_out_enabled' => (bool) env('ATTENDANCE_RULE_BASED_AUTO_CLOCK_OUT', true),
    'auto_clock_out_penalty_percent'    => (int) env('ATTENDANCE_AUTO_CLOCK_OUT_PENALTY_PERCENT', 25),

    // Manual clock-out is never penalised, but the stored time is capped by the
    // rules snapshotted on the row: at the shift end when post-shift overtime is
    // not allowed (is_after_finish_working_hours / is_overtime_after_extension_hours_shift
    // off, or max_over_time = 0), otherwise at shift end + max_over_time.
    'manual_clock_out_cap_enabled' => (bool) env('ATTENDANCE_MANUAL_CLOCK_OUT_CAP', true),
];
