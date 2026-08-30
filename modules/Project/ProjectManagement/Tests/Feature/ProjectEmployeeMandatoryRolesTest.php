<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Str;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Commands\UpdateProjectManagementCommand;
use Modules\Project\ProjectManagement\Handlers\UpdateProjectManagementHandler;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRole;
use Modules\Project\ProjectType\Models\ProjectType;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class ProjectEmployeeMandatoryRolesTest extends BaseAttendanceReportTestCase
{
    public function test_project_manager_employee_cannot_be_removed(): void
    {
        $manager = $this->createProjectUser('Project Manager');
        $project = $this->createProject(manager: $manager);
        $managerEmployee = $this->assignToProject($project, $manager);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson('/api/v1/projects/employees/'.$managerEmployee->id)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('project_employees', [
            'id' => (string) $managerEmployee->id,
            'project_id' => (string) $project->id,
            'user_id' => (string) $manager->id,
        ]);
    }

    public function test_project_creator_employee_cannot_be_removed(): void
    {
        $creator = $this->createProjectUser('Project Creator');
        $project = $this->createProject(creator: $creator);
        $creatorEmployee = $this->assignToProject($project, $creator);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson('/api/v1/projects/employees/'.$creatorEmployee->id)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('project_employees', [
            'id' => (string) $creatorEmployee->id,
            'project_id' => (string) $project->id,
            'user_id' => (string) $creator->id,
        ]);
    }

    public function test_regular_project_employee_can_be_removed(): void
    {
        $project = $this->createProject();
        $employee = $this->createProjectUser('Regular Employee');
        $projectEmployee = $this->assignToProject($project, $employee);

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->deleteJson('/api/v1/projects/employees/'.$projectEmployee->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('project_employees', [
            'id' => (string) $projectEmployee->id,
        ]);
    }

    public function test_project_employee_listing_marks_mandatory_roles(): void
    {
        $manager = $this->createProjectUser('Mandatory Manager');
        $creator = $this->createProjectUser('Mandatory Creator');
        $regular = $this->createProjectUser('Optional Employee');
        $project = $this->createProject(manager: $manager, creator: $creator);

        $this->assignToProject($project, $manager);
        $this->assignToProject($project, $creator);
        $this->assignToProject($project, $regular);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/employees/project/'.$project->id.'?'.http_build_query([
                'company_id' => $this->company->id,
            ]))
            ->assertOk();

        $payload = collect($response->json('payload'));

        $this->assertSame('project_manager', $payload->firstWhere('user.id', (string) $manager->id)['mandatory_reason']);
        $this->assertTrue($payload->firstWhere('user.id', (string) $manager->id)['is_mandatory']);
        $this->assertSame('project_creator', $payload->firstWhere('user.id', (string) $creator->id)['mandatory_reason']);
        $this->assertTrue($payload->firstWhere('user.id', (string) $creator->id)['is_mandatory']);
        $this->assertNull($payload->firstWhere('user.id', (string) $regular->id)['mandatory_reason']);
        $this->assertFalse($payload->firstWhere('user.id', (string) $regular->id)['is_mandatory']);
    }

    public function test_updating_project_manager_adds_new_manager_as_project_employee(): void
    {
        [$projectType, $subProjectType, $subSubProjectType] = $this->createProjectTypes();
        $oldManager = $this->createProjectUser('Old Manager');
        $newManager = $this->createProjectUser('New Manager');
        $project = $this->createProject(
            manager: $oldManager,
            projectTypeId: $projectType->id,
            subProjectTypeId: $subProjectType->id,
            subSubProjectTypeId: $subSubProjectType->id,
        );
        $adminRole = $this->createProjectRole($project, isDefault: true);

        app(UpdateProjectManagementHandler::class)->handle(new UpdateProjectManagementCommand(
            id: Uuid::fromString($project->id),
            projectTypeId: $projectType->id,
            subProjectTypeId: $subProjectType->id,
            subSubProjectTypeId: $subSubProjectType->id,
            name: 'Updated Manager Project',
            managerId: (string) $newManager->id,
            branchId: (string) $this->branch->id,
            status: 1,
        ));

        $this->assertDatabaseHas('project_employees', [
            'project_id' => (string) $project->id,
            'user_id' => (string) $newManager->id,
            'company_id' => (string) $this->company->id,
            'project_role_id' => (string) $adminRole->id,
        ]);
    }

    public function test_assign_role_endpoint_updates_employee_role(): void
    {
        $project = $this->createProject();
        $employee = $this->createProjectUser('Role Employee');
        $projectEmployee = $this->assignToProject($project, $employee);
        $role = $this->createProjectRole($project, name: 'Site Engineer', slug: 'site-engineer');

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->putJson('/api/v1/projects/employees/'.$projectEmployee->id.'/assign-role', [
                'project_role_id' => (string) $role->id,
            ])
            ->assertOk()
            ->assertJsonPath('payload.project_role.id', (string) $role->id);

        $this->assertDatabaseHas('project_employees', [
            'id' => (string) $projectEmployee->id,
            'project_role_id' => (string) $role->id,
        ]);
    }

    private function createProject(
        ?User $manager = null,
        ?User $creator = null,
        ?int $projectTypeId = null,
        ?int $subProjectTypeId = null,
        ?int $subSubProjectTypeId = null,
    ): ProjectManagement {
        if ($projectTypeId === null || $subProjectTypeId === null || $subSubProjectTypeId === null) {
            [$projectType, $subProjectType, $subSubProjectType] = $this->createProjectTypes();
            $projectTypeId = $projectType->id;
            $subProjectTypeId = $subProjectType->id;
            $subSubProjectTypeId = $subSubProjectType->id;
        }

        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->forceCreate([
            'id' => (string) Str::uuid(),
            'serial_number' => 'TEST-'.Str::uuid(),
            'project_type_id' => $projectTypeId,
            'sub_project_type_id' => $subProjectTypeId,
            'sub_sub_project_type_id' => $subSubProjectTypeId,
            'name' => 'Mandatory Roles Project',
            'manager_id' => $manager?->id,
            'created_by_user_id' => $creator?->id,
            'branch_id' => $this->branch->id,
            'company_id' => $this->company->id,
            'status' => 1,
        ]));
    }

    private function createProjectUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'company_id' => $this->company->id,
            'global_company_user_id' => (string) Str::uuid(),
        ]);
    }

    private function assignToProject(ProjectManagement $project, User $user, ?ProjectRole $role = null): ProjectEmployee
    {
        return ProjectEmployee::query()->create([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'assigned_at' => now(),
            'assigned_by_user_id' => $this->actor->id,
            'project_role_id' => $role?->id,
        ]);
    }

    private function createProjectRole(
        ProjectManagement $project,
        string $name = 'Project Admin',
        string $slug = 'project-admin',
        bool $isDefault = false,
    ): ProjectRole {
        return ProjectRole::query()->create([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: ProjectType, 1: ProjectType, 2: ProjectType}
     */
    private function createProjectTypes(): array
    {
        $projectType = ProjectType::query()->create([
            'name' => 'Main Type',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $subProjectType = ProjectType::query()->create([
            'name' => 'Sub Type',
            'parent_id' => $projectType->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $subSubProjectType = ProjectType::query()->create([
            'name' => 'Sub Sub Type',
            'parent_id' => $subProjectType->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        return [$projectType, $subProjectType, $subSubProjectType];
    }

}
