<?php

declare(strict_types=1);

namespace Modules\Reports\Tests\Unit\Services;

use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\PublicHolidayCalendarService;
use Modules\Attendance\Support\ManualAttendanceOverrideSet;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\Attendance\Support\PublicHolidayDates;
use Modules\Attendance\Support\ScheduledWorkDays;
use Modules\Reports\Services\ReportDataExtractionService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A single business_date can hold several attendance rows: one per scheduled period, plus
 * rows the absence sweep flipped before the employee arrived. The attendance & absence
 * report used to freeze the day status on whichever row sorted first, which rendered a
 * worked day as غياب while still printing its clock in/out times.
 */
class ReportAttendanceDayStatusTest extends TestCase
{
    private ReportDataExtractionService $service;
    private ReflectionMethod $mergeDisplayStatus;
    private ReflectionMethod $holidayDisplayStatus;
    private ReflectionMethod $publicHolidayDisplayStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportDataExtractionService(
            $this->createMock(AttendanceConstraintService::class),
            $this->createMock(PublicHolidayCalendarService::class)
        );

        $this->mergeDisplayStatus = new ReflectionMethod($this->service, 'mergeDisplayStatus');
        $this->mergeDisplayStatus->setAccessible(true);

        $this->holidayDisplayStatus = new ReflectionMethod($this->service, 'holidayDisplayStatus');
        $this->holidayDisplayStatus->setAccessible(true);

