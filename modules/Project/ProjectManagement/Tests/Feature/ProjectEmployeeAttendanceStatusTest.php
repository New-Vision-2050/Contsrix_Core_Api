<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\User\Models\User;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

class ProjectEmployeeAttendanceStatusTest extends BaseAttendanceReportTestCase
{
    public function test_project_employees_are_enriched_with_attendance_statuses(): void
    {
        $project = $this->createProject();
        $presentUser = $this->createProjectUser('Present Employee');
        $lateUser = $this->createProjectUser('Late Employee');
        $holidayUser = $this->createProjectUser('Holiday Employee');
        $missingUser = $this->createProjectUser('Missing Employee');
        $outsideProjectUser = $this->createProjectUser('Outside Project Employee');
        $presentConstraint = $this->createConstraint('Present Employee Constraint');
        $missingConstraint = $this->createConstraint('Missing Employee Constraint');

        $this->assignConstraintToUser($presentUser, $presentConstraint);
        $this->assignConstraintToUser($missingUser, $missingConstraint);

        foreach ([$presentUser, $lateUser, $holidayUser, $missingUser] as $user) {
            $this->assignToProject($project, $user);
        }

        $presentAttendance = $this->createAttendance($presentUser, [
            'status' => Attendance::STATUS_ACTIVE,
            'day_status' => 'in_location',
        ]);
        $lateAttendance = $this->createAttendance($lateUser, [
            'status' => Attendance::STATUS_ACTIVE,
            'day_status' => 'in_location',
            'is_late' => true,
        ]);
        $holidayAttendance = $this->createAttendance($holidayUser, [
            'status' => Attendance::STATUS_HOLIDAY,
            'day_status' => 'holiday',
            'is_holiday' => true,
        ]);
        $this->createAttendance($outsideProjectUser, [
            'status' => Attendance::STATUS_ACTIVE,
            'day_status' => 'in_location',
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/employees/project/'.$project->id.'?'.http_build_query([
                'company_id' => $this->company->id,
                'start_date' => '2025-06-23',
                'end_date' => '2025-06-23',
            ]));

        $response->assertOk();

        $payload = collect($response->json('payload'));
        $this->assertCount(4, $payload);
        $this->assertFalse($payload->contains(fn (array $row): bool => $row['user']['id'] === (string) $outsideProjectUser->id));

        $userIds = $payload->pluck('user.id');
        $this->assertSame($userIds->unique()->count(), $userIds->count());

        $this->assertExistingAttendanceFieldsMatch($payload, $presentUser, $presentAttendance);
        $this->assertExistingAttendanceFieldsMatch($payload, $lateUser, $lateAttendance);
        $this->assertExistingAttendanceFieldsMatch($payload, $holidayUser, $holidayAttendance);

        $missingRow = $payload->firstWhere('user.id', (string) $missingUser->id);
        $this->assertNull($missingRow['attendance']['id']);
        $this->assertSame('مطلوب للحضور', $missingRow['attendance']['employee_status']);
        $this->assertSame(Attendance::STATUS_ABSENT, $missingRow['attendance']['status']);
        $this->assertSame(1, $missingRow['attendance']['is_absent']);
        $this->assertSame(0, $missingRow['attendance']['is_late']);
        $this->assertSame(0, $missingRow['attendance']['is_holiday']);
        $this->assertSame('غائب', $missingRow['attendance']['day_status']);
        $this->assertSame((string) $missingConstraint->id, $missingRow['attendance']['attendance_constraint_id']);
        $this->assertSame([
            'id' => (string) $missingConstraint->id,
            'constraint_name' => 'Missing Employee Constraint',
        ], $missingRow['attendance']['attendance_constraint']);
        $this->assertSame('2025-06-23', $missingRow['attendance']['work_date']);
        $this->assertNull($missingRow['attendance']['clock_in_time']);

