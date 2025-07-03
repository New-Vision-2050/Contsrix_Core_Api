<?php

namespace Tests\Unit\Attendance\DataClasses;

use Tests\TestCase;
use Modules\Attendance\DataClasses\TimePeriod;
use Modules\Attendance\DataClasses\DaySchedule;
use Modules\Attendance\DataClasses\WeeklySchedule;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;
use InvalidArgumentException;

class MultiplePeriodsConfigTest extends TestCase
{
    private WeeklySchedule $standardWeeklySchedule;

    protected function setUp(): void
    {
        parent::setUp();

        $workPeriod = new TimePeriod('Work Hours', '09:00', '17:00', false, 15, 15);
        $this->standardWeeklySchedule = WeeklySchedule::standardWorkWeek($workPeriod);
    }

    public function test_can_create_multiple_periods_config()
    {
        $config = new MultiplePeriodsConfig(
            weeklySchedule: $this->standardWeeklySchedule,
            description: 'Test configuration',
            metadata: ['version' => '1.0']
        );

        $this->assertInstanceOf(WeeklySchedule::class, $config->weeklySchedule);
        $this->assertEquals('Test configuration', $config->description);
        $this->assertEquals(['version' => '1.0'], $config->metadata);
    }

    public function test_factory_method_standard_office_hours()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();

