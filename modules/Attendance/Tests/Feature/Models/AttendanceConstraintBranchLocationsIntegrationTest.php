<?php

namespace Modules\Attendance\Tests\Feature\Models;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\Company\CompanyCore\Models\Company;
use Tests\TestCase;
use Modules\Attendance\Models\AttendanceConstraint;

class AttendanceConstraintBranchLocationsIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    private array $sampleLocation;
    private array $sampleLocations;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function createConstraint(array $data = []): AttendanceConstraint
    {
        $company = Company::factory()->create();
        return AttendanceConstraint::factory()->for($company)->create($data);
    }

    /** @test */
    public function it_can_set_and_get_branch_location()
    {
        $constraint = $this->createConstraint();
        $branchId = 'test-branch-uuid';

        $constraint->setBranchLocation($branchId, $this->sampleLocation);
        $constraint->save();

        $freshConstraint = $constraint->fresh();

        $this->assertEquals($this->sampleLocation, $freshConstraint->getBranchLocation($branchId));
        $this->assertTrue($freshConstraint->hasBranchLocation($branchId));
    }

    /** @test */
    public function it_returns_null_for_non_existent_branch_location()
    {
        $constraint = $this->createConstraint();
        $this->assertNull($constraint->getBranchLocation('non-existent-uuid'));
    }

    /** @test */
    public function it_can_remove_branch_location()
    {
        $branchId = 'test-branch-uuid';
        $constraint = $this->createConstraint([
            'branch_locations' => [$branchId => $this->sampleLocation]
        ]);

        $this->assertTrue($constraint->hasBranchLocation($branchId));

        $constraint->removeBranchLocation($branchId);
        $constraint->save();

        $freshConstraint = $constraint->fresh();

        $this->assertFalse($freshConstraint->hasBranchLocation($branchId));
        $this->assertNull($freshConstraint->getBranchLocation($branchId));
    }

    /** @test */
    public function it_can_get_all_branch_locations()
    {
        $constraint = $this->createConstraint([
            'branch_locations' => $this->sampleLocations
        ]);

        $allLocations = $constraint->fresh()->getAllBranchLocations();

        $this->assertEquals($this->sampleLocations, $allLocations);
        $this->assertCount(3, $allLocations);
    }

    /** @test */
    public function it_can_set_multiple_branch_locations_at_once()
    {
        $constraint = $this->createConstraint();
        $constraint->setBranchLocations($this->sampleLocations);
        $constraint->save();

        $freshConstraint = $constraint->fresh();

        $this->assertEquals($this->sampleLocations, $freshConstraint->getAllBranchLocations());
    }

    /** @test */
    public function it_preserves_location_data_types_after_database_storage()
    {
        $branchId = 'test-branch-uuid';
        $constraint = $this->createConstraint([
            'branch_locations' => [
                $branchId => [
                    'name' => 'Type Test Office',
                    'latitude' => 40.7128,    // float
                    'longitude' => -74.0060,  // float
                    'radius' => 50            // integer
                ]
            ]
        ]);

        $retrievedLocation = $constraint->fresh()->getBranchLocation($branchId);

        $this->assertIsString($retrievedLocation['name']);
        $this->assertIsFloat($retrievedLocation['latitude']);
        $this->assertIsFloat($retrievedLocation['longitude']);
        $this->assertIsInt($retrievedLocation['radius']);
    }

    /** @test */
    public function it_handles_unicode_characters_in_location_data()
    {
        $branchId = 'test-branch-uuid';
        $unicodeLocation = [
            'name' => 'Офис в Москве',
            'address' => '123 Улица Пушкина, Москва 101000',
            'latitude' => 55.7558,
            'longitude' => 37.6176,
            'radius' => 100
        ];
        $constraint = $this->createConstraint(['branch_locations' => [$branchId => $unicodeLocation]]);

        $retrievedLocation = $constraint->fresh()->getBranchLocation($branchId);

        $this->assertEquals($unicodeLocation, $retrievedLocation);
    }
}
