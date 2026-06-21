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
        $days = $this->calendarData['days'] ?? [];

        return [
            'days'    => array_map([$this, 'presentDay'], $days),
            'summary' => $this->presentSummary($this->calendarData['summary'] ?? []),
        ];
    }

    private function presentDay(array $day): array
    {
        $statusKey = $day['status_key'] ?? '';

        return [
            'date'               => $day['date'] ?? null,
            'day_name'           => $day['day_name'] ?? null,
            'day_number'         => $day['day_number'] ?? null,
            'status_key'         => $statusKey,
            'status'             => $day['status'] ?? null,
            'work_hours'         => $day['work_hours'] ?? null,
            'duration_formatted' => $day['duration_formatted'] ?? null,
            'dot_color'          => $this->resolveDotColor($statusKey),
            'attendance_count'   => $day['attendance_count'] ?? 0,
        ];
    }

    private function resolveDotColor(string $statusKey): string
    {
        return match ($statusKey) {
            'present'  => '#4CAF50',
            'late'     => '#FF9800',
            'absent'   => '#F44336',
            'leave'    => '#9C27B0',
            'off'      => '#9E9E9E',
            'required' => '#2196F3',
            default    => '#9E9E9E',
        };
    }

    private function presentSummary(array $summary): array
    {
        return [
            'total_days'       => $summary['total_days'] ?? 0,
            'present_count'    => $summary['present_count'] ?? 0,
            'late_count'       => $summary['late_count'] ?? 0,
            'absent_count'     => $summary['absent_count'] ?? 0,
            'leave_count'      => $summary['leave_count'] ?? 0,
            'off_count'        => $summary['off_count'] ?? 0,
            'required_count'   => $summary['required_count'] ?? 0,
            'total_work_hours' => $summary['total_work_hours'] ?? 0.0,
        ];
    }
}