        $this->assertEquals('Standard 5-day office hours with weekend off', $config->description);
        $this->assertEquals(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], $config->getEnabledDays());
        $this->assertEquals(40.0, $config->getTotalWeeklyWorkHours());
    }

    public function test_factory_method_with_custom_parameters()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours(
            startTime: '08:00',
            endTime: '16:00',
            gracePeriod: 30
        );

        $mondaySchedule = $config->getDaySchedule('monday');
        $this->assertNotNull($mondaySchedule);
        
        $period = $mondaySchedule->periods[0];
        $this->assertEquals('08:00', $period->startTime);
        $this->assertEquals('16:00', $period->endTime);
        $this->assertEquals(30, $period->gracePeriodBefore);
        $this->assertEquals(30, $period->gracePeriodAfter);
    }

    public function test_factory_method_restaurant_service_hours()
    {
        $config = MultiplePeriodsConfig::restaurantServiceHours();

        $this->assertEquals('Restaurant lunch and dinner service periods', $config->description);
        $this->assertEquals(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], $config->getEnabledDays());
        $this->assertEquals(12, $config->getTotalPeriodCount()); // 6 days × 2 periods
    }

    public function test_factory_method_security_shifts()
    {
        $config = MultiplePeriodsConfig::securityShifts();

        $this->assertEquals('24/7 security coverage with day and night shifts', $config->description);
        $this->assertTrue($config->hasCrossDayPeriods());
        $this->assertEquals(7, count($config->getEnabledDays())); // All days
    }

    public function test_factory_method_flexible_office_hours()
    {
        $config = MultiplePeriodsConfig::flexibleOfficeHours();

        $this->assertEquals('Flexible office hours with different options per day', $config->description);
        $this->assertEquals(6, $config->getTotalPeriodCount()); // Different periods per day
    }

    public function test_get_day_schedule()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();

        $mondaySchedule = $config->getDaySchedule('monday');
        $this->assertNotNull($mondaySchedule);
        $this->assertTrue($mondaySchedule->enabled);

        $sundaySchedule = $config->getDaySchedule('sunday');
        $this->assertNotNull($sundaySchedule);
        $this->assertFalse($sundaySchedule->enabled);
    }

    public function test_is_day_enabled()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();

        $this->assertTrue($config->isDayEnabled('monday'));
        $this->assertFalse($config->isDayEnabled('sunday'));
    }

    public function test_get_enabled_days()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $enabledDays = $config->getEnabledDays();

        $this->assertEquals(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], $enabledDays);
    }

    public function test_get_total_period_count()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $this->assertEquals(5, $config->getTotalPeriodCount());

        $restaurantConfig = MultiplePeriodsConfig::restaurantServiceHours();
        $this->assertEquals(12, $restaurantConfig->getTotalPeriodCount());
    }

    public function test_get_total_weekly_work_hours()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $this->assertEquals(40.0, $config->getTotalWeeklyWorkHours());
    }

    public function test_has_cross_day_periods()
    {
        $officeConfig = MultiplePeriodsConfig::standardOfficeHours();
        $this->assertFalse($officeConfig->hasCrossDayPeriods());

        $securityConfig = MultiplePeriodsConfig::securityShifts();
        $this->assertTrue($securityConfig->hasCrossDayPeriods());
    }

    public function test_with_description()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $newConfig = $config->withDescription('Updated description');

        $this->assertEquals('Standard 5-day office hours with weekend off', $config->description);
        $this->assertEquals('Updated description', $newConfig->description);
        $this->assertNotSame($config, $newConfig);
    }

    public function test_with_metadata()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $newConfig = $config->withMetadata(['version' => '2.0', 'author' => 'test']);

        $this->assertEquals(['version' => '2.0', 'author' => 'test'], $newConfig->metadata);
        $this->assertNotSame($config, $newConfig);
    }

    public function test_with_weekly_schedule()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $newWeeklySchedule = WeeklySchedule::twentyFourSeven(
            new TimePeriod('Day', '06:00', '18:00'),
            new TimePeriod('Night', '18:00', '06:00', true)
        );
        
        $newConfig = $config->withWeeklySchedule($newWeeklySchedule);

        $this->assertFalse($config->hasCrossDayPeriods());
        $this->assertTrue($newConfig->hasCrossDayPeriods());
        $this->assertNotSame($config, $newConfig);
    }

    public function test_get_summary()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $summary = $config->getSummary();

        $this->assertArrayHasKey('description', $summary);
        $this->assertArrayHasKey('enabled_days', $summary);
        $this->assertArrayHasKey('total_periods', $summary);
        $this->assertArrayHasKey('weekly_work_hours', $summary);
        $this->assertArrayHasKey('has_cross_day_periods', $summary);
        $this->assertArrayHasKey('all_periods', $summary);

        $this->assertEquals('Standard 5-day office hours with weekend off', $summary['description']);
        $this->assertEquals(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], $summary['enabled_days']);
        $this->assertEquals(5, $summary['total_periods']);
        $this->assertEquals(40.0, $summary['weekly_work_hours']);
        $this->assertFalse($summary['has_cross_day_periods']);
    }

    public function test_converts_to_array()
    {
        $config = new MultiplePeriodsConfig(
            weeklySchedule: $this->standardWeeklySchedule,
            description: 'Test config',
            metadata: ['test' => true]
        );

        $array = $config->toArray();

        $this->assertArrayHasKey('weekly_schedule', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('Test config', $array['description']);
        $this->assertEquals(['test' => true], $array['metadata']);
    }

    public function test_creates_from_array()
    {
        $data = [
            'weekly_schedule' => [
                'monday' => [
                    'enabled' => true,
                    'periods' => [
                        [
                            'name' => 'Work',
                            'start_time' => '09:00',
                            'end_time' => '17:00',
                            'spans_next_day' => false,
                            'grace_period_before' => 15,
                            'grace_period_after' => 15
                        ]
                    ]
                ],
                'sunday' => [
                    'enabled' => false
                ]
            ],
            'description' => 'Custom schedule',
            'metadata' => ['version' => '1.0']
        ];

        $config = MultiplePeriodsConfig::fromArray($data);

        $this->assertEquals('Custom schedule', $config->description);
        $this->assertEquals(['version' => '1.0'], $config->metadata);
        $this->assertTrue($config->isDayEnabled('monday'));
        $this->assertFalse($config->isDayEnabled('sunday'));
    }

    public function test_json_serialization()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        
        $json = $config->toJson();
        $this->assertJson($json);
        
        // Test that JSON contains expected structure
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('weekly_schedule', $decoded);
        $this->assertArrayHasKey('description', $decoded);
        
        // Test deserialization
        $restored = MultiplePeriodsConfig::fromJson($json);
        $this->assertEquals($config->description, $restored->description);
        $this->assertEquals($config->getEnabledDays(), $restored->getEnabledDays());
        $this->assertEquals($config->getTotalPeriodCount(), $restored->getTotalPeriodCount());
    }

    public function test_json_deserialization()
    {
        $originalConfig = MultiplePeriodsConfig::restaurantServiceHours();
        $json = $originalConfig->toJson();
        
        $restoredConfig = MultiplePeriodsConfig::fromJson($json);

        $this->assertEquals($originalConfig->description, $restoredConfig->description);
        $this->assertEquals($originalConfig->getEnabledDays(), $restoredConfig->getEnabledDays());
        $this->assertEquals($originalConfig->getTotalPeriodCount(), $restoredConfig->getTotalPeriodCount());
    }

    public function test_json_with_formatting()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        
        $prettyJson = $config->toJson(); // Default is JSON_PRETTY_PRINT
        $compactJson = $config->toJson(0); // No flags = compact
        
        $this->assertJson($compactJson);
        $this->assertJson($prettyJson);
        
        // Pretty JSON should contain newlines, compact should not
        $this->assertStringNotContainsString("\n", $compactJson);
        $this->assertStringContainsString("\n", $prettyJson);
    }

    public function test_validates_missing_weekly_schedule()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: weekly_schedule');

        MultiplePeriodsConfig::fromArray(['description' => 'Missing schedule']);
    }

    public function test_validates_invalid_weekly_schedule_structure()
    {
        $this->expectException(InvalidArgumentException::class);

        MultiplePeriodsConfig::fromArray([
            'weekly_schedule' => [
                'invalidday' => [
                    'enabled' => true,
                    'periods' => []
                ]
            ]
        ]);
    }

    public function test_validates_excessive_weekly_hours()
    {
        // Create a config with excessive hours (more than reasonable)
        $longPeriod = new TimePeriod('Long Work', '00:00', '23:59');
        $allDaysSchedule = [];
        
        $allDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($allDays as $day) {
            $allDaysSchedule[$day] = DaySchedule::enabled($longPeriod);
        }

        $weeklySchedule = new WeeklySchedule($allDaysSchedule);
        
        // This should work as the validation is in the request layer, not the data class itself
        $config = new MultiplePeriodsConfig($weeklySchedule, 'Excessive hours test');
        
        // The config should be created, but weekly hours should be very high
        $this->assertGreaterThan(100, $config->getTotalWeeklyWorkHours());
    }

    public function test_handles_complex_schedule_configuration()
    {
        $data = [
            'weekly_schedule' => [
                'monday' => [
                    'enabled' => true,
                    'periods' => [
                        [
                            'name' => 'Morning Shift',
                            'start_time' => '08:00',
                            'end_time' => '16:00',
                            'spans_next_day' => false,
                            'grace_period_before' => 30,
                            'grace_period_after' => 30
                        ]
                    ]
                ],
                'tuesday' => [
                    'enabled' => true,
                    'periods' => [
                        [
                            'name' => 'Day Shift',
                            'start_time' => '09:00',
                            'end_time' => '17:00',
                            'spans_next_day' => false,
                            'grace_period_before' => 15,
                            'grace_period_after' => 15
                        ],
                        [
                            'name' => 'Evening Shift',
                            'start_time' => '17:00',
                            'end_time' => '01:00',
                            'spans_next_day' => true,
                            'grace_period_before' => 15,
                            'grace_period_after' => 15
                        ]
                    ]
                ],
                'sunday' => [
                    'enabled' => false
                ]
            ],
            'description' => 'Complex mixed schedule',
            'metadata' => [
                'created_by' => 'test',
                'version' => '1.0',
                'department' => 'Operations'
            ]
        ];

        $config = MultiplePeriodsConfig::fromArray($data);

        $this->assertEquals('Complex mixed schedule', $config->description);
        $this->assertEquals(['monday', 'tuesday'], $config->getEnabledDays());
        $this->assertEquals(3, $config->getTotalPeriodCount());
        $this->assertTrue($config->hasCrossDayPeriods());

        // Test specific day schedules
        $mondaySchedule = $config->getDaySchedule('monday');
        $this->assertEquals(1, $mondaySchedule->getPeriodCount());
        $this->assertEquals(['Morning Shift'], $mondaySchedule->getPeriodNames());

        $tuesdaySchedule = $config->getDaySchedule('tuesday');
        $this->assertEquals(2, $tuesdaySchedule->getPeriodCount());
        $this->assertEquals(['Day Shift', 'Evening Shift'], $tuesdaySchedule->getPeriodNames());
    }

    public function test_immutability()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $newConfig = $config->withDescription('New description');

        $this->assertNotSame($config, $newConfig);
        $this->assertEquals('Standard 5-day office hours with weekend off', $config->description);
        $this->assertEquals('New description', $newConfig->description);
    }

    public function test_performance_with_large_configurations()
    {
        $startTime = microtime(true);
        
        // Create and process multiple configurations
        for ($i = 0; $i < 100; $i++) {
            $config = MultiplePeriodsConfig::restaurantServiceHours();
            $json = $config->toJson();
            $restored = MultiplePeriodsConfig::fromJson($json);
            $summary = $restored->getSummary();
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete 100 cycles in reasonable time (less than 100ms)
        $this->assertLessThan(100, $duration, 'Performance test failed - took too long');
    }

    public function test_string_representation()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $string = (string) $config;

        $this->assertThat($string, $this->stringContains('Standard 5-day office hours'));
        $this->assertThat($string, $this->stringContains('Monday: Enabled'));
        $this->assertThat($string, $this->stringContains('Saturday: Disabled'));
    }
}
