<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class AppliedAttendanceConstraintPresenter extends AbstractPresenter
{
    private Attendance $attendance;
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;

    }

    public function present(bool $isListing = false): ?array
    {
        $snapshot = $this->attendance->appliedAttendanceConstraint->constraint_snapshot;

        if (!$snapshot) {
            return null;
        }

        return [
            'id' => (string) $snapshot['id'],
            'constraint_name' => $snapshot['constraint_name'],
            'constraint_type' => __('validation.' . $snapshot['constraint_type']),
            'constraint_code' => $snapshot['constraint_type'],
            'branch_locations' => $snapshot['branch_locations'],
            'notes' => $snapshot['notes'],
            'is_active' => (int) $snapshot['is_active'],
            'priority' => (int) $snapshot['priority'],
            'start_date' => $snapshot['start_date'],
            'end_date' => $snapshot['end_date'],
            'config' => $this->formatConstraintConfig(),
        ];
    }

    private function formatConstraintConfig(): array
    {
        $config = $this->attendance->appliedAttendanceConstraint->constraint_snapshot['constraint_config'];

        if (!isset($config['time_rules']['weekly_schedule'])) {
            return $config;
        }

        $weeklySchedule = $config['time_rules']['weekly_schedule'];

        $orderedDays = [
            'saturday',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
        ];

        $orderedSchedule = [];

        foreach ($orderedDays as $day) {
            $orderedSchedule[$day] = $weeklySchedule[$day] ?? [
                'enabled' => false,
                'periods' => [],
            ];
        }

        $config['time_rules']['weekly_schedule'] = $orderedSchedule;

        return $config;
    }
}
