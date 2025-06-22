<?php

namespace Modules\Attendance\Tests\Unit\Models;

use Tests\TestCase;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Unit tests for AttendanceConstraint branch locations functionality
 */
class AttendanceConstraintBranchLocationsTest extends TestCase
{
    private AttendanceConstraint $constraint;
    private array $sampleLocation;
    private array $sampleLocations;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->constraint = new AttendanceConstraint();
        
        $this->sampleLocation = [
            'name' => 'Downtown Office',
            'address' => '123 Business Ave, Downtown, NY 10001',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius' => 50
        ];
        
        $this->sampleLocations = [
            'branch-1-uuid' => [
                'name' => 'Headquarters',
                'address' => '100 Main St, City 12345',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'radius' => 75
            ],
            'branch-2-uuid' => [
                'name' => 'Branch Office',
                'address' => '200 Side St, City 54321',
                'latitude' => 40.7500,
                'longitude' => -73.9800,
                'radius' => 100
            ],
            'branch-3-uuid' => [
                'name' => 'Remote Hub',
                'address' => '300 Work Ave, City 98765',
                'latitude' => 40.6892,
                'longitude' => -73.9442,
                'radius' => 60
            ]
        ];
    }

    /** @test */
    public function it_can_set_branch_location()
    {
        $branchId = 'test-branch-uuid';
        
        $this->constraint->setBranchLocation($branchId, $this->sampleLocation);
        
        $this->assertEquals($this->sampleLocation, $this->constraint->getBranchLocation($branchId));
        $this->assertTrue($this->constraint->hasBranchLocation($branchId));
    }

    /** @test */
    public function it_can_get_branch_location()
    {
        $branchId = 'test-branch-uuid';
        $this->constraint->branch_locations = [$branchId => $this->sampleLocation];
        
        $location = $this->constraint->getBranchLocation($branchId);
        
        $this->assertEquals($this->sampleLocation, $location);
        $this->assertEquals('Downtown Office', $location['name']);
        $this->assertEquals(40.7128, $location['latitude']);
        $this->assertEquals(-74.0060, $location['longitude']);
        $this->assertEquals(50, $location['radius']);
    }

    /** @test */
    public function it_returns_null_for_non_existent_branch_location()
    {
        $location = $this->constraint->getBranchLocation('non-existent-uuid');
        
        $this->assertNull($location);
    }

    /** @test */
    public function it_can_check_if_branch_has_location()
    {
        $branchId = 'test-branch-uuid';
        
        // Initially should not have location
        $this->assertFalse($this->constraint->hasBranchLocation($branchId));
        
        // After setting location
        $this->constraint->setBranchLocation($branchId, $this->sampleLocation);
        $this->assertTrue($this->constraint->hasBranchLocation($branchId));
    }

    /** @test */
    public function it_can_remove_branch_location()
    {
        $branchId = 'test-branch-uuid';
        
        // Set location first
        $this->constraint->setBranchLocation($branchId, $this->sampleLocation);
        $this->assertTrue($this->constraint->hasBranchLocation($branchId));
        
        // Remove location
        $this->constraint->removeBranchLocation($branchId);
        $this->assertFalse($this->constraint->hasBranchLocation($branchId));
        $this->assertNull($this->constraint->getBranchLocation($branchId));
    }

    /** @test */
    public function it_can_get_all_branch_locations()
    {
        $this->constraint->branch_locations = $this->sampleLocations;
        
        $allLocations = $this->constraint->getAllBranchLocations();
        
        $this->assertEquals($this->sampleLocations, $allLocations);
        $this->assertCount(3, $allLocations);
        $this->assertArrayHasKey('branch-1-uuid', $allLocations);
        $this->assertArrayHasKey('branch-2-uuid', $allLocations);
        $this->assertArrayHasKey('branch-3-uuid', $allLocations);
    }

    /** @test */
    public function it_returns_empty_array_when_no_branch_locations_exist()
    {
        $allLocations = $this->constraint->getAllBranchLocations();
        
        $this->assertEquals([], $allLocations);
        $this->assertCount(0, $allLocations);
    }

    /** @test */
    public function it_can_set_multiple_branch_locations()
    {
        $this->constraint->setBranchLocations($this->sampleLocations);
        
        $this->assertEquals($this->sampleLocations, $this->constraint->getAllBranchLocations());
        
        // Test individual locations
        foreach ($this->sampleLocations as $branchId => $location) {
            $this->assertTrue($this->constraint->hasBranchLocation($branchId));
            $this->assertEquals($location, $this->constraint->getBranchLocation($branchId));
        }
    }

    /** @test */
    public function it_overwrites_existing_locations_when_setting_multiple()
    {
        // Set initial locations
        $initialLocations = [
            'old-branch-uuid' => [
                'name' => 'Old Office',
                'address' => 'Old Address',
                'latitude' => 30.0,
                'longitude' => -80.0,
                'radius' => 25
            ]
        ];
        $this->constraint->setBranchLocations($initialLocations);
        
        // Set new locations (should overwrite)
        $this->constraint->setBranchLocations($this->sampleLocations);
        
        $this->assertEquals($this->sampleLocations, $this->constraint->getAllBranchLocations());
        $this->assertFalse($this->constraint->hasBranchLocation('old-branch-uuid'));
    }

    /** @test */
    public function it_handles_empty_branch_locations_array()
    {
        // Set some locations first
        $this->constraint->setBranchLocations($this->sampleLocations);
        $this->assertCount(3, $this->constraint->getAllBranchLocations());
        
        // Set empty array
        $this->constraint->setBranchLocations([]);
        
        $this->assertEquals([], $this->constraint->getAllBranchLocations());
        $this->assertCount(0, $this->constraint->getAllBranchLocations());
    }

    /** @test */
    public function it_can_update_existing_branch_location()
    {
        $branchId = 'test-branch-uuid';
        
        // Set initial location
        $this->constraint->setBranchLocation($branchId, $this->sampleLocation);
        
        // Update location
        $updatedLocation = [
            'name' => 'Updated Office Name',
            'address' => '456 New Address, Updated City 67890',
            'latitude' => 41.0000,
            'longitude' => -75.0000,
            'radius' => 80
        ];
        $this->constraint->setBranchLocation($branchId, $updatedLocation);
        
        $retrievedLocation = $this->constraint->getBranchLocation($branchId);
        $this->assertEquals($updatedLocation, $retrievedLocation);
        $this->assertEquals('Updated Office Name', $retrievedLocation['name']);
        $this->assertEquals(80, $retrievedLocation['radius']);
    }

    /** @test */
    public function it_handles_partial_location_data()
    {
        $branchId = 'test-branch-uuid';
        $partialLocation = [
            'name' => 'Minimal Office',
            'latitude' => 40.7128,
            'longitude' => -74.0060
            // Missing address and radius
        ];
        
        $this->constraint->setBranchLocation($branchId, $partialLocation);
        
        $retrievedLocation = $this->constraint->getBranchLocation($branchId);
        $this->assertEquals($partialLocation, $retrievedLocation);
        $this->assertEquals('Minimal Office', $retrievedLocation['name']);
        $this->assertArrayNotHasKey('address', $retrievedLocation);
        $this->assertArrayNotHasKey('radius', $retrievedLocation);
    }

    /** @test */
    public function it_preserves_location_data_types()
    {
        $branchId = 'test-branch-uuid';
        $locationWithTypes = [
            'name' => 'Type Test Office',
            'address' => '123 Type St',
            'latitude' => 40.7128,    // float
            'longitude' => -74.0060,  // float
            'radius' => 50            // integer
        ];
        
        $this->constraint->setBranchLocation($branchId, $locationWithTypes);
        $retrievedLocation = $this->constraint->getBranchLocation($branchId);
        
        $this->assertIsString($retrievedLocation['name']);
        $this->assertIsString($retrievedLocation['address']);
        $this->assertIsFloat($retrievedLocation['latitude']);
        $this->assertIsFloat($retrievedLocation['longitude']);
        $this->assertIsInt($retrievedLocation['radius']);
    }

    /** @test */
    public function it_handles_location_with_extreme_coordinates()
    {
        $branchId = 'test-branch-uuid';
        $extremeLocation = [
            'name' => 'Extreme Location',
            'latitude' => 89.9999,   // Near North Pole
            'longitude' => 179.9999, // Near International Date Line
            'radius' => 1000
        ];
        
        $this->constraint->setBranchLocation($branchId, $extremeLocation);
        $retrievedLocation = $this->constraint->getBranchLocation($branchId);
        
        $this->assertEquals($extremeLocation, $retrievedLocation);
        $this->assertEquals(89.9999, $retrievedLocation['latitude']);
        $this->assertEquals(179.9999, $retrievedLocation['longitude']);
    }

    /** @test */
    public function it_can_handle_large_number_of_branch_locations()
    {
        $manyLocations = [];
        
        // Create 100 branch locations
        for ($i = 1; $i <= 100; $i++) {
            $branchId = "branch-{$i}-uuid";
            $manyLocations[$branchId] = [
                'name' => "Office {$i}",
                'address' => "{$i} Test Street, City {$i}",
                'latitude' => 40.0 + ($i * 0.001), // Slight variation
                'longitude' => -74.0 + ($i * 0.001),
                'radius' => 50 + $i
            ];
        }
        
        $this->constraint->setBranchLocations($manyLocations);
        
        $retrievedLocations = $this->constraint->getAllBranchLocations();
        $this->assertCount(100, $retrievedLocations);
        
        // Test random locations
        $this->assertTrue($this->constraint->hasBranchLocation('branch-50-uuid'));
        $this->assertEquals('Office 50', $this->constraint->getBranchLocation('branch-50-uuid')['name']);
        $this->assertEquals(100, $this->constraint->getBranchLocation('branch-50-uuid')['radius']);
    }

    /** @test */
    public function it_maintains_branch_locations_when_other_properties_change()
    {
        $branchId = 'test-branch-uuid';
        $this->constraint->setBranchLocation($branchId, $this->sampleLocation);
        
        // Change other properties
        $this->constraint->constraint_name = 'Updated Constraint Name';
        $this->constraint->is_active = false;
        
        // Location should remain unchanged
        $this->assertTrue($this->constraint->hasBranchLocation($branchId));
        $this->assertEquals($this->sampleLocation, $this->constraint->getBranchLocation($branchId));
    }

    /** @test */
    public function it_handles_unicode_characters_in_location_names_and_addresses()
    {
        $branchId = 'test-branch-uuid';
        $unicodeLocation = [
            'name' => 'Офис в Москве', // Russian
            'address' => '123 Улица Пушкина, Москва 101000', // Russian address
            'latitude' => 55.7558,
            'longitude' => 37.6176,
            'radius' => 100
        ];
        
        $this->constraint->setBranchLocation($branchId, $unicodeLocation);
        $retrievedLocation = $this->constraint->getBranchLocation($branchId);
        
        $this->assertEquals($unicodeLocation, $retrievedLocation);
        $this->assertEquals('Офис в Москве', $retrievedLocation['name']);
        $this->assertEquals('123 Улица Пушкина, Москва 101000', $retrievedLocation['address']);
    }

    /** @test */
    public function it_handles_special_characters_in_location_data()
    {
        $branchId = 'test-branch-uuid';
        $specialLocation = [
            'name' => 'Office & Co. (Main)',
            'address' => '123 "Main" St., Apt. #456, City-Town 12345-6789',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'radius' => 75
        ];
        
        $this->constraint->setBranchLocation($branchId, $specialLocation);
        $retrievedLocation = $this->constraint->getBranchLocation($branchId);
        
        $this->assertEquals($specialLocation, $retrievedLocation);
        $this->assertEquals('Office & Co. (Main)', $retrievedLocation['name']);
        $this->assertStringContains('"Main"', $retrievedLocation['address']);
        $this->assertStringContains('#456', $retrievedLocation['address']);
    }
}
