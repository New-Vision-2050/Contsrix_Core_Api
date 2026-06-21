<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class AttendanceDashboardPresenter extends AbstractPresenter
{
    /**
     * @param  array{contract: array, achieved: array, remaining: array}  $summary
     */
    public function __construct(private array $summary) {}

    protected function present(bool $isListing = false): array
    {
        return [
            'contract' => [
                'attendance_days' => (int) ($this->summary['contract']['attendance_days'] ?? 0),
                'required_hours' => (float) ($this->summary['contract']['required_hours'] ?? 0),
                'leave_allowance' => (int) ($this->summary['contract']['leave_allowance'] ?? 0),
            ],
            'achieved' => [
                'attendance_days' => (int) ($this->summary['achieved']['attendance_days'] ?? 0),
                'worked_hours' => (float) ($this->summary['achieved']['worked_hours'] ?? 0),
                'used_leaves' => (int) ($this->summary['achieved']['used_leaves'] ?? 0),
                'used_holidays' => (int) ($this->summary['achieved']['used_holidays'] ?? 0),
            ],
            'remaining' => [
                'attendance_days' => (int) ($this->summary['remaining']['attendance_days'] ?? 0),
                'worked_hours' => (float) ($this->summary['remaining']['worked_hours'] ?? 0),
                'remaining_leaves' => (int) ($this->summary['remaining']['remaining_leaves'] ?? 0),
            ],
        ];
    }
}
