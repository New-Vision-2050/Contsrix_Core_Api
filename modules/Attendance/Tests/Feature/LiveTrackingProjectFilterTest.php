<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

class LiveTrackingProjectFilterTest extends BaseAttendanceReportTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_live_tracking_without_project_id_keeps_current_active_results(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $projectUser = $this->createLiveTrackingUser('Project User');
        $outsideUser = $this->createLiveTrackingUser('Outside User');

        $this->createActiveAttendance($projectUser);
        $this->createActiveAttendance($outsideUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking');

        $response->assertOk();

        $userIds = collect($response->json('payload'))->pluck('user.id');

        $this->assertTrue($userIds->contains((string) $projectUser->id));
        $this->assertTrue($userIds->contains((string) $outsideUser->id));
    }

    public function test_live_tracking_with_project_id_returns_project_users_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();
        $projectUser = $this->createLiveTrackingUser('Project User');
        $outsideUser = $this->createLiveTrackingUser('Outside User');

        $this->assignToProject($project, $projectUser);
        $projectAttendance = $this->createActiveAttendance($projectUser);
        $this->createActiveAttendance($outsideUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking?'.http_build_query([
                'project_id' => $project->id,
            ]));

        $response->assertOk()
            ->assertJsonPath('payload.0.attendance_id', (string) $projectAttendance->id)
            ->assertJsonStructure([
                'payload' => [
                    '*' => [
                        'attendance_id',
                        'user',
                        'clock_in_time',
                        'status',
                        'is_late',
                        'is_absent',
                        'is_holiday',
                        'latest_location',
                        'tracking_points',
                        'tracking_path',
                        'tracking_stats',
                    ],
                ],
            ]);

        $payload = collect($response->json('payload'));

        $this->assertCount(1, $payload);
        $this->assertSame((string) $projectUser->id, $payload->first()['user']['id']);
        $this->assertFalse($payload->contains(fn (array $row): bool => $row['user']['id'] === (string) $outsideUser->id));
    }

    public function test_live_tracking_with_project_id_and_company_id_uses_matching_project_employee_company(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();
        $otherCompany = $this->createCompany('Other Project Membership Company');
        $currentCompanyUser = $this->createLiveTrackingUser('Current Company Project User');
        $otherCompanyMembershipUser = $this->createLiveTrackingUser('Other Company Membership User');

        $this->assignToProject($project, $currentCompanyUser, $this->company->id);
        $this->assignToProject($project, $otherCompanyMembershipUser, $otherCompany->id);
        $this->createActiveAttendance($currentCompanyUser);
        $otherCompanyMembershipAttendance = $this->createActiveAttendance($otherCompanyMembershipUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking?'.http_build_query([
                'project_id' => $project->id,
                'company_id' => $otherCompany->id,
            ]));

        $response->assertOk()
            ->assertJsonPath('payload.0.attendance_id', (string) $otherCompanyMembershipAttendance->id);

        $payload = collect($response->json('payload'));

        $this->assertCount(1, $payload);
        $this->assertSame((string) $otherCompanyMembershipUser->id, $payload->first()['user']['id']);
        $this->assertFalse($payload->contains(fn (array $row): bool => $row['user']['id'] === (string) $currentCompanyUser->id));
    }

    public function test_live_tracking_with_project_id_and_company_id_returns_empty_when_membership_company_does_not_match(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();
        $otherCompany = $this->createCompany('Empty Membership Company');
        $projectUser = $this->createLiveTrackingUser('Project User');

        $this->assignToProject($project, $projectUser, $this->company->id);
        $this->createActiveAttendance($projectUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking?'.http_build_query([
                'project_id' => $project->id,
                'company_id' => $otherCompany->id,
            ]));

        $response->assertOk()
            ->assertJsonPath('payload', []);
    }

    public function test_live_tracking_project_filter_returns_empty_payload_for_empty_project(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00'));

        $project = $this->createProject();
        $outsideUser = $this->createLiveTrackingUser('Outside User');

        $this->createActiveAttendance($outsideUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking?'.http_build_query([
                'project_id' => $project->id,
            ]));

        $response->assertOk()
            ->assertJsonPath('payload', []);
    }

    public function test_live_tracking_project_id_must_be_uuid(): void
    {
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking?project_id=not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_live_tracking_company_id_must_be_uuid(): void
    {
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/live-tracking?company_id=not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_id']);
    }

    private function createCompany(string $name): Company
    {
        $company = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => $name],
            'user_name' => Str::slug($name).'-'.Str::random(6),
            'email' => Str::slug($name).'-'.Str::random(6).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'LIVE-TRACK-'.Str::upper(Str::random(8)),
        ]));

        $company->domains()->firstOrCreate(['domain' => Str::slug($name).'-'.Str::random(6).'.test']);

        return $company;
    }

    private function createProject(): ProjectManagement
    {
        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Live Tracking Project',
            'company_id' => $this->company->id,
            'status' => 1,
        ]));
    }

    private function createLiveTrackingUser(string $name): User
    {
        $globalId = (string) Str::uuid();

        CompanyUser::query()->create([
            'id' => (string) Str::uuid(),
            'global_id' => $globalId,
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::random(6).'@example.test',
            'country_id' => $this->country->id,
        ]);

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
            'management_id' => $this->management->id,
            'department_id' => $this->department->id,
        ]);

        return $user;
    }

    private function assignToProject(ProjectManagement $project, User $user, ?string $companyId = null): ProjectEmployee
    {
        return ProjectEmployee::query()->create([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id' => $user->id,
            'company_id' => $companyId ?? $this->company->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $this->actor->id,
        ]);
    }

    private function createActiveAttendance(User $user): Attendance
    {
        return Attendance::query()->create([
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
            'location_tracking' => [
                [
                    'latitude' => 30.0444,
                    'longitude' => 31.2357,
                    'timestamp' => '2026-06-23 08:15:00',
                    'type' => 'track',
                ],
            ],
        ]);
    }
}
