<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\Carbon;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\Attendance\Support\PublicHolidayDates;
use PHPUnit\Framework\TestCase;

class PublicHolidayDatesTest extends TestCase
{
    public function test_a_listed_date_is_a_holiday_and_carries_its_name(): void
    {
        $dates = PublicHolidayDates::fromMap(['2026-08-27' => 'المولد النبوي الشريف']);

        $this->assertTrue($dates->isHoliday('2026-08-27'));
        $this->assertSame('المولد النبوي الشريف', $dates->nameFor('2026-08-27'));
    }

    public function test_a_carbon_instance_resolves_by_its_calendar_date(): void
    {
        $dates = PublicHolidayDates::fromMap(['2026-08-27' => 'National Day']);

        $this->assertTrue($dates->isHoliday(Carbon::parse('2026-08-27 23:30:00', 'Asia/Riyadh')));
    }

    public function test_an_unlisted_date_is_not_a_holiday(): void
    {
        $dates = PublicHolidayDates::fromMap(['2026-08-27' => 'National Day']);

        $this->assertFalse($dates->isHoliday('2026-08-28'));
        $this->assertNull($dates->nameFor('2026-08-28'));
    }

    public function test_none_holds_no_dates(): void
    {
        $dates = PublicHolidayDates::none();

        $this->assertTrue($dates->isEmpty());
        $this->assertFalse($dates->isHoliday('2026-08-27'));
    }

    /**
     * An unparseable value must answer "not a holiday" rather than throw: the date reaches
     * here straight from an attendance row.
     */
    public function test_an_unparseable_date_is_not_a_holiday(): void
    {
        $dates = PublicHolidayDates::fromMap(['2026-08-27' => 'National Day']);

        $this->assertFalse($dates->isHoliday('not-a-date'));
    }

    public function test_rows_written_by_the_removed_command_are_recognised(): void
    {
        $this->assertTrue(PublicHolidayDates::isLegacyGeneratedRow('Auto-generated holiday record: National Day'));
        $this->assertTrue(PublicHolidayDates::isLegacyGeneratedRow('  Auto-generated holiday record: عيد  '));
    }

    public function test_other_notes_are_not_mistaken_for_a_generated_row(): void
    {
        $this->assertFalse(PublicHolidayDates::isLegacyGeneratedRow(null));
        $this->assertFalse(PublicHolidayDates::isLegacyGeneratedRow(''));
        $this->assertFalse(PublicHolidayDates::isLegacyGeneratedRow(ManualAttendanceStatus::HOLIDAY_ROW_NOTE));
    }
}
