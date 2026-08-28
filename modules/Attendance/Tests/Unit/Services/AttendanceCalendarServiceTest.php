<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceCalendarService;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\PublicHolidayCalendarService;
use Modules\Attendance\Services\UserAttendanceService;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\Attendance\Support\PublicHolidayDates;
use Modules\Attendance\Support\ScheduledWorkDays;
use Modules\EmployeeTask\Services\EmployeeTaskPresenceService;
use Modules\User\Models\User;
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
            $this->createMock(EmployeeTaskPresenceService::class),
            $this->createMock(PublicHolidayCalendarService::class),
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

    public function test_on_time_clock_in_with_early_departure_is_present_not_late(): void
    {
        $method = new ReflectionMethod($this->service, 'hasLateArrival');
        $method->setAccessible(true);

        $attendances = collect([
            $this->attendance([
                'start_time' => '2026-08-03 07:30:00',
                'end_time' => '2026-08-03 16:30:00',
                'clock_in_time' => '2026-08-03 07:30:00',
                'clock_out_time' => '2026-08-03 16:12:00',
                'timezone' => 'Asia/Riyadh',
                'is_late' => 1,
                'total_work_hours' => '8.69',
            ]),
        ]);

        $this->assertFalse($method->invoke($this->service, $attendances));
    }

    public function test_clock_in_after_shift_start_is_late(): void
    {
        $method = new ReflectionMethod($this->service, 'hasLateArrival');
        $method->setAccessible(true);

        $attendances = collect([
            $this->attendance([
                'start_time' => '2026-08-03 07:30:00',
                'clock_in_time' => '2026-08-03 07:45:00',
                'timezone' => 'Asia/Riyadh',
            ]),
        ]);

        $this->assertTrue($method->invoke($this->service, $attendances));
    }

    public function test_early_clock_in_presence_overrides_sibling_absent_flag(): void
    {
        $dayAttendances = collect([
            $this->attendance([
                'status' => Attendance::STATUS_ABSENT,
                'is_absent' => 1,
                'start_time' => '2026-08-09 08:30:00',
                'clock_in_time' => null,
                'timezone' => 'Asia/Riyadh',
            ]),
            $this->attendance([
                'status' => Attendance::STATUS_ACTIVE,
                'is_absent' => 0,
                'start_time' => '2026-08-09 08:30:00',
                'clock_in_time' => '2026-08-09 08:15:00',
                'clock_out_time' => null,
                'timezone' => 'Asia/Riyadh',
            ]),
        ]);

        $hasLateArrival = new ReflectionMethod($this->service, 'hasLateArrival');
        $hasLateArrival->setAccessible(true);

        $hasPresence = $dayAttendances->contains(fn ($a) => ! empty($a->clock_in_time));
        $hasAbsent = ! $hasPresence && $dayAttendances->contains(
            fn ($a) => (int) ($a->is_absent ?? 0) === 1 || ($a->status ?? null) === Attendance::STATUS_ABSENT
        );

        $this->assertTrue($hasPresence);
        $this->assertFalse($hasAbsent);
        $this->assertFalse($hasLateArrival->invoke($this->service, $dayAttendances));
    }

    public function test_today_stays_required_while_the_clock_in_deadline_is_still_ahead(): void
    {
        $pending = $this->resolvePendingClockInDay(
            '2026-08-25T08:15:00+03:00',
            Carbon::parse('2026-08-25T07:40:00+03:00')
        );

        $this->assertNotNull($pending);
        $this->assertSame(8.0, $pending['work_hours']);
    }

    public function test_today_becomes_absent_once_the_clock_in_deadline_has_passed(): void
    {
        $this->assertNull($this->resolvePendingClockInDay(
            '2026-08-25T08:15:00+03:00',
            Carbon::parse('2026-08-25T09:30:00+03:00')
        ));
    }

    public function test_non_work_day_is_never_reported_as_still_required(): void
    {
        $this->assertNull($this->resolvePendingClockInDay(
            '2026-08-25T08:15:00+03:00',
            Carbon::parse('2026-08-25T07:40:00+03:00'),
            'day_off_or_weekend'
        ));
    }

    /**
     * A flexible employee has no first-clock-in deadline, so ShiftWindowCalculator reports
     * end of day as `can_clock_in_until`. The whole day must stay مطلوب للحضور until then.
     */
    public function test_flexible_day_stays_required_until_end_of_day(): void
    {
        $flexiblePeriod = [
            'start_time' => '00:00',
            'end_time' => '23:59',
            'total_work_hours' => 9.0,
            'attendance_type' => 'flexible',
            'can_clock_in_until' => '2026-08-25T23:59:59+03:00',
            'attendance' => [],
        ];

        $lateEvening = $this->resolvePendingClockInDay(
            '2026-08-25T23:59:59+03:00',
            Carbon::parse('2026-08-25T22:45:00+03:00'),
            'work_day',
            $flexiblePeriod
        );

        $this->assertNotNull($lateEvening);
        $this->assertSame(9.0, $lateEvening['work_hours']);

        $this->assertNull($this->resolvePendingClockInDay(
            '2026-08-25T23:59:59+03:00',
            Carbon::parse('2026-08-26T00:05:00+03:00'),
            'work_day',
            $flexiblePeriod
        ));
    }

    /**
     * The reported case: PATCH /sub_entities/records/attendance-status with
     * status = holiday over 2026-08-25 .. 2026-09-03. Those dates are the employee's own
     * time off, so the calendar owes إجازة, not the عطلة it used to print.
     */
    public function test_override_window_reads_as_leave_on_a_scheduled_work_day(): void
    {
        $day = $this->buildDayData('2026-08-25', $this->userWithHolidayWindow(), ['tuesday' => true]);

        $this->assertSame('leave', $day['status_key']);
        $this->assertSame('إجازة', $day['status']);
    }

    /**
     * عطلة belongs to the schedule, so a weekend keeps it even while an override window
     * covers the date — a day the employee never works cannot spend a leave day.
     */
    public function test_weekend_inside_the_override_window_stays_a_day_off(): void
    {
        // 2026-08-28 is a Friday.
        $day = $this->buildDayData('2026-08-28', $this->userWithHolidayWindow(), ['friday' => false]);

        $this->assertSame('off', $day['status_key']);
        $this->assertSame('عطلة', $day['status']);
    }

    /**
     * Past date_to the employee is back on their constraint, and the holiday rows the
     * override left behind must not keep the day pinned to عطلة.
     */
    public function test_day_after_the_override_window_falls_back_to_the_constraint(): void
    {
        $leftoverRow = $this->attendance([
            'is_holiday' => 1,
            'day_status' => 'holiday',
            'status'     => Attendance::STATUS_HOLIDAY,
            'notes'      => ManualAttendanceStatus::HOLIDAY_ROW_NOTE,
            'start_time' => '2026-09-04 08:00:00',
        ]);

        $day = $this->buildDayData(
            '2026-09-04',
            $this->userWithHolidayWindow(),
            ['friday' => true],
            collect([$leftoverRow])
        );

        $this->assertSame('absent', $day['status_key']);
        $this->assertSame('غائب', $day['status']);
    }

    /**
     * Two holiday PATCHes are two ranges, not one window. The 27th stays إجازة after
     * the 30th is granted; the days in between do not.
     */
    public function test_earlier_override_holiday_stays_leave_after_a_later_holiday_is_set(): void
    {
        $user = new User();
        $user->setRelation('manualAttendanceOverrides', collect([
            (object) ['status' => ManualAttendanceStatus::HOLIDAY, 'starts_on' => '2026-08-27', 'ends_on' => '2026-08-27'],
            (object) ['status' => ManualAttendanceStatus::HOLIDAY, 'starts_on' => '2026-08-30', 'ends_on' => '2026-08-30'],
        ]));

        $aug27Row = $this->attendance([
            'is_holiday' => 1,
            'day_status' => 'holiday',
            'status'     => Attendance::STATUS_HOLIDAY,
            'notes'      => ManualAttendanceStatus::HOLIDAY_ROW_NOTE,
            'start_time' => '2026-08-27 08:00:00',
        ]);

        $day = $this->buildDayData(
            '2026-08-27',
            $user,
            ['thursday' => true],
            collect([$aug27Row])
        );

        $this->assertSame('leave', $day['status_key']);
        $this->assertSame('إجازة', $day['status']);

        $gap = $this->buildDayData('2026-08-28', $user, ['friday' => true]);
        $this->assertSame('absent', $gap['status_key']);
        $this->assertSame('غائب', $gap['status']);
    }

    /**
     * An official holiday is time off granted to the employee on a day they would otherwise
     * have worked, so it reads إجازة (INV-21).
     */
    public function test_official_public_holiday_on_a_scheduled_work_day_is_leave(): void
    {
        $day = $this->buildDayData(
            '2026-08-27',
            new User(),
            ['thursday' => true],
            null,
            PublicHolidayDates::fromMap(['2026-08-27' => 'المولد النبوي الشريف'])
        );

        $this->assertSame('leave', $day['status_key']);
        $this->assertSame('إجازة', $day['status']);
    }

    /**
     * The holiday calendar is country-wide and knows nothing about one employee's
     * instruction, so an admin demanding attendance that date wins and the day resolves from
     * the constraint (INV-21).
     */
    public function test_required_attendance_override_beats_a_public_holiday(): void
    {
        $day = $this->buildDayData(
            '2026-08-27',
            $this->userWithRequiredAttendanceOn('2026-08-27'),
            ['thursday' => true],
            null,
            PublicHolidayDates::fromMap(['2026-08-27' => 'المولد النبوي الشريف'])
        );

        $this->assertSame('absent', $day['status_key']);
        $this->assertSame('غائب', $day['status']);
    }

    /**
     * A day the schedule never works cannot be granted off, so the weekend keeps عطلة.
     */
    public function test_official_public_holiday_on_a_non_working_day_stays_a_day_off(): void
    {
        $day = $this->buildDayData(
            '2026-08-27',
            new User(),
            ['thursday' => false],
            null,
            PublicHolidayDates::fromMap(['2026-08-27' => 'المولد النبوي الشريف'])
        );

        $this->assertSame('off', $day['status_key']);
        $this->assertSame('عطلة', $day['status']);
    }

    /**
     * Holidays are now read live from the holiday table, so a row left behind by the removed
     * pre-writing command must not hold the day off on its own — the date resolves from the
     * constraint like any other.
     */
    public function test_row_left_by_the_removed_holiday_command_is_ignored(): void
    {
        $legacyRow = $this->attendance([
            'is_holiday' => 1,
            'day_status' => 'holiday',
            'status'     => Attendance::STATUS_HOLIDAY,
            'notes'      => 'Auto-generated holiday record: National Day',
            'start_time' => '2026-09-23 08:00:00',
        ]);

        $day = $this->buildDayData(
            '2026-09-23',
            new User(),
            ['wednesday' => true],
            collect([$legacyRow])
        );

        $this->assertSame('absent', $day['status_key']);
        $this->assertSame('غائب', $day['status']);
    }

    /**
     * Raw attributes: the date casts format through the connection on write, which a plain
     * PHPUnit TestCase has no container for.
     */
    private function userWithHolidayWindow(): User
    {
        return (new User())->setRawAttributes([
            'manual_attendance_status'       => ManualAttendanceStatus::HOLIDAY,
            'manual_attendance_status_since' => '2026-08-25',
            'manual_attendance_status_until' => '2026-09-03',
        ]);
    }

    private function userWithRequiredAttendanceOn(string $date): User
    {
        return (new User())->setRawAttributes([
            'manual_attendance_status'       => ManualAttendanceStatus::REQUIRED_ATTENDANCE,
            'manual_attendance_status_since' => $date,
            'manual_attendance_status_until' => $date,
        ]);
    }

    /**
     * @param array<string, bool> $enabledWeekdays
     * @param Collection<int, Attendance>|null $dayAttendances
     * @return array<string, mixed>
     */
    private function buildDayData(
        string $dateString,
        User $user,
        array $enabledWeekdays,
        ?Collection $dayAttendances = null,
        ?PublicHolidayDates $publicHolidays = null
    ): array {
        $weekly = [];
        foreach ($enabledWeekdays as $weekday => $enabled) {
            $weekly[$weekday] = ['enabled' => $enabled, 'periods' => []];
        }

        $constraint = new AttendanceConstraint();
        $constraint->constraint_config = ['time_rules' => ['weekly_schedule' => $weekly]];

        // A day the schedule works, with the clock-in deadline long gone, so anything that
        // is neither عطلة nor إجازة settles as غائب.
        $constraintService = $this->createMock(AttendanceConstraintService::class);
        $constraintService->method('getTodaysWorkRulesForUser')->willReturn([
            'day_status'       => 'work_day',
            'all_work_periods' => [['start_time' => '08:00', 'end_time' => '16:00', 'total_work_hours' => 8.0]],
        ]);

        $service = new AttendanceCalendarService(
            $constraintService,
            $this->createMock(UserAttendanceService::class),
            $this->createMock(EmployeeTaskPresenceService::class),
            $this->createMock(PublicHolidayCalendarService::class),
        );

        $method = new ReflectionMethod($service, 'buildDayData');
        $method->setAccessible(true);

        return $method->invoke(
            $service,
            $user,
            Carbon::parse($dateString, 'Asia/Riyadh'),
            $dateString,
            false,
            false,
            $dayAttendances ?? collect(),
            false,
            'Asia/Riyadh',
            Carbon::parse('2026-09-30T12:00:00+03:00'),
            ScheduledWorkDays::fromConstraint($constraint),
            $publicHolidays ?? PublicHolidayDates::none()
        );
    }

    /**
     * @param array<string, mixed>|null $period
     * @return array{work_hours: float|null}|null
     */
    private function resolvePendingClockInDay(
        string $canClockInUntil,
        Carbon $now,
        string $dayStatus = 'work_day',
        ?array $period = null
    ): ?array {
        $period ??= [
            'start_time' => '08:00',
            'end_time' => '16:00',
            'total_work_hours' => 8.0,
            'can_clock_in_until' => $canClockInUntil,
            'attendance' => [],
        ];

        $userAttendanceService = $this->createMock(UserAttendanceService::class);
        $userAttendanceService->method('getUserConstraints')->willReturn([
            'work_rules' => [
                'day_status' => $dayStatus,
                'all_work_periods' => [$period],
            ],
        ]);

        $service = new AttendanceCalendarService(
            $this->createMock(AttendanceConstraintService::class),
            $userAttendanceService,
            $this->createMock(EmployeeTaskPresenceService::class),
            $this->createMock(PublicHolidayCalendarService::class),
        );

        $method = new ReflectionMethod($service, 'resolvePendingClockInDay');
        $method->setAccessible(true);

        return $method->invoke($service, new User(), '2026-08-25', $now);
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
