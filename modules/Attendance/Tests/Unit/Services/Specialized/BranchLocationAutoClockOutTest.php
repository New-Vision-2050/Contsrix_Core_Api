<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\LocationConstraintService;
use Modules\Attendance\Services\RadiusEnforcementService;
use Modules\Attendance\Services\TaskService;
use Modules\EmployeeTask\Services\EmployeeTaskPresenceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BranchLocationAutoClockOutTest extends TestCase
{
    /** Allowed zone: office plus one additional location. */
    private const ALLOWED_LOCATIONS = [
        ['name' => 'Office', 'latitude' => 21.4225, 'longitude' => 39.8262, 'radius' => 200],
        ['name' => 'Site A', 'latitude' => 21.3974, 'longitude' => 39.7905, 'radius' => 300],
    ];

    private AttendanceService&MockObject $attendanceService;
    private EmployeeTaskPresenceService&MockObject $presenceService;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-24 19:00:00');

        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->presenceService   = $this->createMock(EmployeeTaskPresenceService::class);
        $this->presenceService->method('userIdsWithTaskInRange')->willReturn([]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shift_survives_while_within_the_out_zone_allowance(): void
    {
        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        // Left the zone 10 minutes ago, allowance is 30.
        $this->endShift(
            $this->activeAttendance([
                $this->point(21.4225, 39.8262, '2026-08-24 18:30:00'),
                $this->point(21.6737, 39.2038, '2026-08-24 18:50:00'),
            ]),
            $this->constraint(30)
        );
    }

    public function test_shift_ends_once_the_out_zone_allowance_is_exceeded(): void
    {
        $arguments = [];
        $this->attendanceService
            ->expects($this->once())
            ->method('endShiftAutomatically')
            ->willReturnCallback(function (...$args) use (&$arguments) {
                $arguments = $args;

                return true;
            });

        $this->endShift(
            $this->activeAttendance([
                $this->point(21.4225, 39.8262, '2026-08-24 18:00:00'),
                $this->point(21.6737, 39.2038, '2026-08-24 18:20:00'),
                $this->point(21.6738, 39.2039, '2026-08-24 18:55:00'),
            ]),
            $this->constraint(30)
        );

        $this->assertSame('e5a6faf8-1b88-4546-bc14-e155fa324d05', $arguments[0]);
        $this->assertSame('auto_out_zone_enforcement', $arguments[1]);
        $this->assertStringContainsString('for 40 minutes (allowed 30)', $arguments[2]);
    }

    public function test_returning_to_an_additional_location_resets_the_allowance(): void
    {
        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $this->endShift(
            $this->activeAttendance([
                $this->point(21.6737, 39.2038, '2026-08-24 17:00:00'),
                // Back inside the additional location, then out again 5 minutes ago.
                $this->point(21.3974, 39.7905, '2026-08-24 18:50:00'),
                $this->point(21.6737, 39.2038, '2026-08-24 18:55:00'),
            ]),
            $this->constraint(30)
        );
    }

    public function test_zero_allowance_ends_the_shift_on_the_first_reading_outside(): void
    {
        $this->attendanceService->expects($this->once())->method('endShiftAutomatically');

        $this->endShift(
            $this->activeAttendance([
                $this->point(21.6737, 39.2038, '2026-08-24 19:00:00'),
            ]),
            $this->constraint(0)
        );
    }

    public function test_employee_on_task_keeps_the_shift_open(): void
    {
        $presenceService = $this->createMock(EmployeeTaskPresenceService::class);
        $presenceService->method('userIdsWithTaskInRange')
            ->willReturn(['852a39e8-6e15-4e37-ac43-0da7a93fcd45']);
        $this->presenceService = $presenceService;

        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $this->endShift(
            $this->activeAttendance([$this->point(21.6737, 39.2038, '2026-08-24 17:00:00')]),
            $this->constraint(30)
        );
    }

    public function test_clock_in_pre_validation_does_not_end_anything(): void
    {
        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $attendance = $this->activeAttendance([$this->point(21.6737, 39.2038, '2026-08-24 17:00:00')]);
        $attendance->exists = false;

        $this->endShift($attendance, $this->constraint(30));
    }

    public function test_already_closed_shift_is_left_alone(): void
    {
        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $attendance = $this->activeAttendance([$this->point(21.6737, 39.2038, '2026-08-24 17:00:00')]);
        $attendance->setAttribute('status', Attendance::STATUS_COMPLETED);
        $attendance->setAttribute('clock_out_time', '2026-08-24 18:00:00');

        $this->endShift($attendance, $this->constraint(30));
    }

    /**
     * @return array<string, mixed>
     */
    private function point(float $latitude, float $longitude, string $timestamp): array
    {
        return [
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'timestamp' => $timestamp,
        ];
    }

    private function constraint(int $outZoneMinutes): AttendanceConstraint
    {
        $constraint = new AttendanceConstraint();
        $constraint->setAttribute('out_zone_minutes', $outZoneMinutes);
        $constraint->setAttribute('branch_locations', self::ALLOWED_LOCATIONS);

        return $constraint;
    }

    /**
     * @param array<int, array<string, mixed>> $trackingPoints
     */
    private function activeAttendance(array $trackingPoints): Attendance
    {
        $attendance = new Attendance();
        $attendance->setAttribute('id', 'e5a6faf8-1b88-4546-bc14-e155fa324d05');
        $attendance->setAttribute('user_id', '852a39e8-6e15-4e37-ac43-0da7a93fcd45');
        $attendance->setAttribute('status', Attendance::STATUS_ACTIVE);
        $attendance->setAttribute('clock_in_time', '2026-08-24 16:00:00');
        $attendance->setAttribute('clock_out_time', null);
        $attendance->setAttribute('timezone', 'UTC');
        $attendance->setAttribute('location_tracking', $trackingPoints);
        $attendance->exists = true;

        return $attendance;
    }

    private function endShift(Attendance $attendance, AttendanceConstraint $constraint): void
    {
        $service = new LocationConstraintService(
            $this->attendanceService,
            $this->createMock(RadiusEnforcementService::class),
            $this->createMock(TaskService::class),
            $this->presenceService,
        );

        $method = new ReflectionMethod($service, 'endShiftOutsideZone');
        $method->setAccessible(true);
        $method->invoke($service, $attendance, $constraint, self::ALLOWED_LOCATIONS);
    }
}
