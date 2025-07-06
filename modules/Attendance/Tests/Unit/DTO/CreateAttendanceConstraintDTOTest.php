<?php

namespace Modules\Attendance\Tests\Unit\DTO;

use Tests\TestCase;
use Modules\Attendance\DTO\CreateAttendanceConstraintDTO;

/**
 * Unit tests for CreateAttendanceConstraintDTO with branch locations
 */
class CreateAttendanceConstraintDTOTest extends TestCase
{
    private array $sampleBranchLocations;
    private array $basicDTOData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleBranchLocations = [
            'branch-1-uuid' => [
                'name' => 'Downtown Office',
                'address' => '123 Business Ave, Downtown, NY 10001',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'radius' => 50
            ],
            'branch-2-uuid' => [
                'name' => 'Uptown Branch',
                'address' => '456 Corporate Blvd, Uptown, NY 10002',
                'latitude' => 40.7831,
                'longitude' => -73.9712,
                'radius' => 75
            ]
        ];
        
        $this->basicDTOData = [
            'constraint_type' => 'time_multiple_periods',
            'name' => 'Test Constraint',
            'description' => 'Test Description',
            'config' => ['test' => 'config'],
            'company_id' => 'company-uuid',
            'created_by' => 'user-uuid'
        ];
    }

    /** @test */
    public function it_can_be_created_with_branch_locations()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: $this->sampleBranchLocations
        );

        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
        $this->assertIsArray($dto->getBranchLocations());
        $this->assertCount(2, $dto->getBranchLocations());
    }

    /** @test */
    public function it_can_be_created_without_branch_locations()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by']
        );

        $this->assertNull($dto->getBranchLocations());
    }

    /** @test */
    public function it_can_be_created_with_null_branch_locations()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: null
        );

        $this->assertNull($dto->getBranchLocations());
    }

    /** @test */
    public function it_can_be_created_with_empty_branch_locations_array()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: []
        );

        $this->assertEquals([], $dto->getBranchLocations());
        $this->assertIsArray($dto->getBranchLocations());
        $this->assertCount(0, $dto->getBranchLocations());
    }

    /** @test */
    public function it_includes_branch_locations_in_to_array()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: $this->sampleBranchLocations
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('branch_locations', $array);
        $this->assertEquals($this->sampleBranchLocations, $array['branch_locations']);
    }

    /** @test */
    public function it_includes_null_branch_locations_in_to_array()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: null
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('branch_locations', $array);
        $this->assertNull($array['branch_locations']);
    }

    /** @test */
    public function it_preserves_branch_location_data_structure()
    {
        $complexLocations = [
            'branch-1-uuid' => [
                'name' => 'Complex Office',
                'address' => '123 Complex St, Suite 456, City 12345',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'radius' => 100,
                'additional_data' => 'some extra info'
            ]
        ];

        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: $complexLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals($complexLocations, $retrievedLocations);
        $this->assertEquals('some extra info', $retrievedLocations['branch-1-uuid']['additional_data']);
    }

    /** @test */
    public function it_handles_branch_locations_with_all_optional_fields()
    {
        $minimalLocations = [
            'branch-1-uuid' => [
                'name' => 'Minimal Office'
            ],
            'branch-2-uuid' => [
                'name' => 'Another Office',
                'latitude' => 40.7128,
                'longitude' => -74.0060
            ]
        ];

        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: $minimalLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals($minimalLocations, $retrievedLocations);
        $this->assertEquals('Minimal Office', $retrievedLocations['branch-1-uuid']['name']);
        $this->assertArrayNotHasKey('address', $retrievedLocations['branch-1-uuid']);
    }

    /** @test */
    public function it_works_with_all_constructor_parameters_including_branch_locations()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: 'time_multiple_periods',
            name: 'Full Test Constraint',
            description: 'Full test description',
            config: ['full' => 'config'],
            company_id: 'company-uuid',
            created_by: 'creator-uuid',
            user_id: 'user-uuid',
            department_id: 'dept-uuid',
            branch_ids: ['branch-1', 'branch-2'],
            branch_locations: $this->sampleBranchLocations,
            priority: 5,
            is_active: true,
            inherit_from_parent: false,
            effective_from: '2024-01-01',
            effective_to: '2024-12-31'
        );

        $this->assertEquals('time_multiple_periods', $dto->getConstraintType());
        $this->assertEquals('Full Test Constraint', $dto->getName());
        $this->assertEquals(['branch-1', 'branch-2'], $dto->getBranchIds());
        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
        $this->assertEquals(5, $dto->getPriority());
        $this->assertTrue($dto->isActive());
    }

    /** @test */
    public function it_maintains_parameter_order_in_constructor()
    {
        // Test that branch_locations is in the correct position
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: 'time_range',
            name: 'Order Test',
            description: 'Test description',
            config: [],
            company_id: 'company-uuid',
            created_by: 'creator-uuid',
            user_id: null,
            department_id: null,
            branch_ids: ['branch-1'],
            branch_locations: $this->sampleBranchLocations // Should be after branch_ids
        );

        $this->assertEquals(['branch-1'], $dto->getBranchIds());
        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
    }

    /** @test */
    public function it_converts_to_array_with_all_fields_including_branch_locations()
    {
        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: 'time_multiple_periods',
            name: 'Array Test Constraint',
            description: 'Array test description',
            config: ['test' => 'config'],
            company_id: 'company-uuid',
            created_by: 'creator-uuid',
            user_id: 'user-uuid',
            department_id: 'dept-uuid',
            branch_ids: ['branch-1', 'branch-2'],
            branch_locations: $this->sampleBranchLocations,
            priority: 3,
            is_active: false,
            inherit_from_parent: true,
            effective_from: '2024-06-01',
            effective_to: '2024-12-31'
        );

        $array = $dto->toArray();

        $expectedKeys = [
            'constraint_type', 'constraint_name', 'description', 'constraint_config',
            'company_id', 'created_by', 'user_id', 'department_id', 'branch_ids',
            'branch_locations', 'priority', 'is_active', 'inherit_from_parent',
            'start_date', 'end_date'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array);
        }

        $this->assertEquals('time_multiple_periods', $array['constraint_type']);
        $this->assertEquals('Array Test Constraint', $array['constraint_name']);
        $this->assertEquals(['branch-1', 'branch-2'], $array['branch_ids']);
        $this->assertEquals($this->sampleBranchLocations, $array['branch_locations']);
        $this->assertEquals(3, $array['priority']);
        $this->assertFalse($array['is_active']);
        $this->assertTrue($array['inherit_from_parent']);
    }

    /** @test */
    public function it_handles_large_branch_locations_data()
    {
        $largeBranchLocations = [];
        
        // Create 50 branch locations
        for ($i = 1; $i <= 50; $i++) {
            $largeBranchLocations["branch-{$i}-uuid"] = [
                'name' => "Office {$i}",
                'address' => "{$i} Test Street, City {$i}, State 1234{$i}",
                'latitude' => 40.0 + ($i * 0.001),
                'longitude' => -74.0 + ($i * 0.001),
                'radius' => 50 + $i
            ];
        }

        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: $largeBranchLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertCount(50, $retrievedLocations);
        $this->assertEquals('Office 25', $retrievedLocations['branch-25-uuid']['name']);
        $this->assertEquals(75, $retrievedLocations['branch-25-uuid']['radius']);
    }

    /** @test */
    public function it_handles_unicode_characters_in_branch_locations()
    {
        $unicodeLocations = [
            'branch-moscow-uuid' => [
                'name' => 'Офис в Москве',
                'address' => 'Красная площадь, 1, Москва 101000',
                'latitude' => 55.7558,
                'longitude' => 37.6176,
                'radius' => 100
            ],
            'branch-tokyo-uuid' => [
                'name' => '東京オフィス',
                'address' => '東京都渋谷区渋谷1-1-1',
                'latitude' => 35.6762,
                'longitude' => 139.6503,
                'radius' => 80
            ]
        ];

        $dto = new CreateAttendanceConstraintDTO(
            constraint_type: $this->basicDTOData['constraint_type'],
            name: $this->basicDTOData['name'],
            description: $this->basicDTOData['description'],
            config: $this->basicDTOData['config'],
            company_id: $this->basicDTOData['company_id'],
            created_by: $this->basicDTOData['created_by'],
            branch_locations: $unicodeLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals('Офис в Москве', $retrievedLocations['branch-moscow-uuid']['name']);
        $this->assertEquals('東京オフィス', $retrievedLocations['branch-tokyo-uuid']['name']);
    }
}
