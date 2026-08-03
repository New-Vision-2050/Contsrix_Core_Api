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
use Modules\Attendance\Support\EarlyClockInRules;
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

                $window = $this->computeWindowForPeriod($period, $start, $end, $clockInCarbon, $workRules, $timezone, (string) $user->id);
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

                // Inside the window but already clocked in — keep the specific outcome
                // (pre-V2 behaviour) instead of falling through to a generic message.
                if ($hasActiveAttendance) {
                    $rejection = ['type' => 'already_clocked_in', 'window' => $window];
                    break;
                }

                $canClockIn = true;
                $activePeriod = $period;
                $rejection = null;
                break;
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
        string $timezone,
        string $userId
    ): ShiftWindow {
        $earlyMinutes = $this->resolveEarlyClockInMinutes($period, $workRules);
        $extensionMinutes = $this->resolveExtensionMinutes($period, $workRules);
        $canClockInBefore = $this->resolveCanClockInBeforeMinutes($period, $workRules);
        $flags = OvertimeFlags::fromArray($period['overtime_rules'] ?? $workRules['overtime_rules'] ?? null);

        return $this->windowCalculator->compute(new ShiftWindowInput(
            scheduledStart: CarbonImmutable::parse($periodStart->format('Y-m-d H:i:s'), $timezone),
            scheduledEnd: CarbonImmutable::parse($periodEnd->format('Y-m-d H:i:s'), $timezone),
            clockIn: CarbonImmutable::parse($clockIn->format('Y-m-d H:i:s'), $timezone),
            earlyWindowMinutes: $earlyMinutes,
            extensionMinutes: $extensionMinutes,
            canClockInBeforeMinutes: $canClockInBefore,
            maxOverTimeHours: (float) ($workRules['max_over_time'] ?? 0.0),
            alreadyWorkedMinutesInPeriod: $this->attendanceService->workedMinutesInScheduledPeriod(
                $userId,
                $periodStart->format('Y-m-d H:i:s'),
                $periodEnd->format('Y-m-d H:i:s'),
            ),
            overtimeFlags: $flags,
            timezone: $timezone,
        ));
    }

    /**
     * Prefer day-level V2 minutes; fall back to period/day early_clock_in_rules (legacy shape).
     * Take the max across sources so a stripped minutes key (0) cannot hide early_period=30.
     */
    private function resolveEarlyClockInMinutes(array $period, array $workRules): int
    {
        return max(
            (int) ($period['early_clock_in_minutes'] ?? 0),
            (int) ($workRules['early_clock_in_minutes'] ?? 0),
            EarlyClockInRules::minutes(
                is_array($period['early_clock_in_rules'] ?? null) ? $period['early_clock_in_rules'] : null
            ),
            EarlyClockInRules::minutes(
                is_array($workRules['early_clock_in_rules'] ?? null) ? $workRules['early_clock_in_rules'] : null
            ),
        );
    }

    private function resolveExtensionMinutes(array $period, array $workRules): int
    {
        foreach ([
            $period['extension_minutes'] ?? null,
            $workRules['extension_minutes'] ?? null,
            $period['extension_rules']['extension_minutes'] ?? null,
            $workRules['extension_rules']['extension_minutes'] ?? null,
        ] as $candidate) {
            if ($candidate !== null && is_numeric($candidate)) {
                return max(0, (int) $candidate);
            }
        }

        // Legacy hours (pre minutes-only Rules API).
        $legacyHours = $period['extension_rules']['extension_hours']
            ?? $workRules['extension_rules']['extension_hours']
            ?? null;

        return $legacyHours !== null ? max(0, (int) round(((float) $legacyHours) * 60)) : 0;
    }

    private function resolveCanClockInBeforeMinutes(array $period, array $workRules): ?int
    {
        if (array_key_exists('can_clock_in_before_minutes', $period)) {
            return isset($period['can_clock_in_before_minutes']) ? (int) $period['can_clock_in_before_minutes'] : null;
        }
        if (array_key_exists('can_clock_in_before_minutes', $workRules)) {
            return isset($workRules['can_clock_in_before_minutes']) ? (int) $workRules['can_clock_in_before_minutes'] : null;
        }
        if (isset($period['can_clock_in_before'])) {
            return (int) $period['can_clock_in_before'];
        }
        if (isset($workRules['can_clock_in_before'])) {
            return (int) $workRules['can_clock_in_before'];
        }
        if (isset($period['clock_in_deadline_rules']['can_clock_in_before_minutes'])) {
            return (int) $period['clock_in_deadline_rules']['can_clock_in_before_minutes'];
        }
        if (isset($workRules['clock_in_deadline_rules']['can_clock_in_before_minutes'])) {
            return (int) $workRules['clock_in_deadline_rules']['can_clock_in_before_minutes'];
        }

        return null;
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
                'already_clocked_in' => 'You are already clocked in.',
                'clock_in_too_early' => 'Clock-in is too early. You can clock in from '
                    . $window->earliestClockIn->format('H:i') . '.',
                'clock_in_deadline_passed' => 'Clock-in deadline passed at '
                    . ($window->firstClockInDeadline?->format('H:i') ?? '') . '.',
                default => 'The shift clock-in window has closed.',
            };
        } elseif (empty($workRules['all_work_periods'])) {
            $reason = 'No active work period available for clock in.';
        }

        // Keep violation payload minimal — mobile clients often dump the whole error body.
        return [
            'type' => $type,
            'severity' => 'blocking',
            'message' => $reason,
        ];
    }
}
