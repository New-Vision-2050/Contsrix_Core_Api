<?php

declare(strict_types=1);

namespace Modules\Reports\Tests\Unit\Services;

use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Support\ManualAttendanceStatus;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportDataExtractionService(
            $this->createMock(AttendanceConstraintService::class)
        );

        $this->mergeDisplayStatus = new ReflectionMethod($this->service, 'mergeDisplayStatus');
        $this->mergeDisplayStatus->setAccessible(true);

        $this->holidayDisplayStatus = new ReflectionMethod($this->service, 'holidayDisplayStatus');
        $this->holidayDisplayStatus->setAccessible(true);
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

    public function test_public_holiday_row_after_an_override_window_is_still_a_day_off(): void
    {
        $row = (object) [
            'user_id'                        => 'user-1',
            'notes'                          => 'Auto-generated holiday record: National Day',
            'manual_attendance_status'       => 'holiday',
            'manual_attendance_status_since' => '2026-08-25',
            'manual_attendance_status_until' => '2026-09-03',
        ];

        $this->assertSame('holiday', $this->classifyHoliday($row, '2026-09-04'));
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

    private function merge(string $current, string $incoming): string
    {
        return $this->mergeDisplayStatus->invoke($this->service, $current, $incoming);
    }

    private function classifyHoliday(object $row, string $date): ?string
    {
        $cache = [];

        return $this->holidayDisplayStatus->invokeArgs($this->service, [$row, $date, &$cache]);
    }
}
