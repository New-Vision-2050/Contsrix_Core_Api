<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\UserAttendanceService;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Services\EmployeeTaskAttendanceWindowGuard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmployeeTaskAttendanceWindowGuardTest extends TestCase
{
    private UserAttendanceService&MockObject $userAttendance;
    private AttendanceService&MockObject $attendanceService;
    private EmployeeTaskAttendanceWindowGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-04 10:00:00', 'Asia/Riyadh'));

        $this->userAttendance = $this->createMock(UserAttendanceService::class);
        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->guard = new EmployeeTaskAttendanceWindowGuard(
            $this->userAttendance,
            $this->attendanceService,
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_blocks_when_too_early(): void
    {
        $this->userAttendance->method('getUserConstraints')->willReturn([
            'work_rules' => [
                'day_status' => 'work_day',
                'all_work_periods' => [[
                    'can_clock_in_from' => '2026-08-04T10:30:00+03:00',
                    'can_clock_out_until' => '2026-08-04T17:30:00+03:00',
                    'is_absent' => false,
                    'attendance' => [],
                ]],
            ],
        ]);

        $this->expectException(EmployeeTaskException::class);
        $this->expectExceptionMessage('Too early to create a task');

        $this->guard->assertCanCreateTask('627c7310-cef1-4126-8fc4-e5de721a9dae');
    }

    public function test_blocks_when_deadline_passed_without_clock_in(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 12:00:00', 'Asia/Riyadh'));

        $this->userAttendance->method('getUserConstraints')->willReturn([
            'work_rules' => [
                'day_status' => 'work_day',
                'all_work_periods' => [[
                    'can_clock_in_from' => '2026-08-04T07:00:00+03:00',
                    'can_clock_out_until' => '2026-08-04T17:30:00+03:00',
                    'can_clock_in_until' => '2026-08-04T09:30:00+03:00',
                    'absent_at' => '2026-08-04T09:30:00+03:00',
                    'is_absent' => true,
                    'attendance' => [],
                ]],
            ],
        ]);

        $this->expectException(EmployeeTaskException::class);
        $this->expectExceptionMessage('Clock-in deadline passed');

        $this->guard->assertCanCreateTask('627c7310-cef1-4126-8fc4-e5de721a9dae');
    }

    public function test_blocks_when_inside_window_but_not_clocked_in(): void
    {
        $this->userAttendance->method('getUserConstraints')->willReturn([
            'work_rules' => [
                'day_status' => 'work_day',
                'all_work_periods' => [[
                    'can_clock_in_from' => '2026-08-04T07:00:00+03:00',
                    'can_clock_out_until' => '2026-08-04T17:30:00+03:00',
                    'is_absent' => false,
                    'attendance' => [],
                ]],
            ],
        ]);
        $this->attendanceService->method('getCurrentAttendance')->willReturn(null);

        $this->expectException(EmployeeTaskException::class);
        $this->expectExceptionMessage('You must clock in before creating a task.');

        $this->guard->assertCanCreateTask('627c7310-cef1-4126-8fc4-e5de721a9dae');
    }

    public function test_allows_when_inside_window_and_clocked_in(): void
    {
        $this->userAttendance->method('getUserConstraints')->willReturn([
            'work_rules' => [
                'day_status' => 'work_day',
                'all_work_periods' => [[
                    'can_clock_in_from' => '2026-08-04T07:00:00+03:00',
                    'can_clock_out_until' => '2026-08-04T17:30:00+03:00',
                    'is_absent' => false,
                    'attendance' => [['clock_in_time' => '2026-08-04 07:30:00', 'status' => 'active']],
                ]],
            ],
        ]);

        $active = new Attendance([
            'status' => Attendance::STATUS_ACTIVE,
            'clock_in_time' => '2026-08-04 07:30:00',
            'clock_out_time' => null,
        ]);
        $this->attendanceService->method('getCurrentAttendance')->willReturn($active);

        $this->guard->assertCanCreateTask('627c7310-cef1-4126-8fc4-e5de721a9dae');
        $this->addToAssertionCount(1);
    }
}
