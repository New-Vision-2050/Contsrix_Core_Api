<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\BehavioralConstraintServiceInterface;
use Modules\Attendance\Contracts\ComplianceConstraintServiceInterface;
use Modules\Attendance\Contracts\DeviceConstraintServiceInterface;
use Modules\Attendance\Contracts\LocationConstraintServiceInterface;
use Modules\Attendance\Contracts\RoleConstraintServiceInterface;
use Modules\Attendance\Contracts\SecurityConstraintServiceInterface;
use Modules\Attendance\Contracts\TimeConstraintServiceInterface;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\UserAttendanceService;
use Modules\Attendance\Support\FlexibleWorkDay;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\Attendance\Support\PublicHolidayDates;
use Modules\User\Models\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * `GET /attendance/user-constraint/today` is the screen that offers the clock-in button, so
 * it has to honour the holiday set through PATCH /sub_entities/records/attendance-status.
 * The window arithmetic now lives in ManualAttendanceStatus, shared with the calendar,
 * history and report readers (INV-18); these tests pin the endpoint's own behaviour so the
 * shared reader cannot regress it.
 */
class UserConstraintManualOverrideTest extends TestCase
{
    private UserAttendanceService $service;
    private ReflectionMethod $applyManualAttendanceOverride;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UserAttendanceService(
            $this->createMock(AttendanceConstraintService::class),
            $this->createMock(AttendanceService::class)
        );

