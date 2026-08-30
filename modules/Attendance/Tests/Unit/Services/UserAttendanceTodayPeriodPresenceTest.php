<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\UserAttendanceService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Today is the screen that offers the clock-in button. A real punch in the
 * allowed window must attach to the period so the payload cannot say
 * is_absent / attendance [] while the calendar (INV-16) shows حاضر.
 */
class UserAttendanceTodayPeriodPresenceTest extends TestCase
{
    private UserAttendanceService $service;

    private ReflectionMethod $enhance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserAttendanceService(
            $this->createMock(AttendanceConstraintService::class),
            $this->createMock(AttendanceService::class)
        );

        $tz = new ReflectionProperty(UserAttendanceService::class, 'requestTimezoneOverride');
        $tz->setAccessible(true);
        $tz->setValue($this->service, 'Asia/Riyadh');

        $this->enhance = new ReflectionMethod(UserAttendanceService::class, 'enhancePeriodsWithAttendance');
        $this->enhance->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_early_clock_in_attaches_to_period_and_is_not_absent_after_deadline(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 16:55:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'clock_in_time' => '2026-08-30 08:00:00',
                'clock_out_time' => '2026-08-30 15:04:00',
                'start_time' => '2026-08-30 08:30:00',
                'end_time' => '2026-08-30 17:30:00',
                'status' => Attendance::STATUS_COMPLETED,
                'total_work_hours' => 7.07,
            ]),
        ]));

        $this->assertFalse($periods[0]['is_absent']);
        $this->assertNotEmpty($periods[0]['attendance']);
        $this->assertSame('08:00', $periods[0]['attendance'][0]['clock_in_time']);
        $this->assertSame(7.07, $periods[0]['total_hours_present']);
        $this->assertSame('manual', $periods[0]['attendance'][0]['clock_out_cause']);
        $this->assertNull($periods[0]['attendance'][0]['shift_end_method']);
    }

    public function test_attendance_object_exposes_auto_clock_out_cause_columns(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 19:10:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'id' => 'att-flex-1',
                'clock_in_time' => '2026-08-30 11:00:00',
                'clock_out_time' => '2026-08-30 19:00:00',
                'start_time' => '2026-08-30 11:00:00',
                'end_time' => '2026-08-30 23:59:59',
                'status' => Attendance::STATUS_COMPLETED,
                'total_work_hours' => 8.0,
                'shift_end_method' => 'auto_max_ot',
                'expected_clock_out_time' => '2026-08-30 19:00:00',
                'clock_out_location' => [
                    'latitude' => 21.62870000,
                    'longitude' => 39.12831480,
                ],
                'notes' => "[Auto] Clock-out: auto_max_ot at 2026-08-30T19:00:00+03:00",
            ]),
        ]));

        $row = $periods[0]['attendance'][0];
        $this->assertSame('att-flex-1', $row['id']);
        $this->assertSame('auto_max_ot', $row['clock_out_cause']);
        $this->assertSame('auto_max_ot', $row['shift_end_method']);
        $this->assertSame('2026-08-30 19:00:00', $row['expected_clock_out_time']);
        $this->assertSame([
            'latitude' => 21.62870000,
            'longitude' => 39.12831480,
        ], $row['clock_out_location']);
        $this->assertSame('[Auto] Clock-out: auto_max_ot at 2026-08-30T19:00:00+03:00', $row['notes']);
    }

    public function test_legacy_out_zone_row_fills_clock_out_location_from_tracking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 18:47:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'id' => 'eb771932-cf60-4774-9be7-c24bab56e036',
                'clock_in_time' => '2026-08-30 11:11:00',
                'clock_out_time' => '2026-08-30 11:13:03',
                'start_time' => '2026-08-30 00:00:00',
                'end_time' => '2026-08-30 23:59:59',
                'status' => Attendance::STATUS_COMPLETED,
                'shift_end_method' => 'auto_out_zone',
                'clock_out_location' => null,
                'location_tracking' => [
                    [
                        'latitude' => 21.61000000,
                        'longitude' => 39.11000000,
                        'timestamp' => '2026-08-30 11:12:50',
                    ],
                    [
                        'latitude' => 21.99999999,
                        'longitude' => 39.99999999,
                        'timestamp' => '2026-08-30 12:00:00',
                    ],
                ],
                'notes' => '[2026-08-30 11:13:03] Auto-ended: Shift ended: outside all allowed work locations for 180 minutes (threshold: 30 minutes).',
            ]),
        ]));

        $this->assertSame([
            'latitude' => 21.61000000,
            'longitude' => 39.11000000,
        ], $periods[0]['attendance'][0]['clock_out_location']);
    }

    public function test_null_island_tracking_point_is_not_used_as_clock_out_location(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 18:47:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'clock_in_time' => '2026-08-30 16:08:00',
                'clock_out_time' => '2026-08-30 17:32:55',
                'clock_in_location' => [
                    'latitude' => 21.62671028806533,
                    'longitude' => 39.12821531295776,
                ],
                'start_time' => '2026-08-30 00:00:00',
                'end_time' => '2026-08-30 23:59:59',
                'status' => Attendance::STATUS_COMPLETED,
                'shift_end_method' => 'auto_out_zone',
                'clock_out_location' => null,
                'location_tracking' => [
                    [
                        'latitude' => 21.61769845055973,
                        'longitude' => 39.12814579677232,
                        'timestamp' => '2026-08-30 16:10:00',
                    ],
                    [
                        'latitude' => -0.01578874283118821,
                        'longitude' => 0.1815678982057548,
                        'timestamp' => '2026-08-30 17:32:50',
                    ],
                ],
            ]),
        ]));

        $this->assertSame([
            'latitude' => 21.61769845055973,
            'longitude' => 39.12814579677232,
        ], $periods[0]['attendance'][0]['clock_out_location']);
    }

    public function test_open_shift_does_not_invent_clock_out_location(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 18:47:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'clock_in_time' => '2026-08-30 18:12:00',
                'clock_out_time' => null,
                'start_time' => '2026-08-30 00:00:00',
                'end_time' => '2026-08-30 23:59:59',
                'status' => Attendance::STATUS_ACTIVE,
                'clock_out_location' => null,
                'location_tracking' => [
                    [
                        'latitude' => 21.61000000,
                        'longitude' => 39.11000000,
                        'timestamp' => '2026-08-30 18:20:00',
                    ],
                ],
            ]),
        ]));

        $this->assertNull($periods[0]['attendance'][0]['clock_out_location']);
    }

    public function test_early_clock_in_attaches_even_when_start_time_is_the_punch_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 16:55:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'clock_in_time' => '2026-08-30 08:00:00',
                'clock_out_time' => '2026-08-30 15:04:00',
                'start_time' => '2026-08-30 08:00:00',
                'end_time' => '2026-08-30 17:00:00',
                'status' => Attendance::STATUS_COMPLETED,
                'total_work_hours' => 7.07,
            ]),
        ]));

        $this->assertFalse($periods[0]['is_absent']);
        $this->assertNotEmpty($periods[0]['attendance']);
    }

    public function test_no_punch_after_deadline_is_absent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 16:55:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect());

        $this->assertTrue($periods[0]['is_absent']);
        $this->assertSame([], $periods[0]['attendance']);
        $this->assertFalse($periods[0]['can_clock_in']);
    }

    public function test_leftover_absent_row_does_not_hide_a_real_early_punch(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 16:55:00', 'Asia/Riyadh'));

        $periods = $this->enhancePeriods(collect([
            $this->punch([
                'clock_in_time' => null,
                'clock_out_time' => null,
                'start_time' => '2026-08-30 08:30:00',
                'end_time' => '2026-08-30 17:30:00',
                'status' => Attendance::STATUS_ABSENT,
                'total_work_hours' => 0,
            ]),
            $this->punch([
                'clock_in_time' => '2026-08-30 08:00:00',
                'clock_out_time' => '2026-08-30 15:04:00',
                'start_time' => '2026-08-30 08:30:00',
                'end_time' => '2026-08-30 17:30:00',
                'status' => Attendance::STATUS_COMPLETED,
                'total_work_hours' => 7.07,
            ]),
        ]));

        $this->assertFalse($periods[0]['is_absent']);
        $this->assertCount(1, $periods[0]['attendance']);
        $this->assertSame('08:00', $periods[0]['attendance'][0]['clock_in_time']);
    }

    /**
     * @param Collection<int, Attendance> $attendances
     * @return list<array<string, mixed>>
     */
    private function enhancePeriods(Collection $attendances): array
    {
        $date = Carbon::parse('2026-08-30', 'Asia/Riyadh');
        $earlyRules = [
            'early_period' => 30,
            'early_unit' => 'minutes',
            'prevent_early_clock_in' => false,
        ];
        $workRules = [
            'attendance_type' => 'regular',
            'early_clock_in_minutes' => 30,
            'early_clock_in_rules' => $earlyRules,
            'extension_minutes' => 120,
            'can_clock_in_before_minutes' => 120,
        ];

        return $this->enhance->invoke(
            $this->service,
            [[
                'status' => 'scheduled',
                'start_time' => '08:30',
                'end_time' => '17:30',
                'extends_to_next_day' => false,
            ]],
            $attendances,
            $date,
            $earlyRules,
            null,
            $workRules
        );
    }

    /** @param array<string, mixed> $attributes */
    private function punch(array $attributes): Attendance
    {
        $attendance = new Attendance();
        $attendance->timezone = 'Asia/Riyadh';
        foreach ($attributes as $key => $value) {
            $attendance->{$key} = $value;
        }

        return $attendance;
    }
}
