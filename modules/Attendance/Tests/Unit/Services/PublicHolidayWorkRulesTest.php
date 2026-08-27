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
use Modules\Attendance\Support\PublicHolidayDates;
use Modules\User\Models\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * An official public holiday must close the day in the work rules, so
 * `/attendance/user-constraint/today` returns no periods and therefore no
 * `can_clock_in_until` — the same shape a constraint holiday or a manual holiday override
 * produces, which is what stops the app offering a clock-in button (INV-21).
 */
class PublicHolidayWorkRulesTest extends TestCase
{
    private AttendanceConstraintService $service;
    private ReflectionMethod $applyPublicHoliday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AttendanceConstraintService(
            $this->createMock(TimeConstraintServiceInterface::class),
            $this->createMock(LocationConstraintServiceInterface::class),
            $this->createMock(DeviceConstraintServiceInterface::class),
            $this->createMock(RoleConstraintServiceInterface::class),
            $this->createMock(BehavioralConstraintServiceInterface::class),
            $this->createMock(SecurityConstraintServiceInterface::class),
            $this->createMock(ComplianceConstraintServiceInterface::class)
        );

        $this->applyPublicHoliday = new ReflectionMethod($this->service, 'applyPublicHoliday');
        $this->applyPublicHoliday->setAccessible(true);
    }

    public function test_a_public_holiday_closes_a_scheduled_work_day(): void
    {
        $rules = $this->apply($this->workDayRules(), '2026-08-27', ['2026-08-27' => 'المولد النبوي الشريف']);

        $this->assertSame('holiday', $rules['day_status']);
        $this->assertTrue($rules['is_holiday']);
        $this->assertSame('المولد النبوي الشريف', $rules['reason']);
    }

    /**
     * The periods are what carry `can_clock_in_until`, so they have to be emptied or the app
     * still shows a clock-in button on a day it was just told is a holiday.
     */
    public function test_a_public_holiday_leaves_no_period_to_clock_in_against(): void
    {
        $rules = $this->apply($this->workDayRules(), '2026-08-27', ['2026-08-27' => 'National Day']);

        $this->assertSame([], $rules['all_work_periods']);
        $this->assertNull($rules['current_work_period']);
        $this->assertNull($rules['active_or_next_period']);
        $this->assertSame(0.0, $rules['total_work_hours']);
    }

    public function test_an_ordinary_day_is_left_untouched(): void
    {
        $rules = $this->apply($this->workDayRules(), '2026-08-28', ['2026-08-27' => 'National Day']);

        $this->assertSame('work_day', $rules['day_status']);
        $this->assertFalse($rules['is_holiday']);
        $this->assertCount(1, $rules['all_work_periods']);
    }

    /**
     * A weekend already answers `is_holiday` with no periods. Relabelling it would call a day
     * the employee never works an official holiday, which INV-18 assigns to the schedule.
     */
    public function test_a_weekend_keeps_its_own_day_status(): void
    {
        $weekend = ['day_status' => 'day_off_or_weekend', 'is_holiday' => true, 'all_work_periods' => []];

        $rules = $this->apply($weekend, '2026-08-27', ['2026-08-27' => 'National Day']);

        $this->assertSame('day_off_or_weekend', $rules['day_status']);
    }

    /**
     * No constraint assigned still means the country's holiday applies.
     */
    public function test_a_user_with_no_schedule_still_gets_the_holiday(): void
    {
        $undefined = ['day_status' => 'Undefined', 'is_holiday' => false, 'all_work_periods' => []];

        $rules = $this->apply($undefined, '2026-08-27', ['2026-08-27' => 'National Day']);

        $this->assertSame('holiday', $rules['day_status']);
        $this->assertTrue($rules['is_holiday']);
    }

    /**
     * @return array<string, mixed>
     */
    private function workDayRules(): array
    {
        return [
            'day_status'            => 'work_day',
            'is_holiday'            => false,
            'reason'                => 'Scheduled working day.',
            'all_work_periods'      => [['start_time' => '08:00', 'end_time' => '16:00']],
            'total_work_hours'      => 8.0,
            'current_work_period'   => ['start_time' => '08:00'],
            'active_or_next_period' => ['start_time' => '08:00'],
        ];
    }

    /**
     * @param  array<string, mixed>  $workRules
     * @param  array<string, string>  $holidays
     * @return array<string, mixed>
     */
    private function apply(array $workRules, string $date, array $holidays): array
    {
        return $this->applyPublicHoliday->invoke(
            $this->service,
            $workRules,
            new User(),
            Carbon::parse($date, 'Asia/Riyadh'),
            PublicHolidayDates::fromMap($holidays)
        );
    }
}
