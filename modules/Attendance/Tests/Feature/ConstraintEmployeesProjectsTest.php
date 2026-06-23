<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

class ConstraintEmployeesProjectsTest extends BaseAttendanceReportTestCase
{
    public function test_constraint_employees_include_assigned_projects_without_changing_existing_fields(): void
    {
        $constraint = $this->createConstraint();
        $oneProjectUser = $this->createConstraintUser('One Project Employee', $constraint);
        $multiProjectUser = $this->createConstraintUser('Multi Project Employee', $constraint);
        $noProjectUser = $this->createConstraintUser('No Project Employee', $constraint);

        $projectA = $this->createProject('Project A');
        $projectB = $this->createProject('Project B');
        $projectC = $this->createProject('Project C');

        $this->assignToProject($projectA, $oneProjectUser);
        $this->assignToProject($projectB, $multiProjectUser);
        $this->assignToProject($projectC, $multiProjectUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/constraints/'.$constraint->id.'/employees?'.http_build_query([
                'page' => 1,
                'per_page' => 10,
            ]));

        $response->assertOk()
            ->assertJsonPath('pagination.result_count', 3)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.page_size', 10);

        $payload = collect($response->json('payload'));

        $oneProjectRow = $payload->firstWhere('id', (string) $oneProjectUser->id);
        $this->assertSame('One Project Employee', $oneProjectRow['name']);
        $this->assertSame($oneProjectUser->email, $oneProjectRow['email']);
        $this->assertSame($oneProjectUser->phone, $oneProjectRow['phone']);
        $this->assertSame('main', $oneProjectRow['source']);
        $this->assertSame([
            ['id' => (string) $projectA->id, 'name' => 'Project A'],
        ], $oneProjectRow['projects']);

        $multiProjectRow = $payload->firstWhere('id', (string) $multiProjectUser->id);
        $this->assertEqualsCanonicalizing([
            ['id' => (string) $projectB->id, 'name' => 'Project B'],
            ['id' => (string) $projectC->id, 'name' => 'Project C'],
        ], $multiProjectRow['projects']);

        $noProjectRow = $payload->firstWhere('id', (string) $noProjectUser->id);
        $this->assertSame([], $noProjectRow['projects']);
    }

    public function test_constraint_employees_projects_preserve_pagination(): void
    {
        $constraint = $this->createConstraint();
        $firstUser = $this->createConstraintUser('First Employee', $constraint);
        $secondUser = $this->createConstraintUser('Second Employee', $constraint);

        $project = $this->createProject('Paged Project');
        $this->assignToProject($project, $firstUser);
        $this->assignToProject($project, $secondUser);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/constraints/'.$constraint->id.'/employees?'.http_build_query([
                'page' => 1,
                'per_page' => 1,
            ]));

        $response->assertOk()
            ->assertJsonPath('pagination.result_count', 2)
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.page_size', 1)
            ->assertJsonPath('pagination.last_page', 2);

        $this->assertCount(1, $response->json('payload'));
        $this->assertArrayHasKey('projects', $response->json('payload.0'));
    }

    public function test_constraint_employees_projects_are_eager_loaded(): void
    {
        $constraint = $this->createConstraint();
        $project = $this->createProject('Eager Project');
        $oneUser = $this->createConstraintUser('One Employee', $constraint);
        $this->assignToProject($project, $oneUser);

        $singleEmployeeQueries = $this->countQueriesForConstraintEmployees($constraint);

        $secondUser = $this->createConstraintUser('Second Employee', $constraint);
        $thirdUser = $this->createConstraintUser('Third Employee', $constraint);
        $this->assignToProject($project, $secondUser);
        $this->assignToProject($project, $thirdUser);

        $multiEmployeeQueries = $this->countQueriesForConstraintEmployees($constraint);

        $this->assertSame($singleEmployeeQueries, $multiEmployeeQueries);
    }

    private function createConstraint(): AttendanceConstraint
    {
        return AttendanceConstraint::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'constraint_type' => AttendanceConstraint::REGULAR,
            'constraint_name' => 'Constraint Employees Projects',
            'constraint_config' => [],
            'is_active' => true,
            'priority' => 10,
            'created_by' => $this->actor->id,
        ]);
    }

    private function createConstraintUser(string $name, AttendanceConstraint $constraint): User
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
            'management_id' => $this->management->id,
            'department_id' => $this->department->id,
            'attendance_constraint_id' => $constraint->id,
        ]);

        return $user;
    }

    private function createProject(string $name): ProjectManagement
    {
        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'company_id' => $this->company->id,
            'status' => 1,
        ]));
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

    private function countQueriesForConstraintEmployees(AttendanceConstraint $constraint): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/constraints/'.$constraint->id.'/employees?'.http_build_query([
                'page' => 1,
                'per_page' => 10,
            ]))
            ->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return count($queries);
    }
}
