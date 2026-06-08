<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\AttendanceConstraint;

class ConstraintPresenter extends AbstractPresenter
{
    public function __construct(private AttendanceConstraint $constraint)
    {
    }

    public function present(bool $isListing = false): array
    {
        return [
            'id' => (string) $this->constraint->id,
            'constraint_name' => $this->constraint->constraint_name,
            'constraint_type' =>  __('validation.'.$this->constraint->constraint_type),
            'constraint_code' =>  $this->constraint->constraint_type,
            'branch_locations' => $this->constraint->branch_locations,
            'notes' => $this->constraint->notes,
            'is_active' => (int) $this->constraint->is_active,
            'priority' => (int) $this->constraint->priority,
            'start_date' => $this->constraint->start_date?->format('Y-m-d'),
            'end_date' => $this->constraint->end_date?->format('Y-m-d'),
            'max_over_time' => $this->constraint->max_over_time,
            'config' => $this->formatConstraintConfig(),
            'branches' => $this->formatBranches(),
            'created_by' => $this->constraint->creator?->name,
            'created_at' => $this->constraint->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function formatBranches(): array
    {
        // This assumes you have a `branches` relationship defined on the AttendanceConstraint model.
        if (!$this->constraint->relationLoaded('branches')) {
            return [];
        }

        return $this->constraint->branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
            ];
        })->all();
    }

    private function formatConstraintConfig(): array
    {
        $config = $this->constraint->constraint_config;

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
