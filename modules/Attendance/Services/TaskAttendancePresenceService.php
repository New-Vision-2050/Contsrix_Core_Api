<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Repositories\AttendanceReportRepository;
use Modules\EmployeeTask\Services\EmployeeTaskPresenceService;

/**
 * Augments the official monthly / payroll attendance figures so that days an
 * employee spent on a task (متواجد) — including project-notification / emergency
 * tasks — count as attended, even when no clock-in attendance record exists.
 *
 * Task-presence days that already have a physical attendance record are ignored
 * to avoid double-counting; only "pure task" days add to the totals.
 */
class TaskAttendancePresenceService
{
    public function __construct(
        private readonly EmployeeTaskPresenceService $presenceService,
        private readonly AttendanceReportRepository $repository,
    ) {}

    /**
     * @return array{
     *   extra_days_total: int,
     *   extra_hours_total: float,
     *   extra_days_by_month: array<string, int>,
     *   extra_hours_by_month: array<string, float>
     * }
     */
    public function augmentation(AttendanceReportFilterDTO $filters): array
    {
        $empty = [
            'extra_days_total'     => 0,
            'extra_hours_total'    => 0.0,
            'extra_days_by_month'  => [],
            'extra_hours_by_month' => [],
        ];

        $details = $this->presenceService->taskPresenceDetailsForUsers(
            [$filters->employee_id],
            $filters->periodStart(),
            $filters->periodEnd(),
        )[(string) $filters->employee_id] ?? [];

        if ($details === []) {
            return $empty;
        }

        $attended = array_fill_keys($this->repository->getAttendedDates($filters), true);

        $daysByMonth  = [];
        $hoursByMonth = [];
        $daysTotal    = 0;
        $hoursTotal   = 0.0;

        foreach ($details as $date => $info) {
            if (isset($attended[$date])) {
                continue;
            }

            $month = substr((string) $date, 0, 7);
            $hours = round(((int) $info['minutes']) / 60, 2);

            $daysByMonth[$month]  = ($daysByMonth[$month] ?? 0) + 1;
            $hoursByMonth[$month] = round(($hoursByMonth[$month] ?? 0.0) + $hours, 2);
            $daysTotal++;
            $hoursTotal += $hours;
        }

        return [
            'extra_days_total'     => $daysTotal,
            'extra_hours_total'    => round($hoursTotal, 2),
            'extra_days_by_month'  => $daysByMonth,
            'extra_hours_by_month' => $hoursByMonth,
        ];
    }
}
