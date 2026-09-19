<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\UserLocation;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Spatie\Permission\Models\Permission as SpatiePermission;

class ProjectNotificationEmployeesWithLocationsTest extends BaseAttendanceReportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        setPermissionsTeamId($this->company->id);

        $permission = Permission::PROJECT_NOTIFICATION_CREATE();
        SpatiePermission::firstOrCreate(
            ['name' => $permission, 'guard_name' => 'api'],
            ['name' => $permission, 'guard_name' => 'api', 'company_id' => $this->company->id],
        );

        $this->actor->givePermissionTo($permission);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employees_with_locations_returns_last_location_and_statuses(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();

        $availableUser = $this->createProjectUser('Available User');
        $notConnectedUser = $this->createProjectUser('Not Connected User');
        $outUser = $this->createProjectUser('Out User');
        $offlineUser = $this->createProjectUser('Offline User');
        $noLocationUser = $this->createProjectUser('No Location User');

        foreach ([$availableUser, $notConnectedUser, $outUser, $offlineUser, $noLocationUser] as $user) {
            $this->assignToProject($project, $user);
        }

        $this->createAttendanceWithTracking($availableUser, [
            ['latitude' => 30.0444, 'longitude' => 31.2357, 'timestamp' => '2026-06-23 11:55:00'],
            ['latitude' => 30.0445, 'longitude' => 31.2358, 'timestamp' => '2026-06-23 11:59:00'],
        ]);

        $this->createAttendanceWithTracking($notConnectedUser, [
            ['latitude' => 30.0460, 'longitude' => 31.2370, 'timestamp' => '2026-06-23 10:00:00'],
        ]);

        $this->createAttendanceWithTracking($outUser, [
            ['latitude' => 30.0470, 'longitude' => 31.2380, 'timestamp' => '2026-06-23 09:00:00'],
        ], [
            'clock_out_time' => '2026-06-23 17:00:00',
            'status' => Attendance::STATUS_COMPLETED,
        ]);

        $this->createAttendanceWithTracking($noLocationUser, [], ['clock_in_location' => null]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
            ]));

        $response->assertOk();

        $payload = collect($response->json('payload'));

        $this->assertCount(5, $payload);

        $availableRow = $payload->firstWhere('user_id', (string) $availableUser->id);
        $this->assertSame('available', $availableRow['status']);
        $this->assertSame(30.0445, $availableRow['location']['latitude']);
        $this->assertSame(31.2358, $availableRow['location']['longitude']);
        $this->assertSame('2026-06-23 11:59:00', $availableRow['last_update']);

        $notConnectedRow = $payload->firstWhere('user_id', (string) $notConnectedUser->id);
        $this->assertSame('available', $notConnectedRow['status']);
        $this->assertSame(30.0460, $notConnectedRow['location']['latitude']);
        $this->assertSame('2026-06-23 10:00:00', $notConnectedRow['last_update']);

        $outRow = $payload->firstWhere('user_id', (string) $outUser->id);
        $this->assertSame('out', $outRow['status']);
        $this->assertSame(30.0470, $outRow['location']['latitude']);
        $this->assertSame('2026-06-23 09:00:00', $outRow['last_update']);

        $noLocationRow = $payload->firstWhere('user_id', (string) $noLocationUser->id);
        $this->assertSame('no_location', $noLocationRow['status']);
        $this->assertNull($noLocationRow['location']);

        $offlineRow = $payload->firstWhere('user_id', (string) $offlineUser->id);
        $this->assertSame('offline', $offlineRow['status']);
        $this->assertNull($offlineRow['location']);
    }

    public function test_latest_user_location_is_determined_by_recorded_at_not_uuid_id(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04 15:40:00'));

        $project = $this->createProject();
        $user = $this->createProjectUser('Location User');
        $this->assignToProject($project, $user);

        // UserLocation.id is a UUID and is NOT chronological. The newer record intentionally
        // has a smaller id than the older record, so any MAX(id) logic would pick the stale point.
        // Insert directly so the deterministic ids are stored regardless of UuidTrait behavior.
        DB::table('user_locations')->insert([
            [
                'id' => '00000000-0000-0000-0000-000000000000',
                'user_id' => $user->id,
                'company_id' => $this->company->id,
                'latitude' => 21.7126771,
                'longitude' => 39.2211670,
                'accuracy' => 2.18,
                'location_source' => 'GPS',
                'recorded_at' => '2026-07-04 15:39:35',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'user_id' => $user->id,
                'company_id' => $this->company->id,
                'latitude' => 21.6349003,
                'longitude' => 39.1325632,
                'accuracy' => 2.21,
                'location_source' => 'GPS',
                'recorded_at' => '2026-07-01 12:50:41',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 21.900157203209186,
                'longitude' => 39.20270970649,
            ]));

        $response->assertOk();

        $row = collect($response->json('payload'))->firstWhere('user_id', (string) $user->id);

        $this->assertSame('2026-07-04 15:39:35', $row['last_update']);
        $this->assertEqualsWithDelta(21.7126771, (float) $row['location']['latitude'], 0.0000001);
        $this->assertEqualsWithDelta(39.2211670, (float) $row['location']['longitude'], 0.0000001);
    }

    public function test_radius_filter_excludes_employees_outside_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();
        $nearUser = $this->createProjectUser('Near User');
        $farUser = $this->createProjectUser('Far User');

        $this->assignToProject($project, $nearUser);
        $this->assignToProject($project, $farUser);

        $this->createAttendanceWithTracking($nearUser, [
            ['latitude' => 30.0444, 'longitude' => 31.2357, 'timestamp' => '2026-06-23 11:59:00'],
        ]);

        $this->createAttendanceWithTracking($farUser, [
            ['latitude' => 30.0600, 'longitude' => 31.2500, 'timestamp' => '2026-06-23 11:59:00'],
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'radius' => 500,
            ]));

        $response->assertOk();

        $userIds = collect($response->json('payload'))->pluck('user_id');

        $this->assertTrue($userIds->contains((string) $nearUser->id));
        $this->assertFalse($userIds->contains((string) $farUser->id));
    }

    public function test_employees_with_locations_survives_invalid_timezone_and_bloated_tracking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 16:32:00'));

        $project = $this->createProject();
        $user = $this->createProjectUser('Production-Like User');
        $this->assignToProject($project, $user);

        $bloatedTracking = [];
        for ($i = 0; $i < 200; $i++) {
            $bloatedTracking[] = [
                'latitude' => 21.6000000 + ($i * 0.000001),
                'longitude' => 39.1000000,
                'timestamp' => '2026-09-19 08:00:00',
            ];
        }

        $this->createAttendanceWithTracking($user, $bloatedTracking, [
            'clock_in_time' => '2026-09-19 08:00:00',
            'start_time' => '2026-09-19 08:00:00',
            'business_date' => '2026-09-19',
            'timezone' => '',
        ]);

        UserLocation::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'latitude' => 21.7791920,
            'longitude' => 39.2262200,
            'accuracy' => 8.5,
            'location_source' => 'GPS',
            'recorded_at' => '2026-09-19 16:30:00',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 21.779192,
                'longitude' => 39.22622,
            ]));

        $response->assertOk();

        $row = collect($response->json('payload'))->firstWhere('user_id', (string) $user->id);

        $this->assertSame('available', $row['status']);
        $this->assertEqualsWithDelta(21.779192, (float) $row['location']['latitude'], 0.0000001);
        $this->assertEqualsWithDelta(39.226220, (float) $row['location']['longitude'], 0.0000001);
        $this->assertSame('2026-09-19 16:30:00', $row['last_update']);
        $this->assertSame('08:00:00', $row['attendance']['clock_in_time']);
    }

    public function test_employees_with_locations_survives_non_list_location_tracking_json(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 16:32:00'));

        $project = $this->createProject();
        $user = $this->createProjectUser('Corrupt Tracking User');
        $this->assignToProject($project, $user);

        $attendance = $this->createAttendanceWithTracking($user, [
            ['latitude' => 21.779192, 'longitude' => 39.22622, 'timestamp' => '2026-09-19 16:00:00'],
        ], [
            'clock_in_time' => '2026-09-19 08:00:00',
            'start_time' => '2026-09-19 08:00:00',
            'business_date' => '2026-09-19',
            'timezone' => 'Not/AZone',
        ]);

        DB::table('attendances')
            ->where('id', $attendance->id)
            ->update(['location_tracking' => json_encode(['unexpected' => 'object'])]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 21.779192,
                'longitude' => 39.22622,
            ]));

        $response->assertOk();

        $row = collect($response->json('payload'))->firstWhere('user_id', (string) $user->id);

        $this->assertNotNull($row);
        $this->assertSame((string) $user->id, $row['user_id']);
        $this->assertSame('08:00:00', $row['attendance']['clock_in_time']);
    }

    private function createProject(): ProjectManagement
    {
        $projectTypeId = $this->projectTypeId();

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->withoutGlobalScopes()->forceCreate([
            'id' => (string) Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $projectTypeId,
            'sub_sub_project_type_id' => $projectTypeId,
            'name' => 'Employees With Locations Project',
            'company_id' => $this->company->id,
            'status' => 1,
            'serial_number' => 'PRJ-LOC-'.Str::upper(Str::random(6)),
        ]));
    }

    private function projectTypeId(): int
    {
        return (int) ProjectType::query()->withoutGlobalScopes()->firstOrCreate(
            [
                'name' => 'Employees With Locations Type',
                'company_id' => $this->company->id,
            ],
            [
                'is_created' => true,
                'is_have_schema' => false,
                'is_active' => true,
            ],
        )->id;
    }

    private function createProjectUser(string $name): User
    {
        $globalId = (string) Str::uuid();

        $user = User::factory()->create([
            'name' => $name,
            'company_id' => $this->company->id,
            'global_company_user_id' => $globalId,
        ]);

        UserProfessionalData::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $globalId,
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'management_id' => (string) $this->management->id,
            'department_id' => $this->department->id,
        ]);

        return $user;
    }

    private function assignToProject(ProjectManagement $project, User $user): ProjectEmployee
    {
        return ProjectEmployee::query()->create([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $this->actor->id,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $trackingPoints
     * @param  array<string, mixed>  $overrides
     */
    private function createAttendanceWithTracking(User $user, array $trackingPoints, array $overrides = []): Attendance
    {
        return Attendance::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'clock_in_time' => '2026-06-23 08:00:00',
            'clock_out_time' => null,
            'start_time' => '2026-06-23 08:00:00',
            'business_date' => '2026-06-23',
            'total_work_hours' => 0,
            'total_break_hours' => 0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'is_late' => false,
            'is_absent' => false,
            'is_holiday' => false,
            'status' => Attendance::STATUS_ACTIVE,
            'day_status' => 'in_location',
            'clock_in_location' => [
                'latitude' => 30.0444,
                'longitude' => 31.2357,
            ],
            'location_tracking' => $trackingPoints,
        ], $overrides));
    }
}