        $lateRow = $payload->firstWhere('user.id', (string) $lateUser->id);
        $this->assertNull($lateRow['attendance']['attendance_constraint_id']);
        $this->assertNull($lateRow['attendance']['attendance_constraint']);
    }

    public function test_project_employee_response_keeps_existing_shape_when_enriched(): void
    {
        $project = $this->createProject();
        $user = $this->createProjectUser('Shape Employee');
        $projectEmployee = $this->assignToProject($project, $user);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/employees/project/'.$project->id.'?'.http_build_query([
                'company_id' => $this->company->id,
                'start_date' => '2025-06-23',
                'end_date' => '2025-06-23',
            ]));

        $response->assertOk()
            ->assertJsonPath('payload.0.id', (string) $projectEmployee->id)
            ->assertJsonPath('payload.0.project_id', (string) $project->id)
            ->assertJsonPath('payload.0.user.id', (string) $user->id)
            ->assertJsonPath('payload.0.user.name', 'Shape Employee')
            ->assertJsonPath('payload.0.company.id', (string) $this->company->id)
            ->assertJsonPath('payload.0.attendance.id', null)
            ->assertJsonPath('payload.0.attendance.status', Attendance::STATUS_ABSENT)
            ->assertJsonPath('payload.0.attendance.day_status', 'غائب')
            ->assertJsonMissingPath('payload.0.status')
            ->assertJsonMissingPath('payload.0.day_status');
    }

    public function test_project_employee_attendance_constraints_are_eager_loaded(): void
    {
        $project = $this->createProject();
        $constraint = $this->createConstraint('Eager Constraint');
        $firstUser = $this->createProjectUser('First Eager Employee');
        $secondUser = $this->createProjectUser('Second Eager Employee');
        $thirdUser = $this->createProjectUser('Third Eager Employee');

        foreach ([$firstUser, $secondUser, $thirdUser] as $user) {
            $this->assignConstraintToUser($user, $constraint);
            $this->assignToProject($project, $user);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/projects/employees/project/'.$project->id.'?'.http_build_query([
                'company_id' => $this->company->id,
                'start_date' => '2025-06-23',
                'end_date' => '2025-06-23',
            ]))
            ->assertOk();

        $constraintQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'attendance_constraints'))
            ->count();

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(1, $constraintQueries);
    }

    private function createProject(): ProjectManagement
    {
        return ProjectManagement::withoutEvents(fn () => ProjectManagement::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Attendance Status Project',
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

    private function createConstraint(string $name): AttendanceConstraint
    {
        return AttendanceConstraint::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'constraint_type' => AttendanceConstraint::REGULAR,
            'constraint_name' => $name,
            'constraint_config' => [],
            'is_active' => true,
            'priority' => 10,
            'created_by' => $this->actor->id,
        ]);
    }

    private function assignConstraintToUser(User $user, AttendanceConstraint $constraint): UserProfessionalData
    {
        return UserProfessionalData::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => (string) $user->global_company_user_id,
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'management_id' => $this->management->id,
            'department_id' => $this->department->id,
            'attendance_constraint_id' => $constraint->id,
        ]);
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
     * @param  array<string, mixed>  $overrides
     */
    private function createAttendance(User $user, array $overrides = []): Attendance
    {
        return Attendance::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'clock_in_time' => '2025-06-23 08:00:00',
            'clock_out_time' => null,
            'start_time' => '2025-06-23 08:00:00',
            'business_date' => '2025-06-23',
            'total_work_hours' => 0,
            'total_break_hours' => 0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'is_late' => false,
            'is_absent' => false,
            'is_holiday' => false,
            'status' => Attendance::STATUS_ACTIVE,
            'day_status' => 'in_location',
        ], $overrides));
    }

    private function assertExistingAttendanceFieldsMatch(
        \Illuminate\Support\Collection $payload,
        User $user,
        Attendance $attendance
    ): void {
        $attendance->load(AttendanceTeamPresenter::requiredRelations());
        $expected = (new AttendanceTeamPresenter($attendance))->present();
        $row = $payload->firstWhere('user.id', (string) $user->id);

        $this->assertSame($expected['id'], $row['attendance']['id']);

        foreach (['status', 'is_absent', 'is_late', 'is_holiday', 'day_status', 'work_date', 'clock_in_time'] as $field) {
            $this->assertSame($expected[$field], $row['attendance'][$field]);
        }

        $this->assertSame($expected['attendance_constraint_id'], $row['attendance']['attendance_constraint_id']);

        $expectedConstraint = $attendance->user?->userProfessionalData?->attendanceConstraint
            ? [
                'id' => (string) $attendance->user->userProfessionalData->attendanceConstraint->id,
                'constraint_name' => $attendance->user->userProfessionalData->attendanceConstraint->constraint_name,
            ]
            : null;

        $this->assertSame($expectedConstraint, $row['attendance']['attendance_constraint']);
    }
}
