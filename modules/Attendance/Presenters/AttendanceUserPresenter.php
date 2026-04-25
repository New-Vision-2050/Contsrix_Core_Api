<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class AttendanceUserPresenter extends AbstractPresenter
{
    private Attendance $attendance;
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public static function requiredRelations(): array
    {
        return [
            'appliedAttendanceConstraint',
        ];
    }

    public function present(bool $isListing = false): array
    {
        // Get all tracking points, or an empty array if none.
        $trackingPoints = $this->attendance->location_tracking ?? [];

        // Find the most recent tracking point.
        $latestPoint = !empty($trackingPoints) ? end($trackingPoints) : null;

        // Determine work date (Y-m-d) from start_time or clock_in_time.
        $workDate = $this->attendance->start_time
            ? \Carbon\Carbon::parse($this->attendance->start_time)->format('Y-m-d')
            : ($this->attendance->clock_in_time
                ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d')
                : null);

        // Calculate day name and number of periods for that day from constraint config.
        $dayName = null;
        $dayPeriodsCount = null;
        if ($workDate && $this->attendance->appliedAttendanceConstraint && is_array($this->attendance->appliedAttendanceConstraint->constraint_snapshot)) {
            $workCarbon = \Carbon\Carbon::parse($workDate);
            $dayKey = strtolower($workCarbon->format('l')); // sunday, monday, ...

            $snapshot = $this->attendance->appliedAttendanceConstraint->constraint_snapshot;
            $constraintConfig = $snapshot['constraint_config'] ?? [];
            $timeRules = $constraintConfig['time_rules'] ?? [];
            $weeklySchedule = $timeRules['weekly_schedule'] ?? [];
            $daySchedule = $weeklySchedule[$dayKey] ?? null;

            $dayName = $dayKey;
            if (is_array($daySchedule) && isset($daySchedule['periods']) && is_array($daySchedule['periods'])) {
                $dayPeriodsCount = count($daySchedule['periods']);
            } else {
                $dayPeriodsCount = 0;
            }
        }

        return [

            'id' => $this->attendance->id ? (string)$this->attendance->id : null,

            'status' => $this->attendance->status,
            'is_late' => (int) $this->attendance->is_late,
            'is_absent' => (int) $this->attendance->is_absent,
            'is_holiday' => (int) $this->attendance->is_holiday,
            'work_date' => $workDate,
            'day_name' => $dayName,
            'day_periods_count' => $dayPeriodsCount,
            'day_status' => __('validation.day_status.'.$this->attendance->day_status??'work_day') ?? '',
        ];
    }
}
