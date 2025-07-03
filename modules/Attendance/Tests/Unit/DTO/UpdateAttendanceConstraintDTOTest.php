<?php

namespace Modules\Attendance\Tests\Unit\DTO;

use Tests\TestCase;
use Modules\Attendance\DTO\UpdateAttendanceConstraintDTO;

/**
 * Unit tests for UpdateAttendanceConstraintDTO with branch locations
 */
class UpdateAttendanceConstraintDTOTest extends TestCase
{
    private array $sampleBranchLocations;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleBranchLocations = [
            'branch-1-uuid' => [
                'name' => 'Updated Downtown Office',
                'address' => '456 Updated Ave, Downtown, NY 10001',
                'latitude' => 40.7200,
                'longitude' => -74.0100,
                'radius' => 60
            ],
            'branch-2-uuid' => [
                'name' => 'Updated Uptown Branch',
                'address' => '789 Updated Blvd, Uptown, NY 10002',
                'latitude' => 40.7900,
                'longitude' => -73.9800,
                'radius' => 85
            ]
        ];
    }

    /** @test */
    public function it_can_be_created_with_branch_locations()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_multiple_periods',
            name: 'Updated Constraint',
            description: 'Updated Description',
            config: ['updated' => 'config'],
            branch_locations: $this->sampleBranchLocations
        );

        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
        $this->assertIsArray($dto->getBranchLocations());
        $this->assertCount(2, $dto->getBranchLocations());
    }

    /** @test */
    public function it_can_be_created_without_branch_locations()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_range',
            name: 'Test Update',
            description: 'Test Description',
            config: ['test' => 'config']
        );

        $this->assertNull($dto->getBranchLocations());
    }

    /** @test */
    public function it_can_be_created_with_null_branch_locations()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_range',
            name: 'Test Update',
            description: 'Test Description',
            config: ['test' => 'config'],
            branch_locations: null
        );

        $this->assertNull($dto->getBranchLocations());
    }

    /** @test */
    public function it_can_be_created_with_empty_branch_locations_array()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_range',
            name: 'Test Update',
            description: 'Test Description',
            config: ['test' => 'config'],
            branch_locations: []
        );

        $this->assertEquals([], $dto->getBranchLocations());
        $this->assertIsArray($dto->getBranchLocations());
        $this->assertCount(0, $dto->getBranchLocations());
    }

    /** @test */
    public function it_includes_branch_locations_in_to_array()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_multiple_periods',
            name: 'Array Test',
            description: 'Array Description',
            config: ['array' => 'config'],
            branch_locations: $this->sampleBranchLocations
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('branch_locations', $array);
        $this->assertEquals($this->sampleBranchLocations, $array['branch_locations']);
    }

    /** @test */
    public function it_includes_null_branch_locations_in_to_array()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_range',
            name: 'Null Test',
            description: 'Null Description',
            config: ['null' => 'config'],
            branch_locations: null
        );

        $array = $dto->toArray();

        $this->assertArrayNotHasKey('branch_locations', $array);
    }

    /** @test */
    public function it_can_update_only_branch_locations()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $this->sampleBranchLocations
        );

        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
        $this->assertNull($dto->getConstraintType());
        $this->assertNull($dto->getName());
        $this->assertNull($dto->getDescription());
    }

    /** @test */
    public function it_can_clear_branch_locations_with_empty_array()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: []
        );

        $this->assertEquals([], $dto->getBranchLocations());
        $this->assertIsArray($dto->getBranchLocations());
        $this->assertCount(0, $dto->getBranchLocations());
    }

    /** @test */
    public function it_preserves_branch_location_data_structure_in_updates()
    {
        $complexLocations = [
            'branch-1-uuid' => [
                'name' => 'Complex Updated Office',
                'address' => '999 Complex Updated St, Suite 789, City 54321',
                'latitude' => 41.0000,
                'longitude' => -75.0000,
                'radius' => 120,
                'updated_field' => 'new value',
                'nested' => [
                    'data' => 'structure'
                ]
            ]
        ];

        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $complexLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals($complexLocations, $retrievedLocations);
        $this->assertEquals('new value', $retrievedLocations['branch-1-uuid']['updated_field']);
        $this->assertEquals(['data' => 'structure'], $retrievedLocations['branch-1-uuid']['nested']);
    }

    /** @test */
    public function it_works_with_all_constructor_parameters_including_branch_locations()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_multiple_periods',
            name: 'Full Update Test',
            description: 'Full update description',
            config: ['full' => 'update'],
            user_id: 'user-uuid',
            department_id: 'dept-uuid',
            branch_ids: ['updated-branch-1', 'updated-branch-2'],
            branch_locations: $this->sampleBranchLocations,
            priority: 8,
            is_active: false,
            inherit_from_parent: true,
            effective_from: '2024-07-01',
            effective_to: '2024-12-31'
        );

        $this->assertEquals('time_multiple_periods', $dto->getConstraintType());
        $this->assertEquals('Full Update Test', $dto->getName());
        $this->assertEquals(['updated-branch-1', 'updated-branch-2'], $dto->getBranchIds());
        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
        $this->assertEquals(8, $dto->getPriority());
        $this->assertFalse($dto->isActive());
        $this->assertTrue($dto->isInheritFromParent());
    }

    /** @test */
    public function it_maintains_parameter_order_with_branch_locations()
    {
        // Test that branch_locations is in the correct position after branch_ids
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_range',
            name: 'Order Test Update',
            description: 'Order test description',
            config: ['order' => 'test'],
            user_id: null,
            department_id: null,
            branch_ids: ['order-branch-1'],
            branch_locations: $this->sampleBranchLocations // Should be after branch_ids
        );

        $this->assertEquals(['order-branch-1'], $dto->getBranchIds());
        $this->assertEquals($this->sampleBranchLocations, $dto->getBranchLocations());
    }

    /** @test */
    public function it_converts_to_array_with_all_fields_including_branch_locations()
    {
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            constraint_type: 'time_multiple_periods',
            name: 'Array Update Test',
            description: 'Array update description',
            config: ['array' => 'update'],
            user_id: 'user-uuid',
            department_id: 'dept-uuid',
            branch_ids: ['array-branch-1', 'array-branch-2'],
            branch_locations: $this->sampleBranchLocations,
            priority: 6,
            is_active: true,
            inherit_from_parent: false,
            effective_from: '2024-08-01',
            effective_to: '2024-11-30'
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('constraint_type', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('config', $array);
        $this->assertArrayHasKey('updated_by', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('department_id', $array);
        $this->assertArrayHasKey('branch_ids', $array);
        $this->assertArrayHasKey('branch_locations', $array);
        $this->assertArrayHasKey('priority', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('inherit_from_parent', $array);
        $this->assertArrayHasKey('effective_from', $array);
        $this->assertArrayHasKey('effective_to', $array);

        $this->assertEquals('time_multiple_periods', $array['constraint_type']);
        $this->assertEquals('Array Update Test', $array['name']);
        $this->assertEquals(['array-branch-1', 'array-branch-2'], $array['branch_ids']);
        $this->assertEquals($this->sampleBranchLocations, $array['branch_locations']);
        $this->assertEquals(6, $array['priority']);
        $this->assertTrue($array['is_active']);
        $this->assertFalse($array['inherit_from_parent']);
    }

    /** @test */
    public function it_handles_partial_updates_with_branch_locations()
    {
        // Test updating only some fields including branch_locations
        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            name: 'Partial Update',
            branch_locations: $this->sampleBranchLocations,
            is_active: false
        );

        $array = $dto->toArray();

        $this->assertEquals('Partial Update', $array['name']);
        $this->assertEquals($this->sampleBranchLocations, $array['branch_locations']);
        $this->assertFalse($array['is_active']);
        
        $this->assertArrayNotHasKey('constraint_type', $array);
        $this->assertArrayNotHasKey('description', $array);
        $this->assertArrayNotHasKey('config', $array);
    }

    /** @test */
    public function it_handles_branch_locations_with_minimal_data()
    {
        $minimalLocations = [
            'branch-1-uuid' => [
                'name' => 'Minimal Updated Office'
            ],
            'branch-2-uuid' => [
                'name' => 'Another Minimal Office',
                'radius' => 30
            ]
        ];

        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $minimalLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals($minimalLocations, $retrievedLocations);
        $this->assertEquals('Minimal Updated Office', $retrievedLocations['branch-1-uuid']['name']);
        $this->assertArrayNotHasKey('address', $retrievedLocations['branch-1-uuid']);
        $this->assertEquals(30, $retrievedLocations['branch-2-uuid']['radius']);
    }

    /** @test */
    public function it_handles_single_branch_location_update()
    {
        $singleLocation = [
            'branch-single-uuid' => [
                'name' => 'Single Updated Office',
                'address' => '100 Single St, Single City 11111',
                'latitude' => 42.0000,
                'longitude' => -76.0000,
                'radius' => 90
            ]
        ];

        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $singleLocation
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertCount(1, $retrievedLocations);
        $this->assertEquals('Single Updated Office', $retrievedLocations['branch-single-uuid']['name']);
        $this->assertEquals(90, $retrievedLocations['branch-single-uuid']['radius']);
    }

    /** @test */
    public function it_handles_large_branch_locations_update()
    {
        $largeBranchLocations = [];
        
        // Create 30 updated branch locations
        for ($i = 1; $i <= 30; $i++) {
            $largeBranchLocations["updated-branch-{$i}-uuid"] = [
                'name' => "Updated Office {$i}",
                'address' => "Updated {$i} Street, Updated City {$i}, State 5678{$i}",
                'latitude' => 41.0 + ($i * 0.002),
                'longitude' => -75.0 + ($i * 0.002),
                'radius' => 60 + $i
            ];
        }

        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $largeBranchLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertCount(30, $retrievedLocations);
        $this->assertEquals('Updated Office 15', $retrievedLocations['updated-branch-15-uuid']['name']);
        $this->assertEquals(75, $retrievedLocations['updated-branch-15-uuid']['radius']);
    }

    /** @test */
    public function it_handles_unicode_characters_in_updated_branch_locations()
    {
        $unicodeLocations = [
            'branch-paris-uuid' => [
                'name' => 'Bureau de Paris',
                'address' => '123 Rue de la Paix, 75001 Paris, France',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'radius' => 70
            ],
            'branch-berlin-uuid' => [
                'name' => 'Büro in Berlin',
                'address' => 'Unter den Linden 1, 10117 Berlin, Deutschland',
                'latitude' => 52.5200,
                'longitude' => 13.4050,
                'radius' => 65
            ]
        ];

        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $unicodeLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals('Bureau de Paris', $retrievedLocations['branch-paris-uuid']['name']);
        $this->assertEquals('Büro in Berlin', $retrievedLocations['branch-berlin-uuid']['name']);
        $this->assertStringContainsString('Rue de la Paix', $retrievedLocations['branch-paris-uuid']['address']);
    }

    /** @test */
    public function it_handles_extreme_coordinate_updates()
    {
        $extremeLocations = [
            'branch-arctic-uuid' => [
                'name' => 'Arctic Research Station',
                'latitude' => 89.0,
                'longitude' => 0.0,
                'radius' => 500
            ],
            'branch-pacific-uuid' => [
                'name' => 'Pacific Island Office',
                'latitude' => -89.0,
                'longitude' => 179.9999,
                'radius' => 200
            ]
        ];

        $dto = new UpdateAttendanceConstraintDTO(
            updated_by: 'updater-uuid',
            branch_locations: $extremeLocations
        );

        $retrievedLocations = $dto->getBranchLocations();
        $this->assertEquals(89.0, $retrievedLocations['branch-arctic-uuid']['latitude']);
        $this->assertEquals(-89.0, $retrievedLocations['branch-pacific-uuid']['latitude']);
        $this->assertEquals(179.9999, $retrievedLocations['branch-pacific-uuid']['longitude']);
    }
}
