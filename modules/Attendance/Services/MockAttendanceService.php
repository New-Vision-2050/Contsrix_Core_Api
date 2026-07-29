<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;
use Modules\Attendance\Domain\Time\ShiftWindow;
use Modules\Attendance\Domain\Time\ShiftWindowCalculator;
use Modules\Attendance\Domain\Time\ShiftWindowInput;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\Events\AttendanceClockedIn;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * A service dedicated to creating non-persisted (mock) Attendance models
 * for the purpose of pre-validation against constraints.
 */
class MockAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService,
        private UserAttendanceService $userAttendanceService,
        private ?ShiftWindowCalculator $windowCalculator = null
    ) {
        $this->windowCalculator ??= new ShiftWindowCalculator();
    }
    /**
     * Persist clock-in: create attendance record and dispatch event.
     */
    public function persistClockIn(ClockInDTO $clockInDTO, array $rawRequestData): Attendance
    {
        $attendance = $this->attendanceService->clockIn($clockInDTO);
        AttendanceClockedIn::dispatch($attendance->id);

        return $attendance->refresh();
    }

    /**
     * Validate clock-in against work periods and constraints (pre-persist).
     * Returns array of violations; empty array means validation passed.
     */
    public function validateClockIn(ClockInDTO $clockInDTO, array $rawRequestData): array
    {
        $user = auth()->user();
        // Get user's timezone from request
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');

        // Parse clock-in time (already in correct timezone from request)
        $clockInCarbon = Carbon::parse($clockInDTO->getClockInTime());

        // Get constraints for the clock-in date in user's timezone
        $userConstraints = $this->userAttendanceService->getUserConstraints((string) $user->id, $clockInCarbon->format('Y-m-d'));

        $canClockIn = false;
        $activePeriod = null;
        $rejection = null;
        $clockInCarbon = Carbon::parse($clockInCarbon->format('Y-m-d H:i:s'), $timezone);

        $workRules = $userConstraints['work_rules'] ?? [];

        if (isset($workRules['all_work_periods'])) {
            $periodDateStr = $clockInCarbon->format('Y-m-d');
            foreach ($workRules['all_work_periods'] as $period) {
                $hasActiveAttendance = false;
                if (!empty($period['attendance']) && is_array($period['attendance'])) {
                    foreach ($period['attendance'] as $att) {
                        if (($att['status'] ?? null) === 'active') {
                            $hasActiveAttendance = true;
                            break;
                        }
                    }
                }

                $periodDate = $period['date'] ?? $periodDateStr;
                if ($periodDate instanceof Carbon) {
                    $periodDate = $periodDate->format('Y-m-d');
                }
                $start = Carbon::parse($periodDate . ' ' . ($period['start_time'] ?? '00:00'), $timezone);
                $end = Carbon::parse($periodDate . ' ' . ($period['end_time'] ?? '23:59'), $timezone);
                if (!empty($period['extends_to_next_day'])) {
                    $end->addDay();
                }

                $window = $this->computeWindowForPeriod($period, $start, $end, $clockInCarbon, $workRules, $timezone);
                $hasAnyClockIn = $this->periodHasAnyClockIn($period);
                $isFirstClockIn = !$hasAnyClockIn && !$hasActiveAttendance;

                $latestAllowed = $isFirstClockIn
                    ? ($window->firstClockInDeadline ?? $window->lastClockInAt)
                    : $window->lastClockInAt;

                $tooEarly = $clockInCarbon->lt($window->earliestClockIn);
                $tooLate  = $clockInCarbon->gt($latestAllowed);

                // A different period may still match, so only remember the *closest*
                // rejection — prefer "too late" (window passed) over "too early" (upcoming).
                if ($tooEarly || $tooLate) {
                    if ($rejection === null || $tooLate) {
                        $rejection = [
                            'type' => $tooEarly
                                ? 'clock_in_too_early'
                                : ($isFirstClockIn && $window->firstClockInDeadline !== null
                                    ? 'clock_in_deadline_passed'
                                    : 'clock_in_window_closed'),
                            'window' => $window,
                        ];
                    }
                    continue;
                }

                if (!$hasActiveAttendance) {
                    $canClockIn = true;
                    $activePeriod = $period;
                    $rejection = null;
                    break;
                }
            }
        }

        if (!$canClockIn) {
            return [$this->buildClockInNotAllowedViolation(
                $userConstraints,
                $rejection
            )];
        }

        $mockAttendanceData = [
            'user_id'             => $user->id,
            'clock_in_time'       => $clockInDTO->getClockInTime(),
            'timezone'            => $timezone,
            'clock_in_location'   => $clockInDTO->getLocation(),
            'ip_address'          => $clockInDTO->getIpAddress(),
            'user_agent'          => $clockInDTO->getUserAgent(),
            'verification_data'   => $rawRequestData['verification_data'] ?? null,
        ];
        $mockAttendance = new \Modules\Attendance\Models\Attendance($mockAttendanceData);

        $mockAttendance->setRelation('user', $user);

        return $this->constraintService->validateAttendance($mockAttendance, $rawRequestData, true);
    }

    /**
     * Build the shift window for one period. Rules resolve per-period first (injected into
     * work periods by UserAttendanceService), then fall back to the day-level values emitted
     * by AttendanceConstraintService.
     */
    private function computeWindowForPeriod(
        array $period,
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $clockIn,
        array $workRules,
        string $timezone
    ): ShiftWindow {
        $earlyMinutes = (int) ($period['early_clock_in_minutes'] ?? $workRules['early_clock_in_minutes'] ?? 0);
        $extensionMinutes = (int) ($period['extension_minutes'] ?? $workRules['extension_minutes'] ?? 0);
        $canClockInBefore = $period['can_clock_in_before_minutes'] ?? $workRules['can_clock_in_before_minutes'] ?? null;
        $flags = OvertimeFlags::fromArray($period['overtime_rules'] ?? $workRules['overtime_rules'] ?? null);

        return $this->windowCalculator->compute(new ShiftWindowInput(
            scheduledStart: CarbonImmutable::parse($periodStart->format('Y-m-d H:i:s'), $timezone),
            scheduledEnd: CarbonImmutable::parse($periodEnd->format('Y-m-d H:i:s'), $timezone),
            clockIn: CarbonImmutable::parse($clockIn->format('Y-m-d H:i:s'), $timezone),
            earlyWindowMinutes: $earlyMinutes,
            extensionMinutes: $extensionMinutes,
            canClockInBeforeMinutes: $canClockInBefore !== null ? (int) $canClockInBefore : null,
            maxOverTimeHours: (float) ($workRules['max_over_time'] ?? 0.0),
            overtimeFlags: $flags,
            timezone: $timezone,
        ));
    }

    /**
     * Whether any attendance row in this period has an actual clock-in
     * (period['attendance'] entries are pre-formatted by UserAttendanceService).
     */
    private function periodHasAnyClockIn(array $period): bool
    {
        if (empty($period['attendance']) || !is_array($period['attendance'])) {
            return false;
        }

        foreach ($period['attendance'] as $att) {
            if (!empty($att['clock_in_time'])) {
                return true;
            }
        }

        return false;
    }

    private function buildClockInNotAllowedViolation(
        array $userConstraints,
        ?array $rejection = null
    ): array {
        $workRules = $userConstraints['work_rules'] ?? [];
        $reason = 'Cannot clock in at this time.';
        $type = 'clock_in_not_allowed';
        $dayStatus = $workRules['day_status'] ?? null;

        if ($dayStatus !== null && $dayStatus !== 'work_day') {
            $reason = 'Cannot clock in on non-working day.';
        } elseif ($rejection !== null) {
            $type = $rejection['type'];
            $window = $rejection['window'];
            $reason = match ($rejection['type']) {
                'clock_in_too_early' => 'Clock-in is too early. You can clock in from '
                    . $window->earliestClockIn->format('H:i') . '.',
                'clock_in_deadline_passed' => 'Clock-in deadline passed at '
                    . ($window->firstClockInDeadline?->format('H:i') ?? '') . '.',
                default => 'The shift clock-in window has closed.',
            };
        } elseif (empty($workRules['all_work_periods'])) {
            $reason = 'No active work period available for clock in.';
        }

        $details = [
            'day_status' => $dayStatus,
            'is_holiday' => $workRules['is_holiday'] ?? false,
        ];

        if ($rejection !== null) {
            $details['window'] = $rejection['window']->toResponseArray();
        }

        // Include work periods with early_clock_in_rules so client can show "Clock in from HH:mm (early)"
        $periods = $workRules['all_work_periods'] ?? [];
        if (!empty($periods)) {
            $details['work_periods'] = array_map(function (array $p) {
                $out = [
                    'start_time' => $p['start_time'] ?? null,
                    'end_time' => $p['end_time'] ?? null,
                ];
                if (!empty($p['early_clock_in_rules'])) {
                    $out['early_clock_in_rules'] = $p['early_clock_in_rules'];
                }
                foreach (['can_clock_in_from', 'can_clock_in_until', 'can_clock_out_until', 'absent_at'] as $key) {
                    if (array_key_exists($key, $p)) {
                        $out[$key] = $p[$key];
                    }
                }
                return $out;
            }, $periods);
        }

        return [
            'type' => $type,
            'severity' => 'blocking',
            'message' => $reason,
            'details' => $details,
        ];
    }
}
