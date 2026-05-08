<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;

/**
 * Stateless — safe as a singleton under Octane/RoadRunner.
 * No mutable instance state; per-request memoization lives in getTimeZoneBranchByRequest().
 */
final class TimezoneResolver
{
    /** Use the frozen TZ stored on the attendance row; fall back to user branch. */
    public function forAttendance(Attendance $attendance): string
    {
        return $attendance->timezone ?: $this->forUser($attendance->user);
    }

    /** Derive TZ from pre-loaded user relations — no extra query. */
    public function forUser(?User $user): string
    {
        $timezones = $user?->userProfessionalData?->branch?->address?->country?->timezones;
        if (is_array($timezones) && isset($timezones[0]['zoneName']) && is_string($timezones[0]['zoneName'])) {
            return $timezones[0]['zoneName'];
        }

        return config('app.timezone') ?: 'Asia/Riyadh';
    }

    /**
     * Request-scoped resolution — memoized by once() (Octane-safe per-request cache).
     * Delegates to the existing helper to avoid duplicating the resolution chain.
     */
    public function forCurrentRequest(): string
    {
        return getTimeZoneBranchByRequest() ?: 'Asia/Riyadh';
    }
}
