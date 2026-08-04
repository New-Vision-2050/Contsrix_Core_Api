<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\Carbon;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\UserAttendanceService;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Ramsey\Uuid\Uuid;

/**
 * Enforces Attendance Rules V2 windows on employee self-serve task creation.
 *
 * Same boundaries as clock-in:
 *  - Too early (before earliest_clock_in / early window)
 *  - Past can_clock_in_before with no clock-in → absent / deadline passed
 *  - Outside the work/extension window
 *  - Must already be clocked in (active attendance)
 *
 * Skipped for dashboard/admin forms (e.g. createProjectNotificationTask).
 */
final class EmployeeTaskAttendanceWindowGuard
{
    public function __construct(
        private readonly UserAttendanceService $userAttendanceService,
        private readonly AttendanceService $attendanceService,
    ) {}

    /**
     * @throws EmployeeTaskException
     */
    public function assertCanCreateTask(string $userId): void
    {
        $timezone = $this->resolveTimezone();
        $now = Carbon::now($timezone);

        $constraints = $this->userAttendanceService->getUserConstraints($userId, $now->toDateString());
        $workRules = $constraints['work_rules'] ?? [];
        $dayStatus = $workRules['day_status'] ?? null;

        if ($dayStatus !== null && $dayStatus !== 'work_day') {
            throw EmployeeTaskException::notAllowedOnHolidays();
        }

        $periods = $workRules['all_work_periods'] ?? [];
        if ($periods === []) {
            throw EmployeeTaskException::outsideShiftTimeWindow(
                'No active work period is available to create a task.'
            );
        }

        $rejection = $this->evaluatePeriods($periods, $now, $timezone);

        if ($rejection !== null) {
            throw $this->exceptionForRejection($rejection);
        }

        $current = $this->attendanceService->getCurrentAttendance(Uuid::fromString($userId), false);
        if ($current === null || ! $current->isActive() || empty($current->clock_in_time)) {
            throw EmployeeTaskException::employeeHasNoAttendance();
        }
    }

    /**
     * @param  list<array<string, mixed>> $periods
     * @return array{type: string, from?: string, until?: string, deadline?: string}|null
     *         null = at least one period currently allows task creation
     */
    private function evaluatePeriods(array $periods, Carbon $now, string $timezone): ?array
    {
        $bestRejection = null;

        foreach ($periods as $period) {
            if (! is_array($period)) {
                continue;
            }

            // Period already marked absent (deadline passed, never clocked in).
            if (($period['is_absent'] ?? false) === true) {
                $deadline = $this->formatHm($period['absent_at'] ?? $period['can_clock_in_until'] ?? null, $timezone);
                $bestRejection = [
                    'type' => 'clock_in_deadline_passed',
                    'deadline' => $deadline,
                ];
                continue;
            }

            [$from, $until] = $this->resolveWindowBounds($period, $now, $timezone);
            if ($from === null || $until === null) {
                continue;
            }

            if ($now->lt($from)) {
                if ($bestRejection === null) {
                    $bestRejection = [
                        'type' => 'too_early',
                        'from' => $from->format('H:i'),
                    ];
                }
                continue;
            }

            if ($now->gt($until)) {
                $hasClockIn = $this->periodHasClockIn($period);
                $deadlineIso = $period['absent_at'] ?? $period['can_clock_in_until'] ?? null;
                $deadline = $deadlineIso !== null
                    ? Carbon::parse((string) $deadlineIso, $timezone)
                    : null;

                $isDeadline = ! $hasClockIn
                    && $deadline !== null
                    && $now->gt($deadline);

                $bestRejection = [
                    'type' => $isDeadline ? 'clock_in_deadline_passed' : 'window_closed',
                    'until' => $until->format('H:i'),
                    'deadline' => $deadline?->format('H:i'),
                ];
                continue;
            }

            // Inside the attendance work window for this period.
            return null;
        }

        return $bestRejection ?? ['type' => 'window_closed'];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveWindowBounds(array $period, Carbon $now, string $timezone): array
    {
        // Prefer V2 fields emitted by UserAttendanceService (same as user-constraint/today).
        if (! empty($period['can_clock_in_from']) && ! empty($period['can_clock_out_until'])) {
            return [
                Carbon::parse((string) $period['can_clock_in_from'], $timezone),
                Carbon::parse((string) $period['can_clock_out_until'], $timezone),
            ];
        }

        $date = $period['date'] ?? $now->toDateString();
        if ($date instanceof Carbon) {
            $date = $date->format('Y-m-d');
        }

        $startTime = $period['start_time'] ?? null;
        $endTime = $period['end_time'] ?? null;
        if (! is_string($startTime) || ! is_string($endTime) || $startTime === '' || $endTime === '') {
            return [null, null];
        }

        $start = Carbon::parse($date.' '.$startTime, $timezone);
        $end = Carbon::parse($date.' '.$endTime, $timezone);
        if (! empty($period['extends_to_next_day']) || $end->lte($start)) {
            $end->addDay();
        }

        $earlyMinutes = (int) (
            $period['early_clock_in_rules']['early_period']
            ?? $period['early_clock_in_rules']['allowed_minutes_before']
            ?? 0
        );
        $extensionMinutes = (int) ($period['extension_minutes'] ?? $period['extension_hours_shift'] ?? 0);

        return [
            $start->copy()->subMinutes(max(0, $earlyMinutes)),
            $end->copy()->addMinutes(max(0, $extensionMinutes)),
        ];
    }

    private function periodHasClockIn(array $period): bool
    {
        if (empty($period['attendance']) || ! is_array($period['attendance'])) {
            return false;
        }

        foreach ($period['attendance'] as $att) {
            if (! empty($att['clock_in_time'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{type: string, from?: string, until?: string, deadline?: string} $rejection
     */
    private function exceptionForRejection(array $rejection): EmployeeTaskException
    {
        return match ($rejection['type']) {
            'too_early' => EmployeeTaskException::outsideShiftTimeWindow(
                isset($rejection['from'])
                    ? 'Too early to create a task. You can create tasks from '.$rejection['from'].'.'
                    : 'Too early to create a task.'
            ),
            'clock_in_deadline_passed' => EmployeeTaskException::clockInDeadlinePassed(
                $rejection['deadline'] ?? null
            ),
            default => EmployeeTaskException::outsideShiftTimeWindow(
                isset($rejection['until'])
                    ? 'The shift window has closed (until '.$rejection['until'].'). You cannot create a task now.'
                    : 'You are outside your work shift window. You cannot create a task now.'
            ),
        };
    }

    private function formatHm(mixed $iso, string $timezone): ?string
    {
        if ($iso === null || $iso === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $iso, $timezone)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTimezone(): string
    {
        try {
            $tz = getTimeZoneBranchByRequest();
            if (is_string($tz) && $tz !== '') {
                return $tz;
            }
        } catch (\Throwable) {
        }

        return 'Asia/Riyadh';
    }
}
