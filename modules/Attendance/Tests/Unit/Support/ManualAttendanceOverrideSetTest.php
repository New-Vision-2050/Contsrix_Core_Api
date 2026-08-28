<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Support\ManualAttendanceOverrideSet;
use Modules\Attendance\Support\ManualAttendanceStatus;
use PHPUnit\Framework\TestCase;

class ManualAttendanceOverrideSetTest extends TestCase
{
    public function test_second_holiday_keeps_an_earlier_disjoint_day(): void
    {
        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-27', '2026-08-27')
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-30', '2026-08-30');

        $this->assertTrue($set->isHolidayOn('2026-08-27'));
        $this->assertFalse($set->isHolidayOn('2026-08-28'));
        $this->assertFalse($set->isHolidayOn('2026-08-29'));
        $this->assertTrue($set->isHolidayOn('2026-08-30'));
    }

    public function test_adjacent_holiday_days_merge_into_one_range(): void
    {
        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-27', '2026-08-27')
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-28', '2026-08-28');

        $this->assertSame(
            [['status' => 'holiday', 'starts_on' => '2026-08-27', 'ends_on' => '2026-08-28']],
            $set->ranges()
        );
    }

    public function test_required_attendance_punches_only_the_given_day(): void
    {
        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-27', '2026-08-27')
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-30', '2026-08-30')
            ->withApplied(ManualAttendanceStatus::REQUIRED_ATTENDANCE, '2026-08-27', '2026-08-27');

        $this->assertTrue($set->isRequiredAttendanceOn('2026-08-27'));
        $this->assertFalse($set->isHolidayOn('2026-08-27'));
        $this->assertTrue($set->isHolidayOn('2026-08-30'));
    }

    public function test_required_attendance_splits_a_surrounding_holiday_range(): void
    {
        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-25', '2026-09-03')
            ->withApplied(ManualAttendanceStatus::REQUIRED_ATTENDANCE, '2026-08-28', '2026-08-28');

        $this->assertTrue($set->isHolidayOn('2026-08-27'));
        $this->assertTrue($set->isRequiredAttendanceOn('2026-08-28'));
        $this->assertTrue($set->isHolidayOn('2026-08-29'));
    }

    public function test_open_ended_required_attendance_from_today_leaves_earlier_holidays(): void
    {
        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-27', '2026-08-27')
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-30', '2026-08-30')
            ->withApplied(ManualAttendanceStatus::REQUIRED_ATTENDANCE, '2026-08-28', null);

        $this->assertTrue($set->isHolidayOn('2026-08-27'));
        $this->assertTrue($set->isRequiredAttendanceOn('2026-08-28'));
        $this->assertTrue($set->isRequiredAttendanceOn('2026-08-30'));
        $this->assertFalse($set->isHolidayOn('2026-08-30'));
    }

    public function test_holiday_range_covering_returns_that_range_not_a_span_across_the_gap(): void
    {
        $set = ManualAttendanceOverrideSet::none()
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-27', '2026-08-27')
            ->withApplied(ManualAttendanceStatus::HOLIDAY, '2026-08-30', '2026-08-30');

        $this->assertSame(
            ['date_from' => '2026-08-27', 'date_to' => '2026-08-27'],
            $set->holidayRangeCovering('2026-08-27')
        );
        $this->assertNull($set->holidayRangeCovering('2026-08-28'));
    }
}
