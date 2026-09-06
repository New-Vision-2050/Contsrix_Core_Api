<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\NotificationSettings\Services\FirebaseNotificationService;

class AttendanceNotificationService
{
    public function notifyLateArrival(Attendance $attendance): void
    {
        $constraint = $this->resolveConstraint($attendance);
        if (!$constraint) {
            return;
        }

        $settings = $constraint->notification_settings ?? [];
        if (empty($settings['notify_late_arrival'])) {
            return;
        }

        $user = $attendance->user;
        if (!$user?->fcm_token) {
            return;
        }

        FirebaseNotificationService::send(
            $user->fcm_token,
            __('attendance.notifications.late_arrival_title'),
            __('attendance.notifications.late_arrival_body', [
                'minutes' => $attendance->late_minutes ?? 0,
            ]),
            [
                'type'          => 'attendance_late_arrival',
                'attendance_id' => (string) $attendance->id,
                'late_minutes'  => (string) ($attendance->late_minutes ?? 0),
            ]
        );

        Log::info('Late arrival notification sent', [
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'late_minutes'  => $attendance->late_minutes,
        ]);
    }

    public function notifyEarlyDeparture(Attendance $attendance): void
    {
        $constraint = $this->resolveConstraint($attendance);
        if (!$constraint) {
            return;
        }

        $settings = $constraint->notification_settings ?? [];
        if (empty($settings['notify_early_departure'])) {
            return;
        }

        $user = $attendance->user;
        if (!$user?->fcm_token) {
            return;
        }

        FirebaseNotificationService::send(
            $user->fcm_token,
            __('attendance.notifications.early_departure_title'),
            __('attendance.notifications.early_departure_body', [
                'minutes' => $attendance->early_departure_minutes ?? 0,
            ]),
            [
                'type'                    => 'attendance_early_departure',
                'attendance_id'           => (string) $attendance->id,
                'early_departure_minutes' => (string) ($attendance->early_departure_minutes ?? 0),
            ]
        );

        Log::info('Early departure notification sent', [
            'user_id'                 => $user->id,
            'attendance_id'           => $attendance->id,
            'early_departure_minutes' => $attendance->early_departure_minutes,
        ]);
    }

    public function notifyUnexcusedAbsence(Attendance $attendance): void
    {
        $constraint = $this->resolveConstraint($attendance);
        if (!$constraint) {
            return;
        }

        $settings = $constraint->notification_settings ?? [];
        if (empty($settings['notify_unexcused_absence'])) {
            return;
        }

        $user = $attendance->user;
        if (!$user?->fcm_token) {
            return;
        }

        FirebaseNotificationService::send(
            $user->fcm_token,
            __('attendance.notifications.unexcused_absence_title'),
            __('attendance.notifications.unexcused_absence_body'),
            [
                'type'          => 'attendance_unexcused_absence',
                'attendance_id' => (string) $attendance->id,
            ]
        );

        Log::info('Unexcused absence notification sent', [
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * Ask the employee to open the app and confirm GPS. Sent three times so
     * the phone surfaces it even if the first push is dismissed.
     */
    public function notifyConfirmLocation(Attendance $attendance, int $times = 3): int
    {
        $user = $attendance->relationLoaded('user') ? $attendance->user : $attendance->user()->first();
        if (! $user?->fcm_token) {
            Log::warning('Confirm-location notification skipped: user has no FCM token', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
            ]);

            return 0;
        }

        $times = max(1, $times);
        $sent = 0;
        $title = __('attendance.notifications.confirm_location_title');
        $body = __('attendance.notifications.confirm_location_body');

        for ($sequence = 1; $sequence <= $times; $sequence++) {
            $ok = FirebaseNotificationService::send(
                $user->fcm_token,
                $title,
                $body,
                [
                    'type' => 'out_zone_confirm_location',
                    'action' => 'confirm_location',
                    'attendance_id' => (string) $attendance->id,
                    'sequence' => (string) $sequence,
                ]
            );

            if ($ok) {
                $sent++;
            }
        }

        Log::info('Confirm-location notifications sent', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'sent' => $sent,
            'requested' => $times,
        ]);

        return $sent;
    }

    private function resolveConstraint(Attendance $attendance): ?AttendanceConstraint
    {
        $user = $attendance->user;
        if (!$user) {
            return null;
        }

        return $user->professionalData?->attendanceConstraint;
    }
}
