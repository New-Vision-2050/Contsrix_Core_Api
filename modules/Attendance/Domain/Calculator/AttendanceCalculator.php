<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * Pure domain calculator — no IO, no Eloquent, no facades, no Carbon::now().
 * Stateless: safe as a singleton under Octane/RoadRunner.
 *
 * Single source of truth for: total_work_hours, total_break_hours, overtime_hours,
 * is_late, late_minutes, is_early_departure, early_departure_minutes, and the V2
 * zone hours (pre/in/post shift + outside-window).
 *
 * All callers (AttendanceService, AutoCloseAttendanceService, ProcessClockInAttendanceData,
 * HandleAttendanceLateness) must route through this class.
 *
 * Partial shift: when {@see CalculatorInput::$clockIn} is set but {@see CalculatorInput::$clockOut}
 * is null (user still working), work/overtime/early-exit are not computed; lateness is still
 * evaluated from clock-in vs scheduled start (same rules as a completed shift).
 */
final class AttendanceCalculator
{
    public function __construct(
        private readonly LatenessPolicy       $lateness,
        private readonly OvertimePolicy       $overtime,
        private readonly EarlyDeparturePolicy $earlyDeparture,
        private readonly ZoneSplitter         $zoneSplitter = new ZoneSplitter(),
    ) {}

    public function calculate(CalculatorInput $input): WorkHoursResult
    {
        if (!$input->clockIn) {
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

        if (!$input->clockOut) {
            [$isLate, $lateMinutes] = $this->lateness->evaluate($input);

            return new WorkHoursResult(
                totalWorkHours: 0.0,
                totalBreakHours: 0.0,
                overtimeHours: 0.0,
                isLate: $isLate,
                lateMinutes: $lateMinutes,
                isEarlyDeparture: false,
                earlyDepartureMinutes: 0,
            );
        }

        $grossMinutes = (int) $input->clockIn->diffInMinutes($input->clockOut, false);
        $netMinutes   = max(0, $grossMinutes - $input->totalBreakMinutes);

        $breakHours = round($input->totalBreakMinutes / 60, 2);

        $overtimeHours = $this->overtime->calculate($input, $netMinutes);

        // V2: total_work_hours is net of overtime; V1 rollback keeps it gross.
        $workHours = $input->excludeOvertimeFromWorkHours
            ? round(max(0, $netMinutes - (int) round($overtimeHours * 60)) / 60, 2)
            : round($netMinutes / 60, 2);

        [$isLate, $lateMinutes]               = $this->lateness->evaluate($input);
        [$isEarlyDeparture, $earlyMinutes]    = $this->earlyDeparture->evaluate($input);

        $zones = $this->zoneSplitter->split($input);

        return new WorkHoursResult(
            totalWorkHours: $workHours,
            totalBreakHours: $breakHours,
            overtimeHours: $overtimeHours,
            isLate: $isLate,
            lateMinutes: $lateMinutes,
            isEarlyDeparture: $isEarlyDeparture,
            earlyDepartureMinutes: $earlyMinutes,
            preShiftHours: round(($zones->outerPre + $zones->earlyWindow) / 60, 2),
            inShiftHours: round($zones->inShift / 60, 2),
            postShiftHours: round(($zones->extensionWindow + $zones->outerPost) / 60, 2),
            outsideWindowHours: round($zones->outsideWindow() / 60, 2),
        );
    }
}
