<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\UserAttendanceService;
use Modules\Attendance\Support\FlexibleWorkDay;
use Modules\Attendance\Support\ManualAttendanceStatus;
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
        $rules = $this->applyManualAttendanceOverride->invoke(
            $this->service,
            new User(),
            '2026-08-25',
            $this->workDayRules()
        );

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
        // Raw attributes: the date casts format through the connection on write, which a
        // plain PHPUnit TestCase has no container for.
        $user = (new User())->setRawAttributes([
            'manual_attendance_status'       => $status,
            'manual_attendance_status_since' => $since,
            'manual_attendance_status_until' => $until,
        ]);

        return $this->applyManualAttendanceOverride->invoke(
            $this->service,
            $user,
            $targetDate,
            $workRules ?? $this->workDayRules()
        );
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
