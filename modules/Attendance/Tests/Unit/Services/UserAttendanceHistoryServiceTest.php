<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\UserAttendanceHistoryService;
use Modules\Attendance\Services\UserAttendanceService;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\Attendance\Support\ScheduledWorkDays;
use Modules\User\Models\User;
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
            $this->createMock(AttendanceConstraintService::class),
            $this->createMock(UserAttendanceService::class)
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

    public function test_early_clock_in_with_sibling_absent_period_is_not_absent(): void
    {
        // Early clock-in on one period + leftover absent on another must not show غائب.
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_ABSENT,
                'is_absent' => 1,
                'start_time' => '2026-08-09 08:30:00',
                'end_time' => '2026-08-09 17:30:00',
                'clock_in_time' => null,
                'timezone' => 'Asia/Riyadh',
            ]),
            new Attendance([
                'status' => Attendance::STATUS_ACTIVE,
                'is_absent' => 0,
                'start_time' => '2026-08-09 08:30:00',
                'end_time' => '2026-08-09 17:30:00',
                'clock_in_time' => '2026-08-09 08:15:00',
                'clock_out_time' => null,
                'timezone' => 'Asia/Riyadh',
            ]),
        ]));

        $this->assertSame('نشط', $payload['status']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_clocked_in_row_with_stale_absent_flag_is_not_absent(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_ACTIVE,
                'is_absent' => 1,
                'start_time' => '2026-08-09 08:30:00',
                'clock_in_time' => '2026-08-09 08:15:00',
                'clock_out_time' => null,
                'timezone' => 'Asia/Riyadh',
            ]),
        ]));

        $this->assertSame('نشط', $payload['status']);
        $this->assertSame(0, $payload['is_absent']);
    }

    public function test_late_attendance_sets_late_flag(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_COMPLETED,
                'start_time' => '2026-06-09 09:00:00',
                'clock_in_time' => '2026-06-09 09:15:00',
                'clock_out_time' => '2026-06-09 17:00:00',
                'timezone' => 'Asia/Riyadh',
                'is_late' => 1,
            ]),
        ]));

        $this->assertSame('متأخر', $payload['status']);
        $this->assertSame(1, $payload['is_late']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_on_time_clock_in_with_early_departure_is_not_late(): void
    {
        // Stale is_late=1 must not override clock_in == shift start.
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_COMPLETED,
                'start_time' => '2026-08-03 07:30:00',
                'end_time' => '2026-08-03 16:30:00',
                'clock_in_time' => '2026-08-03 07:30:00',
                'clock_out_time' => '2026-08-03 16:12:00',
                'timezone' => 'Asia/Riyadh',
                'is_late' => 1,
                'is_early_departure' => 1,
            ]),
        ]));

        $this->assertSame('تم الخروج', $payload['status']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_normal_completed_attendance_keeps_flags_clear(): void
    {
        $payload = $this->dayStatusPayload(collect([
            new Attendance([
                'status' => Attendance::STATUS_COMPLETED,
                'start_time' => '2026-06-09 09:00:00',
                'clock_in_time' => '2026-06-09 09:00:00',
                'clock_out_time' => '2026-06-09 17:00:00',
                'timezone' => 'Asia/Riyadh',
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

    public function test_day_stays_awaiting_clock_in_while_the_deadline_is_ahead(): void
    {
        $this->assertTrue($this->isStillAwaitingClockIn(
            '2026-08-25T08:15:00+03:00',
            Carbon::parse('2026-08-25T07:40:00+03:00')
        ));
    }

    public function test_day_is_settled_once_the_clock_in_deadline_has_passed(): void
    {
        $this->assertFalse($this->isStillAwaitingClockIn(
            '2026-08-25T08:15:00+03:00',
            Carbon::parse('2026-08-25T09:30:00+03:00')
        ));
    }

    public function test_past_day_is_settled_without_consulting_constraints(): void
    {
        $userAttendanceService = $this->createMock(UserAttendanceService::class);
        $userAttendanceService->expects($this->never())->method('getUserConstraints');

        $this->assertFalse($this->isStillAwaitingClockIn(
            '2026-08-20T08:15:00+03:00',
            Carbon::parse('2026-08-25T09:30:00+03:00'),
            '2026-08-20',
            'work_day',
            null,
            $userAttendanceService
        ));
    }

    public function test_non_work_day_is_never_awaiting_clock_in(): void
    {
        $this->assertFalse($this->isStillAwaitingClockIn(
            '2026-08-25T08:15:00+03:00',
            Carbon::parse('2026-08-25T07:40:00+03:00'),
            '2026-08-25',
            'day_off_or_weekend'
        ));
    }

    /**
     * A flexible employee has no first-clock-in deadline, so `can_clock_in_until` is end of
     * day and the whole day stays مطلوب للحضور until then.
     */
    public function test_flexible_day_is_awaiting_clock_in_until_end_of_day(): void
    {
        $flexiblePeriod = [
            'start_time' => '00:00',
            'end_time' => '23:59',
            'total_work_hours' => 9.0,
            'attendance_type' => 'flexible',
            'can_clock_in_until' => '2026-08-25T23:59:59+03:00',
        ];

        $this->assertTrue($this->isStillAwaitingClockIn(
            '2026-08-25T23:59:59+03:00',
            Carbon::parse('2026-08-25T22:45:00+03:00'),
            '2026-08-25',
            'work_day',
            $flexiblePeriod
        ));
    }

    public function test_pre_created_absent_row_reads_as_required_before_the_deadline(): void
    {
        $payload = $this->dayStatusPayloadForToday(true);

        $this->assertSame('مطلوب للحضور', $payload['status']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame(0, $payload['is_late']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    public function test_pre_created_absent_row_reads_as_absent_after_the_deadline(): void
    {
        $payload = $this->dayStatusPayloadForToday(false);

        $this->assertSame('غائب', $payload['status']);
        $this->assertSame(1, $payload['is_absent']);
    }

    /**
     * The reported case: PATCH /sub_entities/records/attendance-status with
     * status = holiday over 2026-08-25 .. 2026-09-03. Those dates are the employee's own
     * time off, so history owes إجازة, not the عطلة it used to return.
     */
    public function test_override_window_reads_as_leave_on_a_scheduled_work_day(): void
    {
        $payload = $this->classifyDay('2026-08-25', ['tuesday' => true], true);

        $this->assertSame('إجازة', $payload['status']);
        $this->assertSame(1, $payload['is_holiday']);
        $this->assertSame(0, $payload['is_absent']);
    }

    /**
     * عطلة belongs to the schedule, so a weekend keeps it even while an override window
     * covers the date — a day the employee never works cannot spend a leave day.
     */
    public function test_weekend_inside_the_override_window_stays_a_day_off(): void
    {
        // 2026-08-28 is a Friday.
        $payload = $this->classifyDay('2026-08-28', ['friday' => false], true);

        $this->assertSame('عطلة', $payload['status']);
        $this->assertSame(1, $payload['is_holiday']);
    }

    /**
     * Past date_to the employee is back on their constraint, and the holiday rows the
     * override left behind must not keep the day pinned to عطلة.
     */
    public function test_day_after_the_override_window_falls_back_to_the_constraint(): void
    {
        $leftoverRow = new Attendance([
            'status'     => Attendance::STATUS_HOLIDAY,
            'day_status' => 'holiday',
            'is_holiday' => 1,
            'notes'      => ManualAttendanceStatus::HOLIDAY_ROW_NOTE,
        ]);

        $payload = $this->classifyDay('2026-09-04', ['friday' => true], false, collect([$leftoverRow]));

        $this->assertSame('غائب', $payload['status']);
        $this->assertSame(1, $payload['is_absent']);
        $this->assertSame(0, $payload['is_holiday']);
    }

    /**
     * A company-wide public holiday is not written by the override endpoint and is not in
     * the constraint either, so it is still recognised from its attendance row — عطلة.
     */
    public function test_public_holiday_row_on_a_scheduled_work_day_is_a_day_off(): void
    {
        $publicHolidayRow = new Attendance([
            'status'     => Attendance::STATUS_HOLIDAY,
            'day_status' => 'holiday',
            'is_holiday' => 1,
            'notes'      => 'Auto-generated holiday record: National Day',
        ]);

        $payload = $this->classifyDay('2026-09-23', ['wednesday' => true], false, collect([$publicHolidayRow]));

        $this->assertSame('عطلة', $payload['status']);
        $this->assertSame(1, $payload['is_holiday']);
    }

    /**
     * @param array<string, bool> $enabledWeekdays
     * @param Collection<int, Attendance>|null $attendances
     * @return array{status: string, is_late: int, is_absent: int, is_holiday: int}
     */
    private function classifyDay(
        string $dateString,
        array $enabledWeekdays,
        bool $overrideActive,
        ?Collection $attendances = null
    ): array {
        $weekly = [];
        foreach ($enabledWeekdays as $weekday => $enabled) {
            $weekly[$weekday] = ['enabled' => $enabled, 'periods' => []];
        }

        $constraint = new AttendanceConstraint();
        $constraint->constraint_config = ['time_rules' => ['weekly_schedule' => $weekly]];

        return $this->buildDayStatusPayload->invoke(
            $this->service,
            $attendances ?? collect(),
            $overrideActive,
            new User(),
            $dateString,
            'Asia/Riyadh',
            ScheduledWorkDays::fromConstraint($constraint)
        );
    }

    /**
     * @param array<string, mixed>|null $period
     */
    private function isStillAwaitingClockIn(
        string $canClockInUntil,
        Carbon $now,
        string $dateString = '2026-08-25',
        string $dayStatus = 'work_day',
        ?array $period = null,
        ?UserAttendanceService $userAttendanceService = null
    ): bool {
        $period ??= [
            'start_time' => '08:00',
            'end_time' => '16:00',
            'total_work_hours' => 8.0,
            'can_clock_in_until' => $canClockInUntil,
        ];

        if ($userAttendanceService === null) {
            $userAttendanceService = $this->createMock(UserAttendanceService::class);
            $userAttendanceService->method('getUserConstraints')->willReturn([
                'work_rules' => [
                    'day_status' => $dayStatus,
                    'all_work_periods' => [$period],
                ],
            ]);
        }

        $service = new UserAttendanceHistoryService(
            $this->createMock(AttendanceConstraintService::class),
            $userAttendanceService
        );

        $method = new ReflectionMethod($service, 'isStillAwaitingClockIn');
        $method->setAccessible(true);

        return $method->invoke($service, new User(), $dateString, $now, 'Asia/Riyadh');
    }

    /**
     * Today holding only a pre-created absent row, with the clock-in deadline either still
     * ahead of now or already gone.
     *
     * @return array{status: string, is_late: int, is_absent: int, is_holiday: int}
     */
    private function dayStatusPayloadForToday(bool $deadlineAhead): array
    {
        $timezone = 'Asia/Riyadh';
        $now = Carbon::now($timezone);
        $deadline = $deadlineAhead ? $now->copy()->addHour() : $now->copy()->subHour();

        $userAttendanceService = $this->createMock(UserAttendanceService::class);
        $userAttendanceService->method('getUserConstraints')->willReturn([
            'work_rules' => [
                'day_status' => 'work_day',
                'all_work_periods' => [['can_clock_in_until' => $deadline->toIso8601String()]],
            ],
        ]);

        $service = new UserAttendanceHistoryService(
            $this->createMock(AttendanceConstraintService::class),
            $userAttendanceService
        );

        $method = new ReflectionMethod($service, 'buildDayStatusPayload');
        $method->setAccessible(true);

        return $method->invoke(
            $service,
            collect([
                new Attendance([
                    'status' => Attendance::STATUS_ABSENT,
                    'is_absent' => 1,
                    'start_time' => $now->copy()->startOfDay()->addHours(8)->format('Y-m-d H:i:s'),
                    'clock_in_time' => null,
                    'timezone' => $timezone,
                ]),
            ]),
            false,
            new User(),
            $now->toDateString(),
            $timezone
        );
    }

    /**
     * @return array{status: string, is_late: int, is_absent: int, is_holiday: int}
     */
    private function dayStatusPayload(Collection $attendances): array
    {
        return $this->buildDayStatusPayload->invoke($this->service, $attendances);
    }
}
