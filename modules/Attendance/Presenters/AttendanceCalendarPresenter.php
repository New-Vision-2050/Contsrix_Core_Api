<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class AttendanceCalendarPresenter extends AbstractPresenter
{
    private array $calendarData;

    public function __construct(array $calendarData)
    {
        $this->calendarData = $calendarData;
    }

    public function present(bool $isListing = false): array
    {
        return [
            'days'    => $this->calendarData['days'] ?? [],
            'summary' => $this->presentSummary($this->calendarData['summary'] ?? []),
        ];
    }

    private function presentSummary(array $summary): array
    {
        return [
            'total_days'     => $summary['total_days'] ?? 0,
            'present_count'  => $summary['present_count'] ?? 0,
            'late_count'     => $summary['late_count'] ?? 0,
            'absent_count'   => $summary['absent_count'] ?? 0,
            'leave_count'    => $summary['leave_count'] ?? 0,
            'off_count'      => $summary['off_count'] ?? 0,
            'required_count' => $summary['required_count'] ?? 0,
        ];
    }
}
