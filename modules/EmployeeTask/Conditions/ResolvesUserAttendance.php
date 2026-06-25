<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Carbon\Carbon;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\User\Models\User;

/**
 * Shared logic for evaluators that need to resolve the target user's branch
 * timezone and fetch attendance work rules.
 */
trait ResolvesUserAttendance
{
    private function loadUserWithBranchTimezone(string $userId): ?User
    {
        return User::query()
            ->with([
                'professionalData.attendanceConstraint',
                'userProfessionalData.branch',
                'userProfessionalData.branch.address.country.timezones',
                'userProfessionalData.department',
            ])
            ->find($userId);
    }

    private function resolveBranchTimezone(?User $user): string
    {
        if ($user !== null) {
            $timezones = $user->userProfessionalData?->branch?->address?->country?->timezones;
            if (is_array($timezones) && isset($timezones[0]['zoneName']) && is_string($timezones[0]['zoneName'])) {
                return $timezones[0]['zoneName'];
            }
        }

        return getTimeZoneBranchByRequest() ?? config('app.timezone');
    }

    private function isCurrentlyInAnyWorkPeriod(array $periods): bool
    {
        foreach ($periods as $period) {
            $start = $period['period_start_time_carbon'] ?? null;
            $end   = $period['period_end_time_carbon'] ?? null;

            if ($start instanceof Carbon && $end instanceof Carbon) {
                $now = Carbon::now($start->getTimezone());
                if ($now->between($start, $end, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
