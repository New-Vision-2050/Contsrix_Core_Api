<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Contracts\OutOfZoneClockOutExemption;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\LocationConstraintService;
use Modules\Attendance\Services\RadiusEnforcementService;
use Modules\Attendance\Services\TaskService;
use PHPUnit\Framework\TestCase;

class ValidateMultiLocationOutZoneTest extends TestCase
{
    private AttendanceService $attendanceService;
    private LocationConstraintService $service;

    private const WORK_LAT = 21.62689766;
    private const WORK_LON = 39.12831480;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-25 16:00:00', 'Asia/Riyadh'));

        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->service = new LocationConstraintService(
            $this->attendanceService,
            $this->createMock(RadiusEnforcementService::class),
            $this->createMock(TaskService::class),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_inside_any_allowed_location_passes(): void
    {
        $attendance = $this->makeActiveAttendance([
            [
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'timestamp' => '2026-08-25 15:50:00',
            ],
        ]);

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 100,
            ],
            [
                'name' => 'Additional',
                'latitude' => 21.63000000,
                'longitude' => 39.13000000,
                'radius' => 50,
            ],
        ], 30);

        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $result = $this->service->validateMultiLocation($attendance, $constraint);

        $this->assertFalse($result);
    }

    public function test_outside_within_grace_does_not_clock_out(): void
    {
        // ~200m away, continuous outside for 10 minutes (< 30)
        $attendance = $this->makeActiveAttendance([
            [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
                'timestamp' => '2026-08-25 15:50:00',
            ],
            [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
                'timestamp' => '2026-08-25 16:00:00',
            ],
        ]);

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 50,
            ],
        ], 30);

        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $result = $this->service->validateMultiLocation($attendance, $constraint);

        $this->assertFalse($result);
    }

    public function test_outside_beyond_grace_auto_clocks_out(): void
    {
        // Continuous outside for 35 minutes (>= 30)
        $attendance = $this->makeActiveAttendance([
            [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
                'timestamp' => '2026-08-25 15:25:00',
            ],
            [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
                'timestamp' => '2026-08-25 16:00:00',
            ],
        ]);
        $attendance->id = 'c5778c77-b689-44e3-a634-fcab3c044ea8';

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 50,
            ],
        ], 30);

        $this->attendanceService
            ->expects($this->once())
            ->method('endShiftAutomatically')
            ->with(
                'c5778c77-b689-44e3-a634-fcab3c044ea8',
                'auto_out_zone',
                $this->stringContains('threshold: 30 minutes')
            );

        $result = $this->service->validateMultiLocation($attendance, $constraint);

        $this->assertIsArray($result);
        $this->assertSame('auto_out_zone', $result['details']['enforcement_action']);
        $this->assertGreaterThanOrEqual(30, $result['details']['minutes_outside']);
    }

    /**
     * An accepted task or sent/accepted project notification that day means the
     * employee is expected at a field site, so out-of-zone must not close the shift.
     */
    public function test_field_assignment_on_this_day_skips_out_of_zone_clock_out(): void
    {
        $attendance = $this->makeActiveAttendance([
            [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
                'timestamp' => '2026-08-25 15:25:00',
            ],
            [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
                'timestamp' => '2026-08-25 16:00:00',
            ],
        ]);
        $attendance->id = 'c5778c77-b689-44e3-a634-fcab3c044ea8';

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 50,
            ],
        ], 30);

        $exemption = $this->createMock(OutOfZoneClockOutExemption::class);
        $exemption->method('appliesTo')->willReturn(true);

        $service = new LocationConstraintService(
            $this->attendanceService,
            $this->createMock(RadiusEnforcementService::class),
            $this->createMock(TaskService::class),
            $exemption,
        );

        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $this->assertFalse($service->validateMultiLocation($attendance, $constraint));
    }

    public function test_additional_location_keeps_employee_inside(): void
    {
        // Far from work site, but inside additional location
        $attendance = $this->makeActiveAttendance([
            [
                'latitude' => 21.63000000,
                'longitude' => 39.13000000,
                'timestamp' => '2026-08-25 15:00:00',
            ],
        ]);

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 50,
            ],
            [
                'name' => 'Additional site',
                'latitude' => 21.63000000,
                'longitude' => 39.13000000,
                'radius' => 100,
            ],
        ], 30);

        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $result = $this->service->validateMultiLocation($attendance, $constraint);

        $this->assertFalse($result);
    }

    public function test_clock_in_from_outside_rejected_immediately(): void
    {
        // Unsaved attendance = clock-in dry-run
        $attendance = new Attendance([
            'clock_in_time' => '2026-08-25 16:00:00',
            'clock_in_location' => [
                'latitude' => 21.62870000,
                'longitude' => 39.12831480,
            ],
            'timezone' => 'Asia/Riyadh',
        ]);

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 50,
            ],
        ], 30);

        $this->attendanceService->expects($this->never())->method('endShiftAutomatically');

        $result = $this->service->validateMultiLocation($attendance, $constraint);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('enforcement_action', $result['details']);
    }

    public function test_distance_uses_metres_not_kilometres(): void
    {
        // ~9 km away must be outside a 100 m radius (old bug treated radius as km)
        $attendance = $this->makeActiveAttendance([
            [
                'latitude' => 21.55372,
                'longitude' => 39.166215,
                'timestamp' => '2026-08-25 15:00:00',
            ],
            [
                'latitude' => 21.55372,
                'longitude' => 39.166215,
                'timestamp' => '2026-08-25 16:00:00',
            ],
        ]);
        $attendance->id = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

        $constraint = $this->makeConstraint([
            [
                'name' => 'Work',
                'latitude' => self::WORK_LAT,
                'longitude' => self::WORK_LON,
                'radius' => 100,
            ],
        ], 30);

        $this->attendanceService
            ->expects($this->once())
            ->method('endShiftAutomatically');

        $result = $this->service->validateMultiLocation($attendance, $constraint);

        $this->assertIsArray($result);
    }

    private function makeActiveAttendance(array $tracking): Attendance
    {
        $attendance = $this->getMockBuilder(Attendance::class)
            ->onlyMethods(['isActive'])
            ->getMock();

        $attendance->method('isActive')->willReturn(true);
        $attendance->exists = true;
        $attendance->clock_in_time = '2026-08-25 08:00:00';
        $attendance->clock_out_time = null;
        $attendance->timezone = 'Asia/Riyadh';
        $attendance->location_tracking = $tracking;
        $attendance->clock_in_location = $tracking[0] ?? null;

        return $attendance;
    }

    private function makeConstraint(array $locations, int $outZoneMinutes): AttendanceConstraint
    {
        $constraint = new AttendanceConstraint();
        $constraint->constraint_name = 'multi_location';
        $constraint->constraint_config = [
            'severity' => 'high',
            'time_rules' => [
                'out_zone_rules' => ['duration_minutes' => $outZoneMinutes],
            ],
        ];
        $constraint->branch_locations = $locations;
        $constraint->out_zone_minutes = $outZoneMinutes;
        $constraint->out_zone_rules = ['duration_minutes' => $outZoneMinutes];

        return $constraint;
    }
}
