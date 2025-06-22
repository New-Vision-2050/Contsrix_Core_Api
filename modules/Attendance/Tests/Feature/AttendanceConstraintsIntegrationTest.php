<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\User\Models\User;
use Modules\Company\Models\Company;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Carbon\Carbon;

class AttendanceConstraintsIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $testUser;
    private Company $testCompany;
    private ManagementHierarchy $testBranch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company
        $this->testCompany = Company::factory()->create([
            'name' => 'Test Company',
            'status' => 'active'
        ]);

        // Create test branch
        $this->testBranch = ManagementHierarchy::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'Main Branch',
            'type' => 'branch',
            'parent_id' => null
        ]);

        // Create test user
        $this->testUser = User::factory()->create([
            'company_id' => $this->testCompany->id,
            'management_hierarchy_id' => $this->testBranch->id,
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        // Authenticate the user
        $this->actingAs($this->testUser);
    }

    /**
     * Test 1: Successful check-in without constraints
     */
    public function test_successful_checkin_without_constraints(): void
    {
        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Regular check-in'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'company_id',
                        'clock_in_time',
                        'status'
                    ]
                ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->testUser->id,
            'company_id' => $this->testCompany->id,
            'status' => 'clocked_in'
        ]);
    }

    /**
     * Test 2: Check-in blocked by geofencing constraint
     */
    public function test_checkin_blocked_by_geofencing_constraint(): void
    {
        // Create geofencing constraint
        $constraint = AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'constraint_config' => [
                'allowed_zones' => [
                    [
                        'name' => 'Office Zone',
                        'latitude' => 40.7128,
                        'longitude' => -74.0060,
                        'radius' => 100 // 100 meters
                    ]
                ]
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // Try to check-in from outside the geofenced area
        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7500, // Far from allowed zone
            'longitude' => -74.0500,
            'notes' => 'Outside geofence'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'description' => 'Clock-in blocked due to constraint violations'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'violations' => [
                            '*' => [
                                'constraint_type',
                                'violation_type',
                                'severity',
                                'message',
                                'details'
                            ]
                        ]
                    ]
                ]);

        // Verify no attendance record was created
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->testUser->id,
            'status' => 'clocked_in'
        ]);
    }

    /**
     * Test 3: Successful check-in within geofencing constraint
     */
    public function test_successful_checkin_within_geofencing_constraint(): void
    {
        // Create geofencing constraint
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'constraint_config' => [
                'allowed_zones' => [
                    [
                        'name' => 'Office Zone',
                        'latitude' => 40.7128,
                        'longitude' => -74.0060,
                        'radius' => 100
                    ]
                ]
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // Check-in within the geofenced area
        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128, // Exact location
            'longitude' => -74.0060,
            'notes' => 'Within geofence'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully clocked in'
                ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->testUser->id,
            'status' => 'clocked_in'
        ]);
    }

    /**
     * Test 4: Check-in with time constraint violation (non-blocking)
     */
    public function test_checkin_with_time_constraint_violation_non_blocking(): void
    {
        // Create time constraint for working hours
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_TIME,
            'constraint_name' => AttendanceConstraint::TIME_WORKING_HOURS,
            'constraint_config' => [
                'start_time' => '09:00',
                'end_time' => '17:00',
                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
            ],
            'is_active' => true,
            'priority' => 2,
            'blocking' => false // Non-blocking constraint
        ]);

        // Mock current time to be outside working hours (early morning)
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 6, 0, 0)); // Monday 6:00 AM

        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Early check-in'
        ]);

        // Should succeed but create violation record
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Successfully clocked in'
                ]);

        // Verify attendance was created
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->testUser->id,
            'status' => 'clocked_in'
        ]);

        // Verify violation was recorded
        $this->assertDatabaseHas('attendance_constraint_violations', [
            'user_id' => $this->testUser->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
            'status' => 'pending'
        ]);

        Carbon::setTestNow(); // Reset time
    }

    /**
     * Test 5: Multiple constraints with different priorities
     */
    public function test_multiple_constraints_with_priorities(): void
    {
        // Create high priority blocking geofencing constraint
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'constraint_config' => [
                'allowed_zones' => [
                    [
                        'name' => 'Office Zone',
                        'latitude' => 40.7128,
                        'longitude' => -74.0060,
                        'radius' => 100
                    ]
                ]
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // Create lower priority non-blocking time constraint
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_TIME,
            'constraint_name' => AttendanceConstraint::TIME_WORKING_HOURS,
            'constraint_config' => [
                'start_time' => '09:00',
                'end_time' => '17:00',
                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
            ],
            'is_active' => true,
            'priority' => 2,
            'blocking' => false
        ]);

        // Try to check-in within geofence but outside working hours
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 6, 0, 0)); // Monday 6:00 AM

        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128, // Within geofence
            'longitude' => -74.0060,
            'notes' => 'Early but in location'
        ]);

        // Should succeed (geofence passed, time violation recorded)
        $response->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->testUser->id,
            'status' => 'clocked_in'
        ]);

        $this->assertDatabaseHas('attendance_constraint_violations', [
            'user_id' => $this->testUser->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test 6: Branch-specific constraints
     */
    public function test_branch_specific_constraints(): void
    {
        // Create another branch
        $otherBranch = ManagementHierarchy::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'Other Branch',
            'type' => 'branch'
        ]);

        // Create constraint specific to other branch
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'branch_id' => $otherBranch->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'constraint_config' => [
                'allowed_zones' => [
                    [
                        'name' => 'Other Office',
                        'latitude' => 41.0000,
                        'longitude' => -75.0000,
                        'radius' => 50
                    ]
                ]
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // User from main branch should not be affected by other branch constraint
        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Different branch location'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->testUser->id,
            'status' => 'clocked_in'
        ]);
    }

    /**
     * Test 7: Branch locations constraint validation
     */
    public function test_branch_locations_constraint_validation(): void
    {
        // Create constraint with branch locations
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'branch_id' => $this->testBranch->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'branch_locations' => [
                $this->testBranch->id => [
                    'name' => 'Main Office',
                    'address' => '123 Main St, New York, NY',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'radius' => 100
                ]
            ],
            'constraint_config' => [
                'use_branch_locations' => true
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // Check-in within branch location
        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'At branch location'
        ]);

        $response->assertStatus(200);

        // Check-in outside branch location
        $response = $this->postJson('/api/attendance/clock-out', [
            'latitude' => 41.0000,
            'longitude' => -75.0000,
            'notes' => 'Outside branch location'
        ]);

        // Should still work for clock-out (depending on constraint configuration)
        $response->assertStatus(200);
    }

    /**
     * Test 8: IP restriction constraint
     */
    public function test_ip_restriction_constraint(): void
    {
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_IP_RESTRICTION,
            'constraint_config' => [
                'allowed_ips' => ['192.168.1.100', '10.0.0.50'],
                'allowed_ranges' => ['192.168.1.0/24']
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // Mock request with allowed IP
        $this->app['request']->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->postJson('/api/attendance/clock-in', [
            'notes' => 'From allowed IP'
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test 9: Multiple periods per day constraint
     */
    public function test_multiple_periods_constraint(): void
    {
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_TIME,
            'constraint_name' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
            'constraint_config' => [
                'weekly_schedule' => [
                    'monday' => [
                        'enabled' => true,
                        'periods' => [
                            [
                                'name' => 'Morning Shift',
                                'start_time' => '09:00',
                                'end_time' => '13:00',
                                'spans_next_day' => false,
                                'grace_before' => 15,
                                'grace_after' => 15
                            ],
                            [
                                'name' => 'Afternoon Shift',
                                'start_time' => '14:00',
                                'end_time' => '18:00',
                                'spans_next_day' => false,
                                'grace_before' => 15,
                                'grace_after' => 15
                            ]
                        ]
                    ]
                ]
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => false
        ]);

        // Test check-in during valid period
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 9, 10, 0)); // Monday 9:10 AM

        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Morning shift'
        ]);

        $response->assertStatus(200);

        // Test check-in during invalid period
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 13, 30, 0)); // Monday 1:30 PM (between periods)

        $response = $this->postJson('/api/attendance/clock-out');
        $response->assertStatus(200);

        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Between periods'
        ]);

        // Should create violation but allow check-in (non-blocking)
        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_constraint_violations', [
            'user_id' => $this->testUser->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test 10: Constraint inheritance from parent branch
     */
    public function test_constraint_inheritance_from_parent(): void
    {
        // Create parent branch
        $parentBranch = ManagementHierarchy::factory()->create([
            'company_id' => $this->testCompany->id,
            'name' => 'Regional Office',
            'type' => 'branch',
            'parent_id' => null
        ]);

        // Update test branch to be child of parent
        $this->testBranch->update(['parent_id' => $parentBranch->id]);

        // Create constraint on parent branch with inheritance
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'branch_id' => $parentBranch->id,
            'constraint_type' => AttendanceConstraint::TYPE_TIME,
            'constraint_name' => AttendanceConstraint::TIME_WORKING_HOURS,
            'constraint_config' => [
                'start_time' => '08:00',
                'end_time' => '16:00',
                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
            ],
            'inherit_from_parent' => true,
            'is_active' => true,
            'priority' => 1,
            'blocking' => false
        ]);

        // User in child branch should inherit parent constraint
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 7, 0, 0)); // Monday 7:00 AM (before working hours)

        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Early check-in'
        ]);

        $response->assertStatus(200);

        // Should create violation due to inherited constraint
        $this->assertDatabaseHas('attendance_constraint_violations', [
            'user_id' => $this->testUser->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test 11: Clock-out with constraint validation
     */
    public function test_clockout_with_constraint_validation(): void
    {
        // First clock in
        $attendance = Attendance::factory()->create([
            'user_id' => $this->testUser->id,
            'company_id' => $this->testCompany->id,
            'clock_in_time' => Carbon::now()->subHours(8),
            'status' => 'clocked_in'
        ]);

        // Create constraint for minimum work hours
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_TIME,
            'constraint_name' => AttendanceConstraint::TIME_MINIMUM_HOURS,
            'constraint_config' => [
                'minimum_hours' => 8
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => false
        ]);

        // Try to clock out early (after 4 hours)
        Carbon::setTestNow($attendance->clock_in_time->addHours(4));

        $response = $this->postJson('/api/attendance/clock-out', [
            'notes' => 'Early departure'
        ]);

        $response->assertStatus(200);

        // Should create violation for early departure
        $this->assertDatabaseHas('attendance_constraint_violations', [
            'user_id' => $this->testUser->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test 12: Constraint validation endpoint
     */
    public function test_constraint_validation_endpoint(): void
    {
        // Create geofencing constraint
        AttendanceConstraint::factory()->create([
            'company_id' => $this->testCompany->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'constraint_config' => [
                'allowed_zones' => [
                    [
                        'name' => 'Office Zone',
                        'latitude' => 40.7128,
                        'longitude' => -74.0060,
                        'radius' => 100
                    ]
                ]
            ],
            'is_active' => true,
            'priority' => 1,
            'blocking' => true
        ]);

        // Test validation endpoint
        $response = $this->postJson('/api/attendance/constraints/validate', [
            'user_id' => $this->testUser->id,
            'clock_in_time' => now()->toISOString(),
            'clock_in_location' => [
                'latitude' => 40.7500,
                'longitude' => -74.0500
            ]
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'violations',
                        'can_proceed'
                    ]
                ]);
    }

    /**
     * Test 13: Constraint statistics and reporting
     */
    public function test_constraint_statistics(): void
    {
        // Create some violations
        AttendanceConstraintViolation::factory()->count(3)->create([
            'user_id' => $this->testUser->id,
            'company_id' => $this->testCompany->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_LOCATION_VIOLATION,
            'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
            'status' => 'pending'
        ]);

        AttendanceConstraintViolation::factory()->count(2)->create([
            'user_id' => $this->testUser->id,
            'company_id' => $this->testCompany->id,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
            'severity' => AttendanceConstraintViolation::SEVERITY_MEDIUM,
            'status' => 'resolved'
        ]);

        $response = $this->getJson('/api/attendance/constraints/violations/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_violations',
                        'pending_violations',
                        'resolved_violations',
                        'by_severity',
                        'by_type'
                    ]
                ]);
    }

    /**
     * Test 14: Performance test with multiple constraints
     */
    public function test_performance_with_multiple_constraints(): void
    {
        // Create multiple constraints of different types
        $constraintTypes = [
            [AttendanceConstraint::TYPE_LOCATION, AttendanceConstraint::LOCATION_GEOFENCING],
            [AttendanceConstraint::TYPE_TIME, AttendanceConstraint::TIME_WORKING_HOURS],
            [AttendanceConstraint::TYPE_DEVICE, AttendanceConstraint::DEVICE_TRUSTED_DEVICES],
            [AttendanceConstraint::TYPE_SECURITY, AttendanceConstraint::SECURITY_TWO_FACTOR],
        ];

        foreach ($constraintTypes as $index => [$type, $name]) {
            AttendanceConstraint::factory()->create([
                'company_id' => $this->testCompany->id,
                'constraint_type' => $type,
                'constraint_name' => $name,
                'constraint_config' => $this->getDefaultConfigForConstraint($type, $name),
                'is_active' => true,
                'priority' => $index + 1,
                'blocking' => false
            ]);
        }

        $startTime = microtime(true);

        $response = $this->postJson('/api/attendance/clock-in', [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Performance test'
        ]);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Assert that constraint validation doesn't take too long (under 500ms)
        $this->assertLessThan(500, $executionTime, 'Constraint validation took too long: ' . $executionTime . 'ms');
    }

    /**
     * Helper method to get default config for constraint types
     */
    private function getDefaultConfigForConstraint(string $type, string $name): array
    {
        switch ($type) {
            case AttendanceConstraint::TYPE_LOCATION:
                if ($name === AttendanceConstraint::LOCATION_GEOFENCING) {
                    return [
                        'allowed_zones' => [
                            [
                                'name' => 'Office',
                                'latitude' => 40.7128,
                                'longitude' => -74.0060,
                                'radius' => 100
                            ]
                        ]
                    ];
                }
                break;
            case AttendanceConstraint::TYPE_TIME:
                if ($name === AttendanceConstraint::TIME_WORKING_HOURS) {
                    return [
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                        'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
                    ];
                }
                break;
            case AttendanceConstraint::TYPE_DEVICE:
                return [
                    'allowed_devices' => ['device1', 'device2']
                ];
            case AttendanceConstraint::TYPE_SECURITY:
                return [
                    'require_2fa' => true
                ];
        }

        return [];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time mocking
        parent::tearDown();
    }
}