        $this->applyManualAttendanceOverride = new ReflectionMethod($this->service, 'applyManualAttendanceOverride');
        $this->applyManualAttendanceOverride->setAccessible(true);
    }

    /** The reported window: status = holiday over 2026-08-25 .. 2026-09-03. */
    public function test_scheduled_work_day_inside_the_window_reports_holiday(): void
    {
        $rules = $this->applyOverride('2026-08-25');

        $this->assertSame('holiday', $rules['day_status']);
        $this->assertTrue($rules['is_holiday']);
        $this->assertSame('Manual holiday override.', $rules['reason']);
    }

    public function test_last_day_of_the_window_still_reports_holiday(): void
    {
        $this->assertSame('holiday', $this->applyOverride('2026-09-03')['day_status']);
    }

    /**
     * A constraint holiday returns no periods, so a manual one must not either — otherwise
     * the periods still carry can_clock_in and the app offers a clock-in button on a day it
     * has just been told is a holiday.
     */
    public function test_holiday_clears_the_work_periods(): void
    {
        $rules = $this->applyOverride('2026-08-25');

        $this->assertSame([], $rules['all_work_periods']);
        $this->assertNull($rules['current_work_period']);
    }

    public function test_day_after_date_to_falls_back_to_the_constraint(): void
    {
        $rules = $this->applyOverride('2026-09-04');

        $this->assertSame('work_day', $rules['day_status']);
        $this->assertFalse($rules['is_holiday']);
        $this->assertSame('Scheduled working day.', $rules['reason']);
    }

    public function test_day_before_date_from_falls_back_to_the_constraint(): void
    {
        $this->assertSame('work_day', $this->applyOverride('2026-08-24')['day_status']);
    }

    public function test_open_ended_window_keeps_reporting_holiday(): void
    {
        $rules = $this->applyOverride('2027-03-01', ManualAttendanceStatus::HOLIDAY, '2026-08-25', null);

        $this->assertSame('holiday', $rules['day_status']);
    }

    /**
     * required_attendance is the inverse override: it forces a work day even where the
     * constraint would have said otherwise.
     */
    public function test_required_attendance_override_forces_a_work_day(): void
    {
        $rules = $this->applyOverride(
            '2026-08-28',
            ManualAttendanceStatus::REQUIRED_ATTENDANCE,
            '2026-08-25',
            '2026-09-03',
            ['day_status' => 'day_off_or_weekend', 'is_holiday' => true, 'reason' => 'Scheduled weekend or non-working day.']
        );

        $this->assertSame('work_day', $rules['day_status']);
        $this->assertFalse($rules['is_holiday']);
        $this->assertSame('Manual required-attendance override.', $rules['reason']);
    }

    public function test_user_without_an_override_is_left_untouched(): void
    {
        $rules = $this->applyManualAttendanceOverride->invoke($this->service, null, $this->workDayRules());

        $this->assertSame($this->workDayRules(), $rules);
    }

    /**
     * The override runs before the flexible all-day window is built. That builder bails out
     * on any non-work day, so a flexible employee's holiday is not overwritten with a
     * 00:00–23:59 period — which would put the clock-in button back on screen.
     */
    public function test_flexible_employee_keeps_the_holiday(): void
    {
        $rules = FlexibleWorkDay::applyToWorkRules(
            $this->applyOverride('2026-08-25'),
            '2026-08-25',
            'Asia/Riyadh'
        );

        $this->assertSame('holiday', $rules['day_status']);
        $this->assertTrue($rules['is_holiday']);
        $this->assertSame('flexible', $rules['attendance_type']);
        // No fabricated all-day period: nothing to clock into.
        $this->assertSame([], $rules['all_work_periods']);
        $this->assertNull($rules['current_work_period']);
    }

    /**
     * A required-attendance override on a date that is also an official public holiday.
     *
     * The holiday empties the periods, and `applyManualAttendanceOverride` can flip
     * `day_status` back to `work_day` but cannot rebuild them. Resolving the override first
     * and suppressing the holiday is what keeps a period — and therefore a
     * `can_clock_in_until` — on the day the admin demanded attendance (INV-21).
     */
    public function test_required_attendance_beats_a_public_holiday_and_keeps_its_periods(): void
    {
        $rules = $this->todayRules(ManualAttendanceStatus::REQUIRED_ATTENDANCE);

        $this->assertSame('work_day', $rules['day_status']);
        $this->assertFalse($rules['is_holiday']);
        $this->assertSame('Manual required-attendance override.', $rules['reason']);
        $this->assertCount(1, $rules['all_work_periods']);
        $this->assertNotNull($rules['current_work_period']);
    }

    public function test_a_public_holiday_closes_the_day_when_no_override_applies(): void
    {
        $rules = $this->todayRules(null);

        $this->assertSame('holiday', $rules['day_status']);
        $this->assertTrue($rules['is_holiday']);
        $this->assertSame('المولد النبوي الشريف', $rules['reason']);
        $this->assertSame([], $rules['all_work_periods']);
    }

    public function test_a_holiday_override_on_a_public_holiday_still_closes_the_day(): void
    {
        $rules = $this->todayRules(ManualAttendanceStatus::HOLIDAY);

        $this->assertSame('holiday', $rules['day_status']);
        $this->assertSame([], $rules['all_work_periods']);
    }

    /**
     * Mirrors the order `getUserConstraints` composes the two overrides in: resolve the
     * manual status, build the rules (public holiday applied unless required attendance
     * suppresses it), then apply the manual status.
     *
     * @return array<string, mixed>
     */
    private function todayRules(?string $status): array
    {
        $date = '2026-08-27';
        $user = $status === null
            ? new User()
            : $this->userWithWindow($status, $date, $date);

        $override = ManualAttendanceStatus::activeOn($user, $date);

        $publicHolidays = $override === ManualAttendanceStatus::REQUIRED_ATTENDANCE
            ? PublicHolidayDates::none()
            : PublicHolidayDates::fromMap([$date => 'المولد النبوي الشريف']);

        $constraintService = new AttendanceConstraintService(
            $this->createMock(TimeConstraintServiceInterface::class),
            $this->createMock(LocationConstraintServiceInterface::class),
            $this->createMock(DeviceConstraintServiceInterface::class),
            $this->createMock(RoleConstraintServiceInterface::class),
            $this->createMock(BehavioralConstraintServiceInterface::class),
            $this->createMock(SecurityConstraintServiceInterface::class),
            $this->createMock(ComplianceConstraintServiceInterface::class)
        );

        $applyPublicHoliday = new ReflectionMethod($constraintService, 'applyPublicHoliday');
        $applyPublicHoliday->setAccessible(true);

        $rules = $applyPublicHoliday->invoke(
            $constraintService,
            $this->workDayRules(),
            $user,
            Carbon::parse($date, 'Asia/Riyadh'),
            $publicHolidays
        );

        return $this->applyManualAttendanceOverride->invoke($this->service, $override, $rules);
    }

    /**
     * @param array<string, mixed>|null $workRules
     * @return array<string, mixed>
     */
    private function applyOverride(
        string $targetDate,
        string $status = ManualAttendanceStatus::HOLIDAY,
        string $since = '2026-08-25',
        ?string $until = '2026-09-03',
        ?array $workRules = null
    ): array {
        return $this->applyManualAttendanceOverride->invoke(
            $this->service,
            ManualAttendanceStatus::activeOn($this->userWithWindow($status, $since, $until), $targetDate),
            $workRules ?? $this->workDayRules()
        );
    }

    /**
     * Raw attributes: the date casts format through the connection on write, which a plain
     * PHPUnit TestCase has no container for.
     */
    private function userWithWindow(string $status, string $since, ?string $until): User
    {
        return (new User())->setRawAttributes([
            'manual_attendance_status'       => $status,
            'manual_attendance_status_since' => $since,
            'manual_attendance_status_until' => $until,
        ]);
    }

    /**
     * What getTodaysWorkRulesForUser returns for an ordinary scheduled day.
     *
     * @return array<string, mixed>
     */
    private function workDayRules(): array
    {
        return [
            'day_status'          => 'work_day',
            'is_holiday'          => false,
            'reason'              => 'Scheduled working day.',
            'total_work_hours'    => 8.0,
            'all_work_periods'    => [
                ['start_time' => '08:00', 'end_time' => '16:00', 'total_work_hours' => 8.0],
            ],
            'current_work_period' => ['start_time' => '08:00', 'end_time' => '16:00'],
        ];
    }
}
