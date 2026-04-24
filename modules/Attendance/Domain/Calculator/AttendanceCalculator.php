<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * Pure domain calculator — no IO, no Eloquent, no facades, no Carbon::now().
 * Stateless: safe as a singleton under Octane/RoadRunner.
 *
 * Single source of truth for: total_work_hours, total_break_hours, overtime_hours,
 * is_late, late_minutes, is_early_departure, early_departure_minutes.
 *
 * All callers (ClockOutService, AutoCloseAttendanceService, calculateWorkHours())
 * must route through this class.
 */
final class AttendanceCalculator
{
    public function __construct(
        private readonly LatenessPolicy       $lateness,
        private readonly OvertimePolicy       $overtime,
        private readonly EarlyDeparturePolicy $earlyDeparture,
    ) {}

    public function calculate(CalculatorInput $input): WorkHoursResult
    {
        // No clock-out yet → nothing to compute.
        if (!$input->clockIn || !$input->clockOut) {
            return new WorkHoursResult(
                totalWorkHours: 0.0,
                totalBreakHours: 0.0,
                overtimeHours: 0.0,
                isLate: false,
                lateMinutes: 0,
                isEarlyDeparture: false,
                earlyDepartureMinutes: 0,
            );
        }

        $grossMinutes = (int) $input->clockIn->diffInMinutes($input->clockOut, false);
        $netMinutes   = max(0, $grossMinutes - $input->totalBreakMinutes);

        $breakHours = round($input->totalBreakMinutes / 60, 2);
        $workHours  = round($netMinutes / 60, 2);

        $overtimeHours = $this->overtime->calculate($input, $netMinutes);

        [$isLate, $lateMinutes]               = $this->lateness->evaluate($input);
        [$isEarlyDeparture, $earlyMinutes]    = $this->earlyDeparture->evaluate($input);

        return new WorkHoursResult(
            totalWorkHours: $workHours,
            totalBreakHours: $breakHours,
            overtimeHours: $overtimeHours,
            isLate: $isLate,
            lateMinutes: $lateMinutes,
            isEarlyDeparture: $isEarlyDeparture,
            earlyDepartureMinutes: $earlyMinutes,
        );
    }
}
