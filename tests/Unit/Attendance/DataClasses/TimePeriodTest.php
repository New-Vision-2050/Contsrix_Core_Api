<?php

namespace Tests\Unit\Attendance\DataClasses;

use Tests\TestCase;
use Modules\Attendance\DataClasses\TimePeriod;
use InvalidArgumentException;

class TimePeriodTest extends TestCase
{
    public function test_can_create_valid_time_period()
    {
        $period = new TimePeriod(
            name: 'Morning Shift',
            startTime: '09:00',
            endTime: '17:00',
            spansNextDay: false,
            gracePeriodBefore: 15,
            gracePeriodAfter: 15
        );

        $this->assertEquals('Morning Shift', $period->name);
        $this->assertEquals('09:00', $period->startTime);
        $this->assertEquals('17:00', $period->endTime);
        $this->assertFalse($period->spansNextDay);
        $this->assertEquals(15, $period->gracePeriodBefore);
        $this->assertEquals(15, $period->gracePeriodAfter);
    }

    public function test_can_create_cross_day_period()
    {
        $period = new TimePeriod(
            name: 'Night Shift',
            startTime: '22:00',
            endTime: '06:00',
            spansNextDay: true,
            gracePeriodBefore: 30,
            gracePeriodAfter: 30
        );

        $this->assertTrue($period->spansNextDay);
        $this->assertEquals('22:00', $period->startTime);
        $this->assertEquals('06:00', $period->endTime);
    }

    public function test_calculates_duration_correctly()
    {
        $period = new TimePeriod('Test', '09:00', '17:00');
        $this->assertEquals(480, $period->getDurationMinutes());

        $crossDayPeriod = new TimePeriod('Night', '22:00', '06:00', true);
        $this->assertNull($crossDayPeriod->getDurationMinutes());
    }

    public function test_calculates_effective_times_with_grace_periods()
    {
        $period = new TimePeriod(
            name: 'Work',
            startTime: '09:00',
            endTime: '17:00',
            gracePeriodBefore: 15,
            gracePeriodAfter: 30
        );

        $this->assertEquals('08:45', $period->getEffectiveStartTime());
        $this->assertEquals('17:30', $period->getEffectiveEndTime());
    }

    public function test_detects_overlapping_periods()
    {
        $period1 = new TimePeriod('Morning', '09:00', '13:00');
        $period2 = new TimePeriod('Afternoon', '12:00', '17:00');
        $period3 = new TimePeriod('Evening', '18:00', '22:00');

        $this->assertTrue($period1->overlapsWith($period2));
        $this->assertFalse($period1->overlapsWith($period3));
    }

    public function test_converts_to_array()
    {
        $period = new TimePeriod(
            name: 'Test Period',
            startTime: '09:00',
            endTime: '17:00',
            spansNextDay: false,
            gracePeriodBefore: 15,
            gracePeriodAfter: 15
        );

        $expected = [
            'name' => 'Test Period',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'spans_next_day' => false,
            'grace_period_before' => 15,
            'grace_period_after' => 15
        ];

        $this->assertEquals($expected, $period->toArray());
    }

    public function test_creates_from_array()
    {
        $data = [
            'name' => 'Evening Shift',
            'start_time' => '17:00',
            'end_time' => '01:00',
            'spans_next_day' => true,
            'grace_period_before' => 30,
            'grace_period_after' => 30
        ];

        $period = TimePeriod::fromArray($data);

        $this->assertEquals('Evening Shift', $period->name);
        $this->assertEquals('17:00', $period->startTime);
        $this->assertEquals('01:00', $period->endTime);
        $this->assertTrue($period->spansNextDay);
        $this->assertEquals(30, $period->gracePeriodBefore);
        $this->assertEquals(30, $period->gracePeriodAfter);
    }

    public function test_string_representation()
    {
        $period = new TimePeriod('Work', '09:00', '17:00', false, 15, 15);
        $expected = 'Work: 09:00-17:00 [Grace: -15m, +15m]';
        $this->assertEquals($expected, (string) $period);

        $crossDayPeriod = new TimePeriod('Night', '22:00', '06:00', true);
        $expected = 'Night: 22:00-06:00 (spans next day)';
        $this->assertEquals($expected, (string) $crossDayPeriod);
    }

    /**
     * @dataProvider invalidPeriodDataProvider
     */
    public function test_validates_period_data($name, $startTime, $endTime, $spansNextDay, $graceBefore, $graceAfter, $expectedError)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedError);

        new TimePeriod($name, $startTime, $endTime, $spansNextDay, $graceBefore, $graceAfter);
    }

    public static function invalidPeriodDataProvider(): array
    {
        return [
            'empty name' => ['', '09:00', '17:00', false, 0, 0, 'Period name cannot be empty'],
            'invalid start time' => ['Test', '25:00', '17:00', false, 0, 0, 'Invalid start time format: 25:00. Expected HH:MM'],
            'invalid end time' => ['Test', '09:00', '25:00', false, 0, 0, 'Invalid end time format: 25:00. Expected HH:MM'],
            'end before start same day' => ['Test', '17:00', '09:00', false, 0, 0, 'End time (09:00) must be after start time (17:00) for same-day periods'],
            'negative grace before' => ['Test', '09:00', '17:00', false, -5, 0, 'Grace period before cannot be negative'],
            'negative grace after' => ['Test', '09:00', '17:00', false, 0, -5, 'Grace period after cannot be negative'],
            'excessive grace before' => ['Test', '09:00', '17:00', false, 1500, 0, 'Grace period before cannot exceed 24 hours (1440 minutes)'],
            'excessive grace after' => ['Test', '09:00', '17:00', false, 0, 1500, 'Grace period after cannot exceed 24 hours (1440 minutes)'],
        ];
    }

    public function test_validates_time_format()
    {
        $invalidTimes = ['24:00', '12:60', '1:30', 'invalid', '12:30:45'];

        foreach ($invalidTimes as $time) {
            $this->expectException(InvalidArgumentException::class);
            new TimePeriod('Test', $time, '17:00');
        }
    }

    public function test_handles_edge_cases()
    {
        // Test midnight period (cross-day)
        $midnight = new TimePeriod('Midnight', '00:00', '00:00', true, 0, 0);
        $this->assertEquals('00:00', $midnight->startTime);
        $this->assertEquals('00:00', $midnight->endTime);
        $this->assertTrue($midnight->spansNextDay);

        // Same start and end time (cross-day)
        $fullDayPeriod = new TimePeriod('24h', '00:00', '00:00', true);
        $this->assertNull($fullDayPeriod->getDurationMinutes()); // Cross-day periods return null
        $this->assertTrue($fullDayPeriod->spansNextDay);

        // Test noon period
        $noonPeriod = new TimePeriod('Noon', '12:00', '13:00');
        $this->assertEquals('12:00', $noonPeriod->startTime);
        $this->assertEquals(60, $noonPeriod->getDurationMinutes()); // 1 hour

        // Maximum grace periods
        $maxGracePeriod = new TimePeriod('Max Grace', '12:00', '13:00', false, 1440, 1440);
        $this->assertEquals('12:00', $maxGracePeriod->getEffectiveStartTime()); // 12:00 - 24:00 wraps to 12:00
        $this->assertEquals('13:00', $maxGracePeriod->getEffectiveEndTime()); // 13:00 + 24:00 wraps to 13:00
    }
}
