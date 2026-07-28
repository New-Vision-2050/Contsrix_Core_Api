<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\UserLocation;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
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
                'include_unavailable' => true,
            ]));

        $response->assertOk();

        $payload = collect($response->json('payload'));

        $this->assertCount(5, $payload);

        foreach ($payload as $row) {
            $this->assertArrayHasKey('can_clock_in', $row);
            $this->assertArrayHasKey('can_clock_in_until', $row);
            $this->assertArrayHasKey('is_absent', $row);
        }

        $availableRow = $payload->firstWhere('user_id', (string) $availableUser->id);
        $this->assertSame('available', $availableRow['status']);
        $this->assertSame(30.0445, $availableRow['location']['latitude']);
        $this->assertSame(31.2358, $availableRow['location']['longitude']);
        $this->assertSame('2026-06-23 11:59:00', $availableRow['last_update']);
        // Already clocked in: no rules lookup, never eligible.
        $this->assertFalse($availableRow['can_clock_in']);
        $this->assertNull($availableRow['can_clock_in_until']);
        $this->assertFalse($availableRow['is_absent']);

        $notConnectedRow = $payload->firstWhere('user_id', (string) $notConnectedUser->id);
        $this->assertSame('not_connected', $notConnectedRow['status']);
        $this->assertSame(30.0460, $notConnectedRow['location']['latitude']);
        $this->assertSame('2026-06-23 10:00:00', $notConnectedRow['last_update']);

        $outRow = $payload->firstWhere('user_id', (string) $outUser->id);
        $this->assertSame('out', $outRow['status']);
        $this->assertSame(30.0470, $outRow['location']['latitude']);
        $this->assertSame('2026-06-23 09:00:00', $outRow['last_update']);
        $this->assertFalse($outRow['can_clock_in']);

        $noLocationRow = $payload->firstWhere('user_id', (string) $noLocationUser->id);
        $this->assertSame('no_location', $noLocationRow['status']);
        $this->assertNull($noLocationRow['location']);

        $offlineRow = $payload->firstWhere('user_id', (string) $offlineUser->id);
        $this->assertSame('offline', $offlineRow['status']);
        $this->assertNull($offlineRow['location']);
        // No clock-in today and no work rules resolvable: graceful fallback.
        $this->assertTrue($offlineRow['can_clock_in']);
        $this->assertNull($offlineRow['can_clock_in_until']);
        $this->assertFalse($offlineRow['is_absent']);
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

        // last_update is rendered in the branch timezone; derive the expectation the
        // same way so the assertion holds regardless of the test env's APP_TIMEZONE.
        $expectedLastUpdate = Carbon::parse('2026-07-04 15:39:35', 'UTC')
            ->setTimezone(getTimeZoneBranchByRequest())
            ->format('Y-m-d H:i:s');
        $this->assertSame($expectedLastUpdate, $row['last_update']);
        // user_locations uses decimal:7 casts, which return strings on some PDO drivers.
        $this->assertEquals(21.7126771, $row['location']['latitude']);
        $this->assertEquals(39.2211670, $row['location']['longitude']);
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

    public function test_absent_and_out_employees_are_excluded_by_default(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();

        $availableUser = $this->createProjectUser('Available User');
        $absentUser = $this->createProjectUser('Absent User');
        $outUser = $this->createProjectUser('Out User');

        foreach ([$availableUser, $absentUser, $outUser] as $user) {
            $this->assignToProject($project, $user);
        }

        $this->createAttendanceWithTracking($availableUser, [
            ['latitude' => 30.0444, 'longitude' => 31.2357, 'timestamp' => '2026-06-23 11:59:00'],
        ]);

        // Absence marker row: no clock-in, is_absent flag set.
        $this->createAttendanceWithTracking($absentUser, [], [
            'clock_in_time' => null,
            'is_absent' => true,
            'clock_in_location' => null,
            'location_tracking' => [],
            'day_status' => 'absent',
        ]);

        $this->createAttendanceWithTracking($outUser, [
            ['latitude' => 30.0470, 'longitude' => 31.2380, 'timestamp' => '2026-06-23 09:00:00'],
        ], [
            'clock_out_time' => '2026-06-23 17:00:00',
            'status' => Attendance::STATUS_COMPLETED,
        ]);

        $query = http_build_query([
            'project_id' => $project->id,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        // Default: absent and out are hidden.
        $defaultResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.$query);

        $defaultResponse->assertOk();
        $defaultIds = collect($defaultResponse->json('payload'))->pluck('user_id');

        $this->assertTrue($defaultIds->contains((string) $availableUser->id));
        $this->assertFalse($defaultIds->contains((string) $absentUser->id));
        $this->assertFalse($defaultIds->contains((string) $outUser->id));

        // include_unavailable=true: everyone comes back with their status attached.
        $fullResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.$query.'&include_unavailable=1');

        $fullResponse->assertOk();
        $payload = collect($fullResponse->json('payload'));

        $absentRow = $payload->firstWhere('user_id', (string) $absentUser->id);
        $this->assertSame('absent', $absentRow['status']);
        $this->assertSame('غائب', $absentRow['status_label']);
        $this->assertTrue($absentRow['is_absent']);
        $this->assertFalse($absentRow['can_clock_in']);
        $this->assertNull($absentRow['can_clock_in_until']);

        $outRow = $payload->firstWhere('user_id', (string) $outUser->id);
        $this->assertSame('out', $outRow['status']);
        $this->assertFalse($outRow['is_absent']);
    }

    public function test_employee_past_first_clock_in_deadline_is_absent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();

        $deadlineUser = $this->createProjectUser('Deadline Missed User');
        $clockedInUser = $this->createProjectUser('Clocked In User');

        $this->assignToProject($project, $deadlineUser);
        $this->assignToProject($project, $clockedInUser);

        $this->createAttendanceWithTracking($clockedInUser, [
            ['latitude' => 30.0444, 'longitude' => 31.2357, 'timestamp' => '2026-06-23 11:59:00'],
        ]);

        // Shift 08:00–17:00 (Asia/Riyadh), first clock-in deadline 08:00 + 120min = 10:00.
        // At 12:00 UTC (15:00 Riyadh) the deadline has passed with no clock-in.
        $resolverCalls = [];
        $this->mockConstraintResolver($resolverCalls, [
            (string) $deadlineUser->id => $this->workRulesForPeriod('2026-06-23', '08:00', '17:00', [
                'clock_in_deadline_rules' => ['can_clock_in_before_minutes' => 120],
            ]),
        ]);

        $query = http_build_query([
            'project_id' => $project->id,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'include_unavailable' => true,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.$query);

        $response->assertOk();
        $payload = collect($response->json('payload'));

        $absentRow = $payload->firstWhere('user_id', (string) $deadlineUser->id);
        $this->assertSame('absent', $absentRow['status']);
        $this->assertTrue($absentRow['is_absent']);
        $this->assertFalse($absentRow['can_clock_in']);
        $this->assertSame('2026-06-23T10:00:00+03:00', $absentRow['can_clock_in_until']);

        // The resolver ran exactly once for the user who never clocked in...
        $this->assertSame(1, $resolverCalls[(string) $deadlineUser->id] ?? 0);
        // ...and never for the user who already clocked in today.
        $this->assertArrayNotHasKey((string) $clockedInUser->id, $resolverCalls);

        // Default request hides the deadline-absent employee entirely.
        $defaultResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
            ]));

        $defaultResponse->assertOk();
        $defaultIds = collect($defaultResponse->json('payload'))->pluck('user_id');

        $this->assertFalse($defaultIds->contains((string) $deadlineUser->id));
        $this->assertTrue($defaultIds->contains((string) $clockedInUser->id));
    }

    public function test_statuses_filter_returns_only_requested_statuses(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();

        $availableUser = $this->createProjectUser('Available User');
        $busyUser = $this->createProjectUser('Busy User');
        $offlineUser = $this->createProjectUser('Offline User');
        $outUser = $this->createProjectUser('Out User');

        foreach ([$availableUser, $busyUser, $offlineUser, $outUser] as $user) {
            $this->assignToProject($project, $user);
        }

        $this->createAttendanceWithTracking($availableUser, [
            ['latitude' => 30.0444, 'longitude' => 31.2357, 'timestamp' => '2026-06-23 11:59:00'],
        ]);

        $this->createAttendanceWithTracking($busyUser, [
            ['latitude' => 30.0446, 'longitude' => 31.2359, 'timestamp' => '2026-06-23 11:59:00'],
        ]);
        $this->createBusyTask($busyUser, '2026-06-23');

        $this->createAttendanceWithTracking($outUser, [], [
            'clock_out_time' => '2026-06-23 17:00:00',
            'status' => Attendance::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'statuses' => ['available', 'busy'],
            ]));

        $response->assertOk();
        $payload = collect($response->json('payload'));

        $this->assertCount(2, $payload);

        $busyRow = $payload->firstWhere('user_id', (string) $busyUser->id);
        $this->assertSame('busy', $busyRow['status']);

        $availableRow = $payload->firstWhere('user_id', (string) $availableUser->id);
        $this->assertSame('available', $availableRow['status']);

        $this->assertFalse($payload->pluck('user_id')->contains((string) $offlineUser->id));

        // statuses[] takes precedence over the default exclusion: `out` is requestable.
        $outResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'statuses' => ['out'],
            ]));

        $outResponse->assertOk();
        $outPayload = collect($outResponse->json('payload'));

        $this->assertCount(1, $outPayload);
        $this->assertSame((string) $outUser->id, $outPayload->first()['user_id']);
        $this->assertSame('out', $outPayload->first()['status']);
    }

    public function test_statuses_filter_rejects_unknown_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'statuses' => ['not_a_status'],
            ]));

        $response->assertStatus(422);
    }

    public function test_available_far_when_radius_supplied_and_employee_beyond_it(): void
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

        // With statuses[] the radius classifies instead of dropping.
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'radius' => 500,
                'statuses' => ['available_far'],
            ]));

        $response->assertOk();
        $payload = collect($response->json('payload'));

        $this->assertCount(1, $payload);
        $this->assertSame((string) $farUser->id, $payload->first()['user_id']);
        $this->assertSame('available_far', $payload->first()['status']);

        // With include_unavailable both are returned; the far one keeps its own status.
        $fullResponse = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'radius' => 500,
                'include_unavailable' => true,
            ]));

        $fullResponse->assertOk();
        $fullPayload = collect($fullResponse->json('payload'));

        $this->assertSame('available', $fullPayload->firstWhere('user_id', (string) $nearUser->id)['status']);
        $this->assertSame('available_far', $fullPayload->firstWhere('user_id', (string) $farUser->id)['status']);
    }

    public function test_can_clock_in_fields_reflect_the_work_rules_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();

        $openWindowUser = $this->createProjectUser('Open Window User');
        $deadlineFutureUser = $this->createProjectUser('Deadline Future User');
        $windowClosedUser = $this->createProjectUser('Window Closed User');

        foreach ([$openWindowUser, $deadlineFutureUser, $windowClosedUser] as $user) {
            $this->assignToProject($project, $user);
        }

        $this->createAttendanceWithTracking($openWindowUser, [
            ['latitude' => 30.0444, 'longitude' => 31.2357, 'timestamp' => '2026-06-23 11:59:00'],
        ], ['clock_in_time' => null, 'clock_in_location' => null]);

        // 12:00 UTC = 15:00 Asia/Riyadh, inside [08:00 − 30min early, 17:00 + 1h extension].
        $resolverCalls = [];
        $this->mockConstraintResolver($resolverCalls, [
            (string) $openWindowUser->id => $this->workRulesForPeriod('2026-06-23', '08:00', '17:00', [
                'early_clock_in_rules' => ['early_period' => 30, 'early_unit' => 'minutes'],
                'extension_rules' => ['extension_hours' => 1.0],
            ]),
            (string) $deadlineFutureUser->id => $this->workRulesForPeriod('2026-06-23', '08:00', '17:00', [
                'clock_in_deadline_rules' => ['can_clock_in_before_minutes' => 480], // deadline 16:00 Riyadh
            ]),
            (string) $windowClosedUser->id => $this->workRulesForPeriod('2026-06-23', '02:00', '06:00', []),
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications/employees-with-locations?'.http_build_query([
                'project_id' => $project->id,
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'include_unavailable' => true,
            ]));

        $response->assertOk();
        $payload = collect($response->json('payload'));

        // No deadline configured: window end is the limit.
        $openRow = $payload->firstWhere('user_id', (string) $openWindowUser->id);
        $this->assertTrue($openRow['can_clock_in']);
        $this->assertSame('2026-06-23T18:00:00+03:00', $openRow['can_clock_in_until']);
        $this->assertFalse($openRow['is_absent']);

        // Deadline configured and still ahead: the deadline is the limit.
        $deadlineRow = $payload->firstWhere('user_id', (string) $deadlineFutureUser->id);
        $this->assertTrue($deadlineRow['can_clock_in']);
        $this->assertSame('2026-06-23T16:00:00+03:00', $deadlineRow['can_clock_in_until']);
        $this->assertFalse($deadlineRow['is_absent']);

        // Shift fully in the past (no deadline rule): cannot clock in, not absent.
        $closedRow = $payload->firstWhere('user_id', (string) $windowClosedUser->id);
        $this->assertFalse($closedRow['can_clock_in']);
        $this->assertNull($closedRow['can_clock_in_until']);
        $this->assertFalse($closedRow['is_absent']);

        // Each eligible user was resolved exactly once.
        foreach ([$openWindowUser, $deadlineFutureUser, $windowClosedUser] as $user) {
            $this->assertSame(1, $resolverCalls[(string) $user->id] ?? 0);
        }
    }

    private function createProject(): ProjectManagement
    {
        // projects.project_type_id has a FK to project_types (RESTRICT).
        $projectTypeId = DB::table('project_types')->insertGetId([
            'name' => 'Test Project Type',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // withoutEvents skips the model's UUID-generating `creating` listener, so the
        // id must be force-filled explicitly (it is not in $fillable).
        return ProjectManagement::withoutEvents(function () use ($projectTypeId) {
            $project = new ProjectManagement();
            $project->forceFill([
                'id' => (string) Str::uuid(),
                'name' => 'Employees With Locations Project',
                'company_id' => $this->company->id,
                'status' => 1,
                'serial_number' => 'PRJ-'.Str::upper(Str::random(8)),
                'project_type_id' => $projectTypeId,
                'sub_project_type_id' => $projectTypeId,
                'sub_sub_project_type_id' => $projectTypeId,
            ])->save();

            return $project;
        });
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
     * Swap the attendance constraint resolver for a per-user rules map and count
     * every call, so tests can assert the resolver is only hit for eligible users.
     *
     * @param  array<string, int>  $calls  Filled as user_id => call count.
     * @param  array<string, array>  $rulesByUser
     */
    private function mockConstraintResolver(array &$calls, array $rulesByUser): void
    {
        $this->mock(AttendanceConstraintService::class, function ($mock) use (&$calls, $rulesByUser) {
            $mock->shouldReceive('getTodaysWorkRulesForUser')
                ->andReturnUsing(function ($user) use (&$calls, $rulesByUser) {
                    $calls[(string) $user->id] = ($calls[(string) $user->id] ?? 0) + 1;

                    return $rulesByUser[(string) $user->id] ?? ['all_work_periods' => []];
                });
        });
    }

    /**
     * A getTodaysWorkRulesForUser-shaped array for a single-period day, using the
     * branch timezone the real resolver builds its period carbons in.
     */
    private function workRulesForPeriod(string $date, string $startTime, string $endTime, array $overrides = []): array
    {
        return array_merge([
            'day_status' => 'work_day',
            'all_work_periods' => [[
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'extends_to_next_day' => false,
                'period_start_time_carbon' => Carbon::parse("{$date} {$startTime}:00", 'Asia/Riyadh'),
                'period_end_time_carbon' => Carbon::parse("{$date} {$endTime}:00", 'Asia/Riyadh'),
            ]],
            'early_clock_in_rules' => null,
            'extension_rules' => null,
            'clock_in_deadline_rules' => null,
        ], $overrides);
    }

    private function createBusyTask(User $user, string $date): EmployeeTaskRequest
    {
        return EmployeeTaskRequest::query()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'serial_number' => 'TASK-'.Str::upper(Str::random(8)),
            'title' => 'Busy Task',
            'duration_hours' => 2,
            'task_date' => $date,
            'task_latitude' => 30.0444,
            'task_longitude' => 31.2357,
            'status' => 'in_progress',
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
