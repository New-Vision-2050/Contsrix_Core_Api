<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class AttendanceReportRowPresenter extends AbstractPresenter
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function __construct(private array $row) {}

    protected function present(bool $isListing = false): array
    {
        return [
            'month' => (string) ($this->row['month'] ?? ''),
            'days_in_month' => (int) ($this->row['days_in_month'] ?? 0),
            'required_attendance_days' => (int) ($this->row['required_attendance_days'] ?? 0),
            'used_leaves' => (int) ($this->row['used_leaves'] ?? 0),
            'earned_leave_days' => (float) ($this->row['earned_leave_days'] ?? 0),
            'month_holidays' => (int) ($this->row['month_holidays'] ?? 0),
            'required_hours' => (float) ($this->row['required_hours'] ?? 0),
            'actual_attendance_days' => (int) ($this->row['actual_attendance_days'] ?? 0),
            'remaining_attendance_days' => (int) ($this->row['remaining_attendance_days'] ?? 0),
            'leave_balance_used' => (int) ($this->row['leave_balance_used'] ?? 0),
            'remaining_leave_balance' => (int) ($this->row['remaining_leave_balance'] ?? 0),
            'actual_worked_hours' => (float) ($this->row['actual_worked_hours'] ?? 0),
            'calculated_hours' => (float) ($this->row['calculated_hours'] ?? 0),
            'remaining_hours' => (float) ($this->row['remaining_hours'] ?? 0),
            'delays' => (int) ($this->row['delays'] ?? 0),
            'overtime' => (float) ($this->row['overtime'] ?? 0),
            'status' => (string) ($this->row['status'] ?? 'pending'),
        ];
    }
}
