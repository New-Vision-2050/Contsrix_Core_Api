<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Mockery;
use Modules\Attendance\Controllers\AttendanceConstraintController;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraintLocation;
use Modules\Attendance\Repositories\AttendanceConstraintRepository;
use Modules\Attendance\Repositories\AttendanceConstraintViolationRepository;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Tests\TestCase;

class AttendanceConstraintLocationUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private User $actor;

    private AttendanceConstraintService $constraintService;

    private AttendanceConstraintController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::query()->first()
            ?? Country::query()->create([
                'name' => 'Test Country',
                'phonecode' => '20',
                'status' => 1,
            ]);

        $this->company = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Attendance Location Company'],
            'user_name' => 'attendance_location_'.Str::random(6),
            'email' => 'attendance-location-'.Str::random(6).'@example.test',
            'phone' => '01000000000',
            'country_id' => $country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'ATT-LOC-'.Str::upper(Str::random(8)),
        ]));

        tenancy()->initialize($this->company);

        $this->actor = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->actingAs($this->actor, 'api');

        $this->constraintService = Mockery::mock(AttendanceConstraintService::class);
        $this->constraintService
            ->shouldReceive('bumpApplicableConstraintsCacheForCompany')
            ->with((string) $this->company->id)
            ->zeroOrMoreTimes();

        $this->controller = new AttendanceConstraintController(
            $this->constraintService,
            Mockery::mock(AttendanceConstraintRepository::class),
            Mockery::mock(AttendanceConstraintViolationRepository::class),
        );
    }

    public function test_it_updates_additional_location_by_uuid(): void
    {
        $constraint = $this->createConstraint();
        $location = AttendanceConstraintLocation::query()->create([
            'attendance_constraint_id' => $constraint->id,
            'company_id' => $this->company->id,
            'name' => 'Old additional',
            'latitude' => 30.1,
            'longitude' => 31.1,
            'radius' => 100,
            'created_by' => $this->actor->id,
        ]);

        $response = TestResponse::fromBaseResponse($this->controller->updateLocation(
            $this->request([
                'name' => 'Updated additional',
                'latitude' => 30.06574497540264,
                'longitude' => 31.395688214910898,
                'radius' => 200,
            ]),
            (string) $location->id,
        ));

        $response->assertOk()
            ->assertJsonPath('payload.id', (string) $location->id);
        $this->assertSuccessfulLocationPayload($response);

        $this->assertDatabaseHas('attendance_constraint_locations', [
            'id' => $location->id,
            'name' => 'Updated additional',
            'radius' => 200,
        ]);
    }

    public function test_legacy_route_updates_unambiguous_branch_location(): void
    {
        $constraint = $this->createConstraint([
            'branch_locations' => [
                [
                    'branch_id' => '54',
                    'name' => 'Old branch',
                    'latitude' => 30.1,
                    'longitude' => 31.1,
                    'radius' => 100,
                ],
            ],
        ]);

        $response = TestResponse::fromBaseResponse($this->controller->updateLocation(
            $this->request(['radius' => 250]),
            '54',
        ));

        $response->assertOk()
            ->assertJsonPath('payload.id', '54')
            ->assertJsonPath('payload.radius', 250)
            ->assertJsonPath('payload.name', 'Old branch');
        $this->assertSuccessfulLocationPayload($response);

        $this->assertSame(250, $constraint->fresh()->branch_locations[0]['radius']);
    }

    public function test_legacy_route_rejects_ambiguous_branch_location(): void
    {
        $first = $this->createConstraint([
            'branch_locations' => [
                '54' => [
                    'name' => 'First branch',
                    'latitude' => 30.1,
                    'longitude' => 31.1,
                    'radius' => 100,
                ],
            ],
        ]);
        $second = $this->createConstraint([
            'branch_locations' => [
                [
                    'branch_id' => '54',
                    'name' => 'Second branch',
                    'latitude' => 30.2,
                    'longitude' => 31.2,
                    'radius' => 150,
                ],
            ],
        ]);

        $response = TestResponse::fromBaseResponse($this->controller->updateLocation(
            $this->request(['radius' => 250]),
            '54',
        ));

        $response->assertStatus(409)
            ->assertJsonPath('message.code', 'ambiguous_location_id');

        $this->assertSame(100, $first->fresh()->branch_locations['54']['radius']);
        $this->assertSame(150, $second->fresh()->branch_locations[0]['radius']);
    }

    public function test_missing_location_returns_not_found(): void
    {
        $response = TestResponse::fromBaseResponse($this->controller->updateLocation(
            $this->request(['radius' => 250]),
            'missing-location',
        ));

        $response->assertStatus(404)
            ->assertJsonPath('message.code', 'location_not_found');
    }

    private function createConstraint(array $attributes = []): AttendanceConstraint
    {
        return AttendanceConstraint::query()->create(array_merge([
            'company_id' => $this->company->id,
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'constraint_name' => AttendanceConstraint::LOCATION_GEOFENCING,
            'constraint_config' => [],
            'branch_locations' => [],
            'is_active' => true,
            'priority' => 1,
            'created_by' => $this->actor->id,
        ], $attributes));
    }

    private function request(array $payload): Request
    {
        return Request::create('/api/v1/attendance/constraints/locations/54', 'PUT', $payload);
    }

    private function assertSuccessfulLocationPayload(TestResponse $response): void
    {
        $this->assertSame(
            ['id', 'name', 'latitude', 'longitude', 'radius'],
            array_keys($response->json('payload'))
        );
    }
}
