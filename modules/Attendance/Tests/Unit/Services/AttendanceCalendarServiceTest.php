<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceCalendarService;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\UserAttendanceService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AttendanceCalendarServiceTest extends TestCase
{
    private AttendanceCalendarService $service;
    private ReflectionMethod $calculateTotalWorkHours;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AttendanceCalendarService(
            $this->createMock(AttendanceConstraintService::class),
            $this->createMock(UserAttendanceService::class),
        );

        $this->calculateTotalWorkHours = new ReflectionMethod($this->service, 'calculateTotalWorkHoursFromGroupedAttendances');
        $this->calculateTotalWorkHours->setAccessible(true);
    }

    public function test_total_work_hours_sums_multiple_attendance_records_in_month(): void
    {
        $groupedAttendances = collect([
            '2026-05-01' => collect([
                $this->attendance(['total_work_hours' => '8.00']),
            ]),
            '2026-05-02' => collect([
                $this->attendance(['total_work_hours' => '7.50']),
            ]),
        ]);

        $this->assertSame(15.5, $this->totalWorkHours($groupedAttendances));
    }

    public function test_total_work_hours_is_zero_when_no_attendance_records_exist(): void
    {
        $this->assertSame(0.0, $this->totalWorkHours(collect()));
    }

    public function test_total_work_hours_falls_back_to_clock_times_minus_breaks(): void
    {
        $attendance = $this->attendance([
            'clock_in_time' => '2026-05-03 09:00:00',
            'clock_out_time' => '2026-05-03 18:00:00',
            'total_work_hours' => '0.00',
            'total_break_hours' => '0.00',
            'timezone' => 'UTC',
        ]);
        $attendance->setRelation('breaks', collect([
            (object) [
                'start_time' => '2026-05-03 13:00:00',
                'end_time' => '2026-05-03 14:00:00',
                'duration_minutes' => 60,
            ],
        ]));

        $groupedAttendances = collect([
            '2026-05-03' => collect([$attendance]),
        ]);

        $this->assertSame(8.0, $this->totalWorkHours($groupedAttendances));
    }

    public function test_total_work_hours_for_partial_month_ignores_days_without_attendance(): void
    {
        $groupedAttendances = collect([
            '2026-05-01' => collect([
                $this->attendance(['total_work_hours' => '2.00']),
            ]),
            '2026-05-02' => collect(),
            '2026-05-20' => collect([
                $this->attendance(['total_work_hours' => '2.00']),
            ]),
        ]);

        $this->assertSame(4.0, $this->totalWorkHours($groupedAttendances));
    }

    /**
     * @param Collection<string, Collection<int, Attendance>> $groupedAttendances
     */
    private function totalWorkHours(Collection $groupedAttendances): float
    {
        return $this->calculateTotalWorkHours->invoke($this->service, $groupedAttendances);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function attendance(array $attributes): Attendance
    {
        $attendance = new Attendance();
        foreach ($attributes as $key => $value) {
            $attendance->setAttribute($key, $value);
        }

        return $attendance;
    }
}
