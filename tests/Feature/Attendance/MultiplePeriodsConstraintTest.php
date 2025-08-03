<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;
use Modules\Attendance\Requests\CreateAttendanceConstraintRequest;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MultiplePeriodsConstraintTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceConstraintService();
    }

    public function test_can_create_constraint_with_standard_office_hours()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours();
        $constraintData = [
            'constraint_type' => 'time',
            'constraint_name' => 'multiple_periods',
            'constraint_config' => $config->toArray(),
            'is_active' => true
        ];

        $validator = Validator::make($constraintData, []);
        $request = new CreateAttendanceConstraintRequest();
        
        // This should not throw any exceptions
        $request->validateMultiplePeriodsConfig($validator, $constraintData['constraint_config']);
        
        $this->assertFalse($validator->fails());
    }

    public function test_validates_invalid_constraint_configuration()
    {
        $invalidConfig = [
            'weekly_schedule' => [
                'invalidday' => [
                    'enabled' => true,
                    'periods' => []
                ]
            ]
        ];

        $constraintData = [
            'constraint_type' => 'time',
            'constraint_name' => 'multiple_periods',
            'constraint_config' => $invalidConfig,
            'is_active' => true
        ];

        $validator = Validator::make($constraintData, []);
        $request = new CreateAttendanceConstraintRequest();
        
        $request->validateMultiplePeriodsConfig($validator, $constraintData['constraint_config']);
        
        $this->assertTrue($validator->fails());
        $this->assertThat($validator->errors()->first('constraint_config'), $this->stringContains('Invalid day: invalidday'));
    }

    public function test_validates_overlapping_periods()
    {
        $overlappingConfig = [
            'weekly_schedule' => [
                'monday' => [
                    'enabled' => true,
                    'periods' => [
                        [
                            'name' => 'Period 1',
                            'start_time' => '09:00',
                            'end_time' => '13:00',
                            'spans_next_day' => false,
                            'grace_period_before' => 0,
                            'grace_period_after' => 0
                        ],
                        [
                            'name' => 'Period 2',
                            'start_time' => '12:00',
                            'end_time' => '16:00',
                            'spans_next_day' => false,
                            'grace_period_before' => 0,
                            'grace_period_after' => 0
                        ]
                    ]
                ]
            ]
        ];

        $constraintData = [
            'constraint_type' => 'time',
            'constraint_name' => 'multiple_periods',
            'constraint_config' => $overlappingConfig,
            'is_active' => true
        ];

        $validator = Validator::make($constraintData, []);
        $request = new CreateAttendanceConstraintRequest();
        
        $request->validateMultiplePeriodsConfig($validator, $constraintData['constraint_config']);
        
        $this->assertTrue($validator->fails());
        $this->assertThat($validator->errors()->first('constraint_config'), $this->stringContains('overlap'));
    }

    public function test_validates_excessive_weekly_hours()
    {
        // Create config with excessive hours (more than 80 hours per week)
        $excessiveConfig = [
            'weekly_schedule' => []
        ];

        $allDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($allDays as $day) {
            $excessiveConfig['weekly_schedule'][$day] = [
                'enabled' => true,
                'periods' => [
                    [
                        'name' => 'Long Shift',
                        'start_time' => '00:00',
                        'end_time' => '23:59',
                        'spans_next_day' => false,
                        'grace_period_before' => 0,
                        'grace_period_after' => 0
                    ]
                ]
            ];
        }

        $constraintData = [
            'constraint_type' => 'time',
            'constraint_name' => 'multiple_periods',
            'constraint_config' => $excessiveConfig,
            'is_active' => true
        ];

        $validator = Validator::make($constraintData, []);
        $request = new CreateAttendanceConstraintRequest();
        
        $request->validateMultiplePeriodsConfig($validator, $constraintData['constraint_config']);
        
        $this->assertTrue($validator->fails());
        $this->assertThat($validator->errors()->first('constraint_config'), $this->stringContains('Weekly hours exceed limit'));
    }

    public function test_service_validates_attendance_within_allowed_period()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
        
        // Create mock attendance for Monday at 9:30 AM (within 9-5 period with grace)
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 09:30:00'); // Monday
        
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        
        $this->assertNull($result); // No violation
    }

    public function test_service_validates_attendance_with_grace_period()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
        
        // Create mock attendance for Monday at 8:50 AM (within grace period)
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 08:50:00'); // Monday, 10 minutes before 9 AM
        
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        
        $this->assertNull($result); // Should be valid due to grace period
    }

    public function test_service_rejects_attendance_outside_allowed_periods()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
        
        // Create mock attendance for Monday at 7:00 AM (too early, outside grace period)
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 07:00:00'); // Monday
        
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        
        $this->assertNotNull($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertThat($result['error'], $this->stringContains('outside allowed periods'));
    }

    public function test_service_rejects_attendance_on_disabled_day()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
        
        // Create mock attendance for Sunday (disabled day)
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-07 10:00:00'); // Sunday
        
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        
        $this->assertNotNull($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertThat($result['error'], $this->stringContains('not enabled'));
    }

    public function test_service_handles_cross_day_periods()
    {
        $config = MultiplePeriodsConfig::securityShifts()->toArray();
        
        // Create mock attendance for night shift (22:30 PM)
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 22:30:00'); // Monday night
        
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        
        $this->assertNull($result); // Should be valid for night shift
    }

    public function test_service_handles_restaurant_multiple_periods()
    {
        $config = MultiplePeriodsConfig::restaurantServiceHours()->toArray();
        
        // Test lunch period
        $lunchAttendance = new Attendance();
        $lunchAttendance->clock_in_time = Carbon::parse('2024-01-01 11:30:00'); // Monday lunch
        
        $result = $this->service->validateMultiplePeriods($lunchAttendance, $config);
        $this->assertNull($result);
        
        // Test dinner period
        $dinnerAttendance = new Attendance();
        $dinnerAttendance->clock_in_time = Carbon::parse('2024-01-01 18:30:00'); // Monday dinner
        
        $result = $this->service->validateMultiplePeriods($dinnerAttendance, $config);
        $this->assertNull($result);
        
        // Test between periods (should fail)
        $betweenAttendance = new Attendance();
        $betweenAttendance->clock_in_time = Carbon::parse('2024-01-01 15:00:00'); // Monday between lunch and dinner
        
        $result = $this->service->validateMultiplePeriods($betweenAttendance, $config);
        $this->assertNotNull($result);
    }

    public function test_service_provides_detailed_violation_messages()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
        
        // Create attendance outside allowed period
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 20:00:00'); // Monday 8 PM
        
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        
        $this->assertNotNull($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('allowed_periods', $result['details']);
        $this->assertThat($result['details']['allowed_periods'], $this->stringContains('Work Hours: 08:45-17:15'));
    }

    public function test_handles_invalid_configuration_in_service()
    {
        $invalidConfig = [
            'weekly_schedule' => [
                'invalidday' => [
                    'enabled' => true,
                    'periods' => []
                ]
            ]
        ];
        
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 10:00:00');
        
        $result = $this->service->validateMultiplePeriods($attendance, $invalidConfig);
        
        $this->assertNotNull($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertThat($result['error'], $this->stringContains('Invalid day: invalidday'));
    }

    public function test_factory_methods_create_valid_configurations()
    {
        $factoryMethods = [
            'standardOfficeHours',
            'restaurantServiceHours',
            'securityShifts',
            'flexibleOfficeHours'
        ];

        foreach ($factoryMethods as $method) {
            $config = MultiplePeriodsConfig::$method();
            
            // Test that the configuration is valid
            $validator = Validator::make([], []);
            $request = new CreateAttendanceConstraintRequest();
            
            $request->validateMultiplePeriodsConfig($validator, $config->toArray());
            
            $this->assertFalse($validator->fails(), "Factory method {$method} produced invalid configuration");
            
            // Test that it has expected properties
            $this->assertNotEmpty($config->description);
            $this->assertGreaterThan(0, count($config->getEnabledDays()));
            $this->assertGreaterThan(0, $config->getTotalPeriodCount());
        }
    }

    public function test_json_serialization_roundtrip_maintains_functionality()
    {
        $originalConfig = MultiplePeriodsConfig::restaurantServiceHours();
        
        // Serialize to JSON and back
        $json = $originalConfig->toJson();
        $restoredConfig = MultiplePeriodsConfig::fromJson($json);
        
        // Test that both configurations work the same way in validation
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 11:30:00'); // Monday lunch
        
        $originalResult = $this->service->validateMultiplePeriods($attendance, $originalConfig->toArray());
        $restoredResult = $this->service->validateMultiplePeriods($attendance, $restoredConfig->toArray());
        
        $this->assertEquals($originalResult, $restoredResult);
    }

    public function test_performance_with_complex_configurations()
    {
        $complexConfig = [
            'weekly_schedule' => []
        ];

        // Create a complex configuration with multiple periods per day
        $allDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($allDays as $day) {
            $complexConfig['weekly_schedule'][$day] = [
                'enabled' => true,
                'periods' => [
                    [
                        'name' => 'Early Shift',
                        'start_time' => '06:00',
                        'end_time' => '10:00',
                        'spans_next_day' => false,
                        'grace_period_before' => 15,
                        'grace_period_after' => 15
                    ],
                    [
                        'name' => 'Mid Shift',
                        'start_time' => '10:00',
                        'end_time' => '14:00',
                        'spans_next_day' => false,
                        'grace_period_before' => 15,
                        'grace_period_after' => 15
                    ],
                    [
                        'name' => 'Late Shift',
                        'start_time' => '14:00',
                        'end_time' => '18:00',
                        'spans_next_day' => false,
                        'grace_period_before' => 15,
                        'grace_period_after' => 15
                    ]
                ]
            ];
        }

        $startTime = microtime(true);
        
        // Validate configuration
        $validator = Validator::make([], []);
        $request = new CreateAttendanceConstraintRequest();
        $request->validateMultiplePeriodsConfig($validator, $complexConfig);
        
        // Test multiple attendance validations
        for ($i = 0; $i < 50; $i++) {
            $attendance = new Attendance();
            $attendance->clock_in_time = Carbon::parse('2024-01-01 12:00:00');
            $this->service->validateMultiplePeriods($attendance, $complexConfig);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Should complete in reasonable time (less than 100ms)
        $this->assertLessThan(100, $duration, 'Performance test failed - validation took too long');
    }

    public function test_edge_cases_and_boundary_conditions()
    {
        $config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
        
        // Test exactly at start time
        $attendance = new Attendance();
        $attendance->clock_in_time = Carbon::parse('2024-01-01 09:00:00'); // Exactly 9 AM
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        $this->assertNull($result);
        
        // Test exactly at end time
        $attendance->clock_in_time = Carbon::parse('2024-01-01 17:00:00'); // Exactly 5 PM
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        $this->assertNull($result);
        
        // Test one minute before grace period starts
        $attendance->clock_in_time = Carbon::parse('2024-01-01 08:44:00'); // 1 minute before grace
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        $this->assertNotNull($result);
        
        // Test one minute after grace period ends
        $attendance->clock_in_time = Carbon::parse('2024-01-01 17:16:00'); // 1 minute after grace
        $result = $this->service->validateMultiplePeriods($attendance, $config);
        $this->assertNotNull($result);
    }
}
