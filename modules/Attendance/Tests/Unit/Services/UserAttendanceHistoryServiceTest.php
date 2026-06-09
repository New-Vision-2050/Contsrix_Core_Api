<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\UserAttendanceHistoryService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class UserAttendanceHistoryServiceTest extends TestCase
{
    private UserAttendanceHistoryService $service;
    private ReflectionMethod $buildDayStatusPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserAttendanceHistoryService(
            $this->createMock(AttendanceConstraintService::class)
        );

        $this->buildDayStatusPayload = new ReflectionMethod($this->service, 'buildDayStatusPayload');
        $this->buildDayStatusPayload->setAccessible(true);
    }

    public function test_empty_attendance_collection_is_absent(): void
    {
        $payload = $this->dayStatusPayload(collect());

        $this->assertSame('غائب', $payload['status']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(1, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_holiday_attendance_sets_holiday_flag(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_HOLIDAY,
                'day_status' => 'holiday',
                'is_holiday' => 1,
            ]),
        ]));

        $this->assertSame('عطلة', $payload['status']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(1, $payload['is_holiday']);
    }

    public function test_absent_attendance_sets_absent_flag(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_ABSENT,
                'is_absent' => 1,
            ]),
        ]));

        $this->assertSame('غائب', $payload['status']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(1, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_late_attendance_sets_late_flag(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_COMPLETED,
                'clock_out_time' => '2026-06-09 17:00:00',
                'is_late' => 1,
            ]),
        ]));

        $this->assertSame('متأخر', $payload['status']);
        $this->assertSame(1, $payload['is_late']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_normal_completed_attendance_keeps_flags_clear(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_COMPLETED,
                'clock_out_time' => '2026-06-09 17:00:00',
                'is_late' => 0,
                'is_absent' => 0,
                'is_holiday' => 0,
            ]),
        ]));

        $this->assertSame('تم الخروج', $payload['status']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    /**
     * @return array{status: string, is_late: int, is_absent: int, is_holiday: int}
     */
    private function dayStatusPayload(Collection $attendances): array
    {
        return $this->buildDayStatusPayload->invoke($this->service, $attendances);
    }
}
