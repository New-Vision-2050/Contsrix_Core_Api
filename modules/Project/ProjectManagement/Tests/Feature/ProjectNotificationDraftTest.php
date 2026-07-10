<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Str;
use Modules\EmployeeTask\Database\Seeders\EmployeeTaskTypeSeeder;
use Modules\EmployeeTask\Models\EmployeeTaskType;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;

class ProjectNotificationDraftTest extends BaseAttendanceReportTestCase
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

        $this->seedEmployeeTaskType();
    }

    private function seedEmployeeTaskType(): void
    {
        EmployeeTaskType::updateOrCreate(
            ['key' => 'project_notification'],
            ['name' => 'إشعار مشروع']
        );
    }

    public function test_draft_create_persists_minimal_data_and_returns_draft_status(): void
    {
        $project = $this->createProject();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/notifications', [
                'is_draft' => true,
                'project_id' => $project->id,
            ]);

        $response->assertOk();

        $data = $response->json('payload');
        $this->assertNotEmpty($data['id']);
        $this->assertSame('draft', $data['status']);

        $this->assertDatabaseHas('project_notifications', [
            'id' => $data['id'],
            'status' => 'draft',
            'project_id' => $project->id,
            'created_by_user_id' => $this->actor->id,
        ], tenant()->getDatabaseName());
    }

    public function test_draft_create_accepts_empty_assigned_user_ids(): void
    {
        $project = $this->createProject();

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->postJson('/api/v1/projects/notifications', [
                'is_draft' => true,
                'project_id' => $project->id,
                'assigned_user_ids' => [],
            ]);

        $response->assertOk();

        $data = $response->json('payload');
        $this->assertSame('draft', $data['status']);
    }

    public function test_draft_update_overwrites_fields_and_keeps_status_draft(): void
    {
        $project = $this->createProject();
        $draft = $this->createDraftNotification($project);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/notifications/{$draft->id}", [
                'is_draft' => true,
                'work_description' => 'Updated draft description',
                'repair_point' => null,
            ]);

        $response->assertOk();

        $data = $response->json('payload');
        $this->assertSame('draft', $data['status']);
        $this->assertSame('Updated draft description', $data['work_description']);
        $this->assertNull($data['repair_point']);
    }

    public function test_publishing_draft_changes_status_to_pending(): void
    {
        $project = $this->createProject();
        $employee = $this->createProjectUser('Assigned Employee');
        $this->assignToProject($project, $employee);
        $draft = $this->createDraftNotification($project, $employee);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/notifications/{$draft->id}", [
                'is_draft' => false,
                'assigned_user_ids' => [$employee->id],
                'task_date' => now()->addDay()->format('Y-m-d'),
                'duration_hours' => 2,
                'task_latitude' => 30.0444,
                'task_longitude' => 31.2357,
            ]);

        $response->assertOk();

        $data = $response->json('payload');
        $this->assertSame('pending', $data['status']);
        $this->assertNotNull($data['employee_task']);
    }

    public function test_default_list_includes_drafts(): void
    {
        $project = $this->createProject();
        $draft = $this->createDraftNotification($project);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications');

        $response->assertOk();

        $ids = collect($response->json('payload'))->pluck('id');
        $this->assertTrue($ids->contains($draft->id));
    }

    public function test_status_draft_filter_returns_all_drafts(): void
    {
        $project = $this->createProject();
        $draft = $this->createDraftNotification($project);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $otherDraft = ProjectNotification::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'notification_number' => 'NTF-OTHER-001',
            'status' => 'draft',
            'created_by_user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/notifications?status=draft');

        $response->assertOk();

        $ids = collect($response->json('payload'))->pluck('id');
        $this->assertTrue($ids->contains($draft->id));
        $this->assertTrue($ids->contains($otherDraft->id));
    }

    public function test_creator_can_view_draft(): void
    {
        $project = $this->createProject();
        $draft = $this->createDraftNotification($project);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/notifications/{$draft->id}");

        $response->assertOk();
        $this->assertSame('draft', $response->json('payload.status'));
    }

    public function test_non_creator_cannot_view_draft(): void
    {
        $project = $this->createProject();
        $draft = $this->createDraftNotification($project);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($otherUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson("/api/v1/projects/notifications/{$draft->id}");

        $response->assertNotFound();
    }

    public function test_non_creator_cannot_update_draft(): void
    {
        $project = $this->createProject();
        $draft = $this->createDraftNotification($project);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($otherUser, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson("/api/v1/projects/notifications/{$draft->id}", [
                'is_draft' => true,
                'work_description' => 'Hijacked',
            ]);

        $response->assertNotFound();
    }

    private function createProject(): ProjectManagement
    {
        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Draft Test Project',
            'company_id' => $this->company->id,
            'status' => 1,
        ]));
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

    private function createDraftNotification(ProjectManagement $project, ?User $assignedUser = null): ProjectNotification
    {
        $data = [
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'notification_number' => 'NTF-DRAFT-' . Str::upper(Str::random(6)),
            'status' => 'draft',
            'created_by_user_id' => $this->actor->id,
            'work_description' => 'Initial draft description',
            'repair_point' => 'Initial repair point',
        ];

        if ($assignedUser !== null) {
            $data['assigned_user_ids'] = [$assignedUser->id];
        }

        return ProjectNotification::query()->create($data);
    }
}
