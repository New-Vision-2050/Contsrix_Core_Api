<?php

namespace Tests\Unit\Attendance\DataClasses;

use Tests\TestCase;
use Modules\Attendance\DataClasses\TimePeriod;
use Modules\Attendance\DataClasses\DaySchedule;
use InvalidArgumentException;

class DayScheduleTest extends TestCase
{
    private TimePeriod $morningPeriod;
    private TimePeriod $afternoonPeriod;
    private TimePeriod $eveningPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->morningPeriod = new TimePeriod('Morning', '09:00', '12:00');
        $this->afternoonPeriod = new TimePeriod('Afternoon', '13:00', '17:00');
        $this->eveningPeriod = new TimePeriod('Evening', '18:00', '22:00');
    }

    public function test_can_create_enabled_day_schedule()
    {
        $schedule = new DaySchedule(true, [$this->morningPeriod, $this->afternoonPeriod]);

        $this->assertTrue($schedule->enabled);
        $this->assertCount(2, $schedule->periods);
        $this->assertEquals('Morning', $schedule->periods[0]->name);
        $this->assertEquals('Afternoon', $schedule->periods[1]->name);
    }

    public function test_can_create_disabled_day_schedule()
    {
        $schedule = new DaySchedule(false, []);

        $this->assertFalse($schedule->enabled);
        $this->assertEmpty($schedule->periods);
    }

    public function test_factory_method_enabled()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);

        $this->assertTrue($schedule->enabled);
        $this->assertCount(2, $schedule->periods);
    }

    public function test_factory_method_disabled()
    {
        $schedule = DaySchedule::disabled();

        $this->assertFalse($schedule->enabled);
        $this->assertEmpty($schedule->periods);
    }

    public function test_get_period_count()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);
        $this->assertEquals(2, $schedule->getPeriodCount());

        $disabledSchedule = DaySchedule::disabled();
        $this->assertEquals(0, $disabledSchedule->getPeriodCount());
    }

    public function test_get_period_names()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);
        $names = $schedule->getPeriodNames();

        $this->assertEquals(['Morning', 'Afternoon'], $names);
    }

    public function test_get_total_work_minutes()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);
        $totalMinutes = $schedule->getTotalWorkMinutes();

        // Morning: 3 hours (180 min) + Afternoon: 4 hours (240 min) = 420 min
        $this->assertEquals(420, $totalMinutes);
    }

    public function test_get_period_by_name()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);

        $foundPeriod = $schedule->getPeriod('Morning');
        $this->assertNotNull($foundPeriod);
        $this->assertEquals('Morning', $foundPeriod->name);

        $notFound = $schedule->getPeriod('NonExistent');
        $this->assertNull($notFound);
    }

    public function test_add_period()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod);
        $newSchedule = $schedule->addPeriod($this->eveningPeriod);

        $this->assertCount(1, $schedule->periods); // Original unchanged
        $this->assertCount(2, $newSchedule->periods); // New schedule has both
        $this->assertEquals('Evening', $newSchedule->periods[1]->name);
    }

    public function test_remove_period()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);
        $newSchedule = $schedule->removePeriod('Morning');

        $this->assertCount(2, $schedule->periods); // Original unchanged
        $this->assertCount(1, $newSchedule->periods); // New schedule has one less
        $this->assertEquals('Afternoon', $newSchedule->periods[0]->name);
    }

    public function test_has_cross_day_periods()
    {
        $crossDayPeriod = new TimePeriod('Night', '22:00', '06:00', true);
        $schedule = DaySchedule::enabled($this->morningPeriod, $crossDayPeriod);

        $this->assertTrue($schedule->hasCrossDayPeriods());

        $regularSchedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);
        $this->assertFalse($regularSchedule->hasCrossDayPeriods());
    }

    public function test_converts_to_array()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod);
        $array = $schedule->toArray();

        $expected = [
            'enabled' => true,
            'periods' => [
                [
                    'name' => 'Morning',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'spans_next_day' => false,
                    'grace_period_before' => 0,
                    'grace_period_after' => 0
                ]
            ]
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_creates_from_array()
    {
        $data = [
            'enabled' => true,
            'periods' => [
                [
                    'name' => 'Work Period',
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'spans_next_day' => false,
                    'grace_period_before' => 15,
                    'grace_period_after' => 15
                ]
            ]
        ];

        $schedule = DaySchedule::fromArray($data);

        $this->assertTrue($schedule->enabled);
        $this->assertCount(1, $schedule->periods);
        $this->assertEquals('Work Period', $schedule->periods[0]->name);
    }

    public function test_string_representation()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->afternoonPeriod);
        $expected = 'Enabled: Morning: 09:00-12:00, Afternoon: 13:00-17:00';
        $this->assertEquals($expected, (string) $schedule);

        $disabled = DaySchedule::disabled();
        $this->assertEquals('Disabled', (string) $disabled);
    }

    public function test_validates_enabled_day_must_have_periods()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enabled days must have at least one period');

        new DaySchedule(true, []);
    }

    public function test_validates_disabled_day_cannot_have_periods()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Disabled days cannot have periods');

        new DaySchedule(false, [$this->morningPeriod]);
    }

    public function test_validates_unique_period_names()
    {
        $duplicatePeriod = new TimePeriod('Morning', '14:00', '18:00');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Period names must be unique within a day');

        new DaySchedule(true, [$this->morningPeriod, $duplicatePeriod]);
    }

    public function test_validates_no_overlapping_same_day_periods()
    {
        $overlappingPeriod = new TimePeriod('Overlap', '11:00', '14:00');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Periods 'Morning' and 'Overlap' overlap");

        new DaySchedule(true, [$this->morningPeriod, $overlappingPeriod]);
    }

    public function test_allows_cross_day_periods_with_same_day_periods()
    {
        $crossDayPeriod = new TimePeriod('Night', '22:00', '06:00', true);
        
        // This should not throw an exception as cross-day periods are allowed
        $schedule = new DaySchedule(true, [$this->morningPeriod, $crossDayPeriod]);
        
        $this->assertCount(2, $schedule->periods);
        $this->assertTrue($schedule->hasCrossDayPeriods());
    }

    public function test_handles_edge_cases()
    {
        // Single period
        $singlePeriod = DaySchedule::enabled($this->morningPeriod);
        $this->assertEquals(1, $singlePeriod->getPeriodCount());

        // Multiple periods with gaps
        $schedule = DaySchedule::enabled($this->morningPeriod, $this->eveningPeriod);
        $this->assertEquals(2, $schedule->getPeriodCount());
        $this->assertEquals(420, $schedule->getTotalWorkMinutes()); // 3h + 4h = 7h = 420min

        // Remove non-existent period
        $newSchedule = $schedule->removePeriod('NonExistent');
        $this->assertEquals($schedule->getPeriodCount(), $newSchedule->getPeriodCount());
    }

    public function test_immutability()
    {
        $schedule = DaySchedule::enabled($this->morningPeriod);
        $newSchedule = $schedule->addPeriod($this->afternoonPeriod);

        // Original should be unchanged
        $this->assertCount(1, $schedule->periods);
        $this->assertCount(2, $newSchedule->periods);
        $this->assertNotSame($schedule, $newSchedule);
    }
}
