<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\OutOfZoneClockOutExemption;
use Modules\Attendance\Contracts\TemporaryLocationProvider;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;

/**
 * Holds off location (out-of-zone) auto clock-out when the employee has field
 * work that day: an accepted employee task, or a sent/accepted project
 * notification. Same write-side idea as
 * {@see AbsenceMarkingService} consulting {@see TemporaryLocationProvider::isEngagedElsewhere()},
 * but keyed to the calendar day rather than the live task geofence — a task can
 * be accepted (or a notification sent) before it publishes coordinates.
 */
final class FieldWorkOutOfZoneExemption implements OutOfZoneClockOutExemption
{
    public function appliesTo(Attendance $attendance): bool
    {
        $userId = $attendance->user_id ?? null;
        if ($userId === null || $userId === '') {
            return false;
        }

        $user = $attendance->relationLoaded('user')
            ? $attendance->user
            : User::query()->find($userId);

        if (! $user) {
            return false;
        }

        $date = $this->workDate($attendance);

        try {
            $providers = app()->tagged('attendance.temporary_location_providers');
        } catch (\Throwable) {
            return false;
        }

        foreach ($providers as $provider) {
            if ($provider instanceof TemporaryLocationProvider
                && $provider->hasFieldAssignmentOn($user, $date)) {
                return true;
            }
        }

        return false;
    }

    private function workDate(Attendance $attendance): string
    {
        if ($attendance->business_date) {
            return Carbon::parse((string) $attendance->business_date)->toDateString();
        }

        if ($attendance->start_time) {
            return Carbon::parse((string) $attendance->start_time)->toDateString();
        }

        $timezone = is_string($attendance->timezone) && $attendance->timezone !== ''
            ? $attendance->timezone
            : (string) config('app.timezone');

        return Carbon::now($timezone)->toDateString();
    }
}
