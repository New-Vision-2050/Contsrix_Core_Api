<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Presenters;

use Modules\Attendance\Presenters\AttendanceCalendarPresenter;
use PHPUnit\Framework\TestCase;

class AttendanceCalendarPresenterTest extends TestCase
{
    public function test_summary_includes_total_work_hours(): void
    {
        $payload = (new AttendanceCalendarPresenter([
            'days' => [],
            'summary' => [
                'total_days' => 30,
                'present_count' => 22,
                'late_count' => 3,
                'absent_count' => 2,
                'leave_count' => 1,
                'off_count' => 5,
                'required_count' => 25,
                'total_work_hours' => 176.5,
            ],
        ]))->present();

        $this->assertSame(176.5, $payload['summary']['total_work_hours']);
    }

    public function test_summary_defaults_total_work_hours_to_zero(): void
    {
        $payload = (new AttendanceCalendarPresenter([
            'days' => [],
            'summary' => [],
        ]))->present();

        $this->assertSame(0.0, $payload['summary']['total_work_hours']);
    }
}
