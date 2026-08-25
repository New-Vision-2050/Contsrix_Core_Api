<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Support\ScheduledWorkDays;
use PHPUnit\Framework\TestCase;

/**
 * عطلة is a property of the schedule. Separating it from إجازة is what stops the calendar,
 * the history endpoint and the report PDF from printing "leave" over every weekend.
 */
class ScheduledWorkDaysTest extends TestCase
{
    public function test_disabled_weekday_is_not_a_work_day(): void
    {
        $days = $this->schedule(['friday' => false, 'saturday' => false, 'sunday' => true]);

        // 2026-08-28 is a Friday, 2026-08-30 a Sunday.
        $this->assertFalse($days->isWorkDay('2026-08-28'));
        $this->assertTrue($days->isWorkDay('2026-08-30'));
    }

    public function test_weekday_missing_from_the_schedule_is_not_a_work_day(): void
    {
        $days = $this->schedule(['sunday' => true]);

        $this->assertFalse($days->isWorkDay('2026-08-25'));
    }

    public function test_constraint_holiday_date_is_not_a_work_day(): void
    {
        $days = $this->schedule(
            ['tuesday' => true],
            [['date' => '2026-08-25', 'name' => 'Company day']]
        );

        $this->assertFalse($days->isWorkDay('2026-08-25'));
        $this->assertTrue($days->isWorkDay('2026-09-01'));
    }

    /**
     * With nothing configured there is no basis to call a date عطلة, so every date stays
     * schedulable and the caller falls through to its normal attendance logic.
     */
    public function test_missing_schedule_treats_every_date_as_schedulable(): void
    {
        $this->assertFalse(ScheduledWorkDays::unknown()->hasSchedule());
        $this->assertTrue(ScheduledWorkDays::unknown()->isWorkDay('2026-08-28'));
        $this->assertTrue(ScheduledWorkDays::fromConstraint(null)->isWorkDay('2026-08-28'));
    }

    /**
     * @param array<string, bool> $enabled
     * @param array<int, array<string, string>> $holidays
     */
    private function schedule(array $enabled, array $holidays = []): ScheduledWorkDays
    {
        $weekly = [];
        foreach ($enabled as $day => $isEnabled) {
            $weekly[$day] = ['enabled' => $isEnabled, 'periods' => []];
        }

        $constraint = new AttendanceConstraint();
        $constraint->constraint_config = [
            'time_rules' => [
                'weekly_schedule' => $weekly,
                'holidays'        => $holidays,
            ],
        ];

        return ScheduledWorkDays::fromConstraint($constraint);
    }
}