        $this->publicHolidayDisplayStatus = new ReflectionMethod($this->service, 'publicHolidayDisplayStatus');
        $this->publicHolidayDisplayStatus->setAccessible(true);
    }

    /**
     * An official holiday is time off granted on a day the employee would otherwise have
     * worked, so the report prints إجازة rather than عطلة (INV-21).
     */
    public function test_official_public_holiday_on_a_scheduled_work_day_is_leave(): void
    {
        $this->assertSame('leave', $this->classifyPublicHoliday($this->plainRow(), '2026-08-27'));
    }

    /**
     * The holiday calendar is country-wide and knows nothing about one employee's
     * instruction, so an admin demanding attendance that date wins and the row is scored
     * from its punches like any other.
     */
    public function test_required_attendance_override_beats_a_public_holiday(): void
    {
        $row = (object) [
            'user_id'                        => 'user-1',
            'manual_attendance_status'       => ManualAttendanceStatus::REQUIRED_ATTENDANCE,
            'manual_attendance_status_since' => '2026-08-27',
            'manual_attendance_status_until' => '2026-08-27',
        ];

        $this->assertNull($this->classifyPublicHoliday($row, '2026-08-27'));
    }

    public function test_a_date_that_is_not_an_official_holiday_is_left_alone(): void
    {
        $this->assertNull($this->classifyPublicHoliday($this->plainRow(), '2026-08-28'));
    }

    public function test_clock_in_row_overrides_earlier_absent_row(): void
    {
        // Real case: 07:30 period row marked absent at the deadline, employee clocked in 08:44.
        $this->assertSame('present', $this->merge('absent', 'present'));
    }

    public function test_absent_row_does_not_override_clock_in_row(): void
    {
        $this->assertSame('present', $this->merge('present', 'absent'));
    }

    public function test_holiday_outranks_present_and_absent(): void
    {
        $this->assertSame('holiday', $this->merge('present', 'holiday'));
        $this->assertSame('holiday', $this->merge('holiday', 'present'));
        $this->assertSame('holiday', $this->merge('absent', 'holiday'));
    }

    public function test_day_without_any_clock_in_stays_absent(): void
    {
        $this->assertSame('absent', $this->merge('absent', 'absent'));
    }

    public function test_leave_outranks_holiday_on_the_same_date(): void
    {
        // A manual-leave row and a weekend row can coexist on one date; the grant wins.
        $this->assertSame('leave', $this->merge('holiday', 'leave'));
        $this->assertSame('leave', $this->merge('leave', 'holiday'));
    }

    /**
     * A holiday row with no override window covering it is a weekend, a day off, or a
     * public holiday — عطلة, never إجازة. This is the PDF bug: every such row used to
     * print إجازة.
     */
    public function test_holiday_row_without_an_override_is_a_day_off(): void
    {
        $row = (object) [
            'user_id'                        => 'user-1',
            'manual_attendance_status'       => null,
            'manual_attendance_status_since' => null,
            'manual_attendance_status_until' => null,
        ];

        $this->assertSame('holiday', $this->classifyHoliday($row, '2026-08-28'));
    }

    /**
     * Holidays are read live from the holiday table now, so a row left behind by the removed
     * pre-writing command carries no authority: the day is scored present or absent from its
     * punches like any other.
     */
    public function test_row_left_by_the_removed_holiday_command_is_ignored(): void
    {
        $row = (object) [
            'user_id'                        => 'user-1',
            'notes'                          => 'Auto-generated holiday record: National Day',
            'manual_attendance_status'       => null,
            'manual_attendance_status_since' => null,
            'manual_attendance_status_until' => null,
        ];

        $this->assertNull($this->classifyHoliday($row, '2026-09-04'));
    }

    /**
     * Setting the override rewrote every row in its range and shortening the range does not
     * undo those writes. Past date_to the employee is back on their constraint, so the
     * leftover row must be ignored rather than printed as either إجازة or عطلة.
     */
    public function test_override_row_past_the_window_is_ignored(): void
    {
        $row = (object) [
            'user_id'                        => 'user-1',
            'notes'                          => ManualAttendanceStatus::HOLIDAY_ROW_NOTE,
            'manual_attendance_status'       => 'holiday',
            'manual_attendance_status_since' => '2026-08-25',
            'manual_attendance_status_until' => '2026-09-03',
        ];

        $this->assertNull($this->classifyHoliday($row, '2026-09-04'));
    }

    public function test_disjoint_holiday_ranges_are_leave_on_each_granted_day_only(): void
    {
        $row = (object) [
            'user_id'                        => 'user-1',
            'notes'                          => ManualAttendanceStatus::HOLIDAY_ROW_NOTE,
            'manual_attendance_status'       => 'holiday',
            'manual_attendance_status_since' => '2026-08-30',
            'manual_attendance_status_until' => '2026-08-30',
        ];

        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-27', '2026-08-27')
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-30', '2026-08-30');

        $cache = ['user-1' => ScheduledWorkDays::unknown()];
        $sets = ['user-1' => $set];

        $this->assertSame(
            'leave',
            $this->holidayDisplayStatus->invokeArgs($this->service, [$row, '2026-08-27', &$cache, $sets])
        );

        $this->assertNull(
            $this->holidayDisplayStatus->invokeArgs($this->service, [$row, '2026-08-28', &$cache, $sets])
        );
    }

    private function merge(string $current, string $incoming): string
    {
        return $this->mergeDisplayStatus->invoke($this->service, $current, $incoming);
    }

    private function classifyHoliday(object $row, string $date): ?string
    {
        $cache = [];

        return $this->holidayDisplayStatus->invokeArgs($this->service, [$row, $date, &$cache]);
    }

    /**
     * @return object A row carrying no manual override.
     */
    private function plainRow(): object
    {
        return (object) [
            'user_id'                        => 'user-1',
            'manual_attendance_status'       => null,
            'manual_attendance_status_since' => null,
            'manual_attendance_status_until' => null,
        ];
    }

    private function classifyPublicHoliday(object $row, string $date): ?string
    {
        $cache = ['user-1' => ScheduledWorkDays::unknown()];
        $holidays = ['user-1' => PublicHolidayDates::fromMap(['2026-08-27' => 'المولد النبوي الشريف'])];

        return $this->publicHolidayDisplayStatus->invokeArgs($this->service, [$row, $date, $holidays, &$cache]);
    }
}
