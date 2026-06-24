<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Attendance\Models\Attendance;
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
        // Default attendance values when no attendance record exists for today
        $this->assertSame(0, $oneProjectRow['is_absent']);
        $this->assertSame(0, $oneProjectRow['is_holiday']);
        $this->assertSame(0, $oneProjectRow['is_late']);
        $this->assertNotEmpty($oneProjectRow['day_status']);

        $multiProjectRow = $payload->firstWhere('id', (string) $multiProjectUser->id);
        $this->assertEqualsCanonicalizing([
            ['id' => (string) $projectB->id, 'name' => 'Project B'],
            ['id' => (string) $projectC->id, 'name' => 'Project C'],
        ], $multiProjectRow['projects']);
        $this->assertSame(0, $multiProjectRow['is_absent']);
        $this->assertSame(0, $multiProjectRow['is_holiday']);
        $this->assertSame(0, $multiProjectRow['is_late']);
        $this->assertNotEmpty($multiProjectRow['day_status']);

        $noProjectRow = $payload->firstWhere('id', (string) $noProjectUser->id);
        $this->assertSame([], $noProjectRow['projects']);
        $this->assertSame(0, $noProjectRow['is_absent']);
        $this->assertSame(0, $noProjectRow['is_holiday']);
        $this->assertSame(0, $noProjectRow['is_late']);
        $this->assertNotEmpty($noProjectRow['day_status']);
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

    public function test_constraint_employees_reflect_attendance_status_for_requested_date(): void
    {
        $constraint = $this->createConstraint();
        $lateUser = $this->createConstraintUser('Late Employee', $constraint);
        $holidayUser = $this->createConstraintUser('Holiday Employee', $constraint);
        $absentUser = $this->createConstraintUser('Absent Employee', $constraint);

        $date = '2025-05-15';

        Attendance::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $lateUser->id,
            'company_id' => $this->company->id,
            'clock_in_time' => $date . ' 08:15:00',
            'clock_out_time' => $date . ' 17:00:00',
            'business_date' => $date,
            'total_work_hours' => 8,
            'is_late' => true,
            'is_absent' => false,
            'is_holiday' => false,
            'day_status' => 'work_day',
            'status' => 'approved',
        ]);

        Attendance::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $holidayUser->id,
            'company_id' => $this->company->id,
            'clock_in_time' => null,
            'clock_out_time' => null,
            'business_date' => $date,
            'total_work_hours' => 0,
            'is_late' => false,
            'is_absent' => false,
            'is_holiday' => true,
            'day_status' => 'holiday',
            'status' => 'holiday',
        ]);

        Attendance::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $absentUser->id,
            'company_id' => $this->company->id,
            'clock_in_time' => null,
            'clock_out_time' => null,
            'business_date' => $date,
            'total_work_hours' => 0,
            'is_late' => false,
            'is_absent' => true,
            'is_holiday' => false,
            'day_status' => 'absent',
            'status' => 'absent',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/constraints/'.$constraint->id.'/employees?'.http_build_query([
                'page' => 1,
                'per_page' => 10,
                'start_date' => $date,
                'end_date' => $date,
            ]));

        $response->assertOk();

        $payload = collect($response->json('payload'));

        $lateRow = $payload->firstWhere('id', (string) $lateUser->id);
        $this->assertSame(0, $lateRow['is_absent']);
        $this->assertSame(0, $lateRow['is_holiday']);
        $this->assertSame(1, $lateRow['is_late']);
        $this->assertSame(__('validation.day_status.work_day'), $lateRow['day_status']);

        $holidayRow = $payload->firstWhere('id', (string) $holidayUser->id);
        $this->assertSame(0, $holidayRow['is_absent']);
        $this->assertSame(1, $holidayRow['is_holiday']);
        $this->assertSame(0, $holidayRow['is_late']);
        $this->assertSame(__('validation.day_status.holiday'), $holidayRow['day_status']);

        $absentRow = $payload->firstWhere('id', (string) $absentUser->id);
        $this->assertSame(1, $absentRow['is_absent']);
        $this->assertSame(0, $absentRow['is_holiday']);
        $this->assertSame(0, $absentRow['is_late']);
        $this->assertSame(__('validation.day_status.absent'), $absentRow['day_status']);
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
