<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\Events\AttendanceClockedIn;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Carbon\Carbon;

/**
 * A service dedicated to creating non-persisted (mock) Attendance models
 * for the purpose of pre-validation against constraints.
 */
class MockAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService,
        private UserAttendanceService $userAttendanceService
    ) {}
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
        $matchedPeriod = null;
        $matchedPeriodHasActiveAttendance = false;
        $clockInCarbon = Carbon::parse($clockInCarbon->format('Y-m-d H:i:s'), $timezone);

        if (isset($userConstraints['work_rules']['all_work_periods'])) {
            $periodDateStr = $clockInCarbon->format('Y-m-d');
            foreach ($userConstraints['work_rules']['all_work_periods'] as $period) {
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

                // Apply early clock-in window: allow clock-in from (start - early_period) to end
                $effectiveStart = $this->applyEarlyClockInWindow($start, $period);
                if ($effectiveStart !== null) {
                    $start = $effectiveStart;
                }

                if ($clockInCarbon->between($start, $end, true)) {
                    $matchedPeriod = $period;
                    $matchedPeriodHasActiveAttendance = $hasActiveAttendance;

                    if (!$hasActiveAttendance) {
                        $canClockIn = true;
                        $activePeriod = $period;
                    }
                    break;
                }
            }
        }
        
        if (!$canClockIn) {
            return [$this->buildClockInNotAllowedViolation(
                $userConstraints,
                $matchedPeriod,
                $matchedPeriodHasActiveAttendance,
                $activePeriod
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

        // Check lateness at clock-in time for the mock attendance
        //$mockAttendance->checkLateness();

        return $this->constraintService->validateAttendance($mockAttendance, $rawRequestData, true);
    }

    /**
     * If period has early_clock_in_rules, return period start minus early window; otherwise null.
     */
    private function applyEarlyClockInWindow(Carbon $periodStart, array $period): ?Carbon
    {
        $rules = $period['early_clock_in_rules'] ?? null;
        if (!is_array($rules)) {
            return null;
        }
        $earlyPeriod = (int) ($rules['early_period'] ?? 0);
        if ($earlyPeriod <= 0) {
            return null;
        }
        $earlyUnit = (string) ($rules['early_unit'] ?? 'minutes');
        if ($earlyUnit === '') {
            return null;
        }
        if (strtolower($earlyUnit) === 'minute') {
            $earlyUnit = 'minutes';
        }
        return $periodStart->copy()->sub($earlyPeriod, $earlyUnit);
    }

    private function buildClockInNotAllowedViolation(
        array $userConstraints,
        ?array $matchedPeriod,
        bool $matchedPeriodHasActiveAttendance,
        ?array $activePeriod
    ): array {
        $reason = 'Cannot clock in at this time.';
        $dayStatus = $userConstraints['work_rules']['day_status'] ?? null;

        if ($dayStatus !== null && $dayStatus !== 'work_day') {
            $reason = 'Cannot clock in on non-working day.';
        } elseif ($matchedPeriod !== null && $matchedPeriodHasActiveAttendance) {
            $reason = 'You are already clocked in.';
        } elseif ($activePeriod === null) {
            $reason = 'No active work period available for clock in.';
        }

        $details = [
            'day_status' => $dayStatus,
            'is_holiday' => $userConstraints['work_rules']['is_holiday'] ?? false,
        ];
        // Include work periods with early_clock_in_rules so client can show "Clock in from HH:mm (early)"
        $periods = $userConstraints['work_rules']['all_work_periods'] ?? [];
        if (!empty($periods)) {
            $details['work_periods'] = array_map(function (array $p) {
                $out = [
                    'start_time' => $p['start_time'] ?? null,
                    'end_time' => $p['end_time'] ?? null,
                ];
                if (!empty($p['early_clock_in_rules'])) {
                    $out['early_clock_in_rules'] = $p['early_clock_in_rules'];
                }
                return $out;
            }, $periods);
        }

        return [
            'type' => 'clock_in_not_allowed',
            'severity' => 'blocking',
            'message' => $reason,
            'details' => $details,
        ];
    }
}
