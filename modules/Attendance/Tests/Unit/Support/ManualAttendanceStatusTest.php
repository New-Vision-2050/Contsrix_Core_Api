<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\Carbon;
use Modules\Attendance\Support\ManualAttendanceStatus;
use PHPUnit\Framework\TestCase;

/**
 * The window written by PATCH /sub_entities/records/attendance-status. Reading it in one
 * place is what lets the calendar, the history endpoint and the report PDF agree on which
 * dates are إجازة and which have fallen back to the employee's constraint.
 */
class ManualAttendanceStatusTest extends TestCase
{
    /** The payload the product reported against: 2026-08-25 through 2026-09-03. */
    public function test_holiday_applies_across_the_inclusive_window(): void
    {
        foreach (['2026-08-25', '2026-08-30', '2026-09-03'] as $date) {
            $this->assertTrue($this->isHoliday($date), $date . ' should be covered');
        }
    }

    public function test_dates_outside_the_window_fall_back_to_the_constraint(): void
    {
        $this->assertFalse($this->isHoliday('2026-08-24'));
        $this->assertFalse($this->isHoliday('2026-09-04'));
    }

    public function test_null_until_leaves_the_override_open_ended(): void
    {
        $this->assertSame(
            ManualAttendanceStatus::HOLIDAY,
            ManualAttendanceStatus::resolve('holiday', '2026-08-25', null, '2027-01-01')
        );
    }

    public function test_required_attendance_never_reads_as_holiday(): void
    {
        $this->assertFalse(
            ManualAttendanceStatus::isHolidayFor('required_attendance', '2026-08-25', null, '2026-08-26')
        );
        $this->assertSame(
            ManualAttendanceStatus::REQUIRED_ATTENDANCE,
            ManualAttendanceStatus::resolve('required_attendance', '2026-08-25', null, '2026-08-26')
        );
    }

    public function test_unknown_status_is_ignored(): void
    {
        $this->assertNull(ManualAttendanceStatus::resolve(null, null, null, '2026-08-25'));
        $this->assertNull(ManualAttendanceStatus::resolve('absent', null, null, '2026-08-25'));
    }

    /** The columns are date casts, so both Carbon instances and strings reach this code. */
    public function test_carbon_and_string_bounds_behave_alike(): void
    {
        $this->assertTrue(ManualAttendanceStatus::isHolidayFor(
            'holiday',
            Carbon::parse('2026-08-25'),
            Carbon::parse('2026-09-03'),
            '2026-08-25'
        ));
    }

    public function test_only_the_override_note_marks_a_row_as_override_written(): void
    {
        $this->assertTrue(ManualAttendanceStatus::isHolidayRow(ManualAttendanceStatus::HOLIDAY_ROW_NOTE));
        $this->assertFalse(ManualAttendanceStatus::isHolidayRow('Auto-generated holiday record.'));
        $this->assertFalse(ManualAttendanceStatus::isHolidayRow(null));
    }

    public function test_disjoint_ranges_on_the_user_are_read_independently(): void
    {
        $user = new \Modules\User\Models\User();
        $user->setRelation('manualAttendanceOverrides', collect([
            (object) ['status' => 'holiday', 'starts_on' => '2026-08-27', 'ends_on' => '2026-08-27'],
            (object) ['status' => 'holiday', 'starts_on' => '2026-08-30', 'ends_on' => '2026-08-30'],
        ]));

        $this->assertTrue(ManualAttendanceStatus::isHolidayOn($user, '2026-08-27'));
        $this->assertFalse(ManualAttendanceStatus::isHolidayOn($user, '2026-08-28'));
        $this->assertTrue(ManualAttendanceStatus::isHolidayOn($user, '2026-08-30'));
    }

    private function isHoliday(string $date): bool
    {
        return ManualAttendanceStatus::isHolidayFor('holiday', '2026-08-25', '2026-09-03', $date);
    }
}
