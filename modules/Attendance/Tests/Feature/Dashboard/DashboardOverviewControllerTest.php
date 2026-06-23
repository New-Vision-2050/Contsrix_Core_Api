<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

class DashboardOverviewControllerTest extends BaseAttendanceReportTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_overview_returns_personal_mobile_dashboard_cards(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 12:00:00', 'Asia/Riyadh'));
        $this->assignWeeklyConstraintToEmployee();

        EmploymentContract::query()
            ->where('company_id', $this->company->id)
            ->where('global_id', $this->globalId)
            ->update(['working_hours' => 1]);

        $this->seedDashboardTasks();
        $this->seedDashboardAttendances();

        $response = $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/dashboard/overview');

        $response->assertOk()
            ->assertJsonPath('payload.timezone', 'Asia/Riyadh')
            ->assertJsonPath('payload.week.starts_on', 'saturday')
            ->assertJsonPath('payload.week.from_date', '2026-06-20')
            ->assertJsonPath('payload.week.to_date', '2026-06-26')
            ->assertJsonPath('payload.tasks.count', 3)
            ->assertJsonPath('payload.tasks.previous_count', 2)
            ->assertJsonPath('payload.tasks.percentage_change', 50.0)
            ->assertJsonPath('payload.tasks.trend', 'up')
            ->assertJsonPath('payload.attendance.worked_minutes', 600)
            ->assertJsonPath('payload.attendance.worked.hours', 10)
            ->assertJsonPath('payload.attendance.worked.minutes', 0)
            ->assertJsonPath('payload.attendance.worked.label', '10h 00m')
            ->assertJsonPath('payload.attendance.required_minutes', 1560)
            ->assertJsonPath('payload.attendance.remaining_minutes', 960)
            ->assertJsonPath('payload.attendance.previous_worked_minutes', 300)
            ->assertJsonPath('payload.attendance.percentage_change', 100.0)
            ->assertJsonPath('payload.attendance.trend', 'up')
            ->assertJsonPath('payload.attendance.donut.0.key', 'worked')
            ->assertJsonPath('payload.attendance.donut.0.value', 600)
            ->assertJsonPath('payload.attendance.donut.1.key', 'remaining')
            ->assertJsonPath('payload.attendance.donut.1.value', 960);
    }

    public function test_overview_requires_authentication(): void
    {
        $this->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/dashboard/overview')
            ->assertUnauthorized();
    }

    private function assignWeeklyConstraintToEmployee(): AttendanceConstraint
    {
        $constraint = AttendanceConstraint::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'constraint_type' => AttendanceConstraint::REGULAR,
            'constraint_name' => 'Dashboard Weekly Constraint',
            'constraint_config' => [
                'time_rules' => [
                    'subtype' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
                    'weekly_schedule' => [
                        'saturday' => [
                            'enabled' => true,
                            'periods' => [
                                ['start_time' => '10:00', 'end_time' => '14:00'],
                            ],
                        ],
                        'sunday' => [
                            'enabled' => true,
                            'periods' => [
                                ['start_time' => '08:30', 'end_time' => '17:30'],
                            ],
                        ],
                        'monday' => [
                            'enabled' => false,
                            'periods' => [],
                        ],
                        'tuesday' => [
                            'enabled' => true,
                            'periods' => [
                                ['start_time' => '09:00', 'end_time' => '11:00'],
                                ['start_time' => '13:00', 'end_time' => '16:00'],
                            ],
                        ],
                        'wednesday' => [
                            'enabled' => false,
                            'periods' => [],
                        ],
                        'thursday' => [
                            'enabled' => true,
                            'periods' => [
                                ['start_time' => '09:00', 'end_time' => '17:00'],
                            ],
                        ],
                        'friday' => [
                            'enabled' => false,
                            'periods' => [],
                        ],
                    ],
                ],
            ],
            'is_active' => true,
            'priority' => 10,
            'created_by' => $this->employee->id,
        ]);

        UserProfessionalData::query()
            ->where('company_id', $this->company->id)
            ->where('user_id', $this->employee->id)
            ->update(['attendance_constraint_id' => $constraint->id]);

        app(AttendanceConstraintService::class)
            ->bumpApplicableConstraintsCacheForCompany((string) $this->company->id);

        return $constraint;
    }

    private function seedDashboardTasks(): void
    {
        foreach ([
            ['task_date' => '2026-06-01', 'status' => 'pending'],
            ['task_date' => '2026-06-15', 'status' => 'completed'],
            ['task_date' => '2026-06-26', 'status' => 'cancelled'],
            ['task_date' => '2026-05-05', 'status' => 'rejected'],
            ['task_date' => '2026-05-20', 'status' => 'approved'],
        ] as $attributes) {
            $this->createTaskFor($this->employee, $this->company, $attributes);
        }

        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->createTaskFor($otherUser, $this->company, [
            'task_date' => '2026-06-10',
            'status' => 'completed',
        ]);

        $otherCompany = Company::factory()->create(['status' => 'active']);
        $otherCompanyUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $this->createTaskFor($otherCompanyUser, $otherCompany, [
            'task_date' => '2026-06-10',
            'status' => 'completed',
        ]);
    }

    private function seedDashboardAttendances(): void
    {
        foreach ([
            ['business_date' => '2026-06-21', 'total_work_hours' => 4.5],
            ['business_date' => '2026-06-23', 'total_work_hours' => 5.5],
            ['business_date' => '2026-06-18', 'total_work_hours' => 5.0],
        ] as $attributes) {
            $this->createAttendanceFor($this->employee, $this->company, $attributes);
        }

        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        $this->createAttendanceFor($otherUser, $this->company, [
            'business_date' => '2026-06-23',
            'total_work_hours' => 8.0,
        ]);

        $otherCompany = Company::factory()->create(['status' => 'active']);
        $otherCompanyUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $this->createAttendanceFor($otherCompanyUser, $otherCompany, [
            'business_date' => '2026-06-23',
            'total_work_hours' => 8.0,
        ]);
    }

    private function createTaskFor(User $user, Company $company, array $attributes): void
    {
        EmployeeTaskRequest::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'user_id' => $user->id,
            'serial_number' => 'DASH-TASK-'.Str::upper(Str::random(10)),
            'title' => 'Dashboard task',
            'duration_hours' => 2,
            'task_latitude' => 24.7136,
            'task_longitude' => 46.6753,
        ], $attributes));
    }

    private function createAttendanceFor(User $user, Company $company, array $attributes): void
    {
        $businessDate = $attributes['business_date'];

        Attendance::query()->create(array_merge([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'company_id' => $company->id,
            'clock_in_time' => "{$businessDate} 09:00:00",
            'clock_out_time' => "{$businessDate} 17:00:00",
            'total_break_hours' => 0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'is_late' => false,
            'is_absent' => false,
            'is_holiday' => false,
            'status' => 'approved',
        ], $attributes));
    }
}
