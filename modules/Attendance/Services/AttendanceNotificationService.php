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

    private function resolveConstraint(Attendance $attendance): ?AttendanceConstraint
    {
        $user = $attendance->user;
        if (!$user) {
            return null;
        }

        return $user->professionalData?->attendanceConstraint;
    }
}
