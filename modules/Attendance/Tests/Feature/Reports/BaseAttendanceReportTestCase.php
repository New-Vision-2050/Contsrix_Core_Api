<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Attendance\Models\LeaveType;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Country\Models\Country;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Models\PublicHolidayDay;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Tests\TestCase;

abstract class BaseAttendanceReportTestCase extends TestCase
{
    use DatabaseTransactions;

    protected User $actor;

    protected User $employee;

    protected Company $company;

    protected string $globalId;

    protected Country $country;

    protected ManagementHierarchy $branch;

    protected ManagementHierarchy $management;

    protected ManagementHierarchy $department;

    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->databaseReady()) {
            $this->markTestSkipped('Database seed prerequisites missing for attendance report feature tests.');
        }

        $this->country = Country::query()->first()
            ?? Country::query()->create([
                'name' => 'Test Country',
                'phonecode' => '20',
                'status' => 1,
            ]);

        $this->company = Company::withoutEvents(fn () => Company::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Attendance Report Company'],
            'user_name' => 'attendance_report_'.Str::random(6),
            'email' => 'attendance-report-'.Str::random(6).'@example.test',
            'phone' => '01000000000',
            'country_id' => $this->country->id,
            'company_type_id' => (string) Str::uuid(),
            'company_field_id' => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id' => (string) Str::uuid(),
            'is_active' => 1,
            'complete_data' => 1,
            'serial_no' => 'ATT-REPORT-'.Str::upper(Str::random(8)),
        ]));
        $this->company->domains()->firstOrCreate(['domain' => 'attendance-report-'.Str::random(6).'.test']);
        tenancy()->initialize($this->company);

        $this->globalId = (string) Str::uuid();

        $this->branch = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->create([
            'name' => 'Main Branch',
            'type' => 'branch',
            'company_id' => $this->company->id,
        ]));

        $this->management = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->create([
            'name' => 'Main Management',
            'type' => 'management',
            'company_id' => $this->company->id,
        ]));

        $this->department = ManagementHierarchy::withoutEvents(fn () => ManagementHierarchy::query()->create([
            'name' => 'Operations',
            'type' => 'department',
            'company_id' => $this->company->id,
            'parent_id' => $this->management->id,
        ]));

        $this->employee = User::factory()->create([
            'company_id' => $this->company->id,
            'global_company_user_id' => $this->globalId,
        ]);

        $this->actor = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        UserProfessionalData::query()->create([
            'company_id' => $this->company->id,
            'global_id' => $this->globalId,
            'user_id' => $this->employee->id,
            'branch_id' => $this->branch->id,
            'management_id' => (string) $this->management->id,
            'department_id' => $this->department->id,
        ]);

        $leaveTypeId = (string) Str::uuid();
        DB::table('leave_types')->insert([
            'id' => $leaveTypeId,
            'company_id' => $this->company->id,
            'name' => json_encode(['en' => 'Annual Leave']),
            'description' => json_encode(['en' => 'Annual Leave']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->leaveType = LeaveType::query()->findOrFail($leaveTypeId);

        EmploymentContract::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $this->globalId,
            'contract_number' => 'C-001',
            'start_date' => now()->startOfYear()->toDateString(),
            'commencement_date' => now()->startOfYear()->toDateString(),
            'contract_duration' => '12',
            'notice_period' => 30,
            'probation_period' => 90,
            'nature_work_id' => null,
            'type_working_hour_id' => null,
            'working_hours' => 8,
            'annual_leave' => 21,
            'country_id' => $this->country->id,
            'right_terminate_id' => null,
        ]);

        $this->seedAttendances();
        $this->seedLeaveRequests();
        $this->seedPublicHolidays();
        $this->grantReportPermissions($this->actor);
    }

    protected function databaseReady(): bool
    {
        try {
            return Schema::hasTable('countries')
                && Schema::hasTable('companies')
                && Schema::hasTable('users');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function grantReportPermissions(User $user): void
    {
        setPermissionsTeamId($this->company->id);

        foreach ([
            Permission::ATTENDANCE_REPORTS_VIEW(),
        ] as $permissionName) {
            SpatiePermission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'api'],
                ['name' => $permissionName, 'guard_name' => 'api', 'company_id' => $this->company->id],
            );
        }

        $user->givePermissionTo([
            Permission::ATTENDANCE_REPORTS_VIEW(),
        ]);
    }

    protected function seedAttendances(): void
    {
        foreach (range(1, 5) as $day) {
            Attendance::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $this->employee->id,
                'company_id' => $this->company->id,
                'clock_in_time' => sprintf('2025-05-%02d 08:00:00', $day),
                'clock_out_time' => sprintf('2025-05-%02d 17:00:00', $day),
                'business_date' => sprintf('2025-05-%02d', $day),
                'total_work_hours' => 8,
                'total_break_hours' => 0,
                'overtime_hours' => $day === 5 ? 2 : 0,
                'late_minutes' => $day <= 2 ? 15 : 0,
                'is_late' => $day <= 2,
                'is_absent' => false,
                'is_holiday' => false,
                'status' => 'approved',
            ]);
        }

        Attendance::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'clock_in_time' => null,
            'clock_out_time' => null,
            'business_date' => '2025-05-10',
            'total_work_hours' => 0,
            'total_break_hours' => 0,
            'overtime_hours' => 0,
            'late_minutes' => 0,
            'is_late' => false,
            'is_absent' => false,
            'is_holiday' => true,
            'status' => 'holiday',
        ]);
    }

    protected function seedLeaveRequests(): void
    {
        LeaveRequest::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2025-05-01',
            'end_date' => '2025-05-02',
            'total_days' => 2,
            'reason' => 'Annual leave',
            'status' => LeaveRequest::STATUS_APPROVED,
            'requested_by' => $this->employee->id,
        ]);
    }

    protected function seedPublicHolidays(): void
    {
        $activeHoliday = PublicHoliday::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Report Holiday',
            'country_id' => $this->country->id,
            'date_start' => '2025-05-08',
            'date_end' => '2025-05-08',
            'year' => 2025,
            'holiday_type' => 'national',
            'is_active' => true,
        ]);

        PublicHolidayDay::query()->create([
            'id' => (string) Str::uuid(),
            'public_holiday_id' => $activeHoliday->id,
            'date' => '2025-05-08',
        ]);

        $weekendHoliday = PublicHoliday::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Weekend Report Holiday',
            'country_id' => $this->country->id,
            'date_start' => '2025-05-10',
            'date_end' => '2025-05-10',
            'year' => 2025,
            'holiday_type' => 'national',
            'is_active' => true,
        ]);

        PublicHolidayDay::query()->create([
            'id' => (string) Str::uuid(),
            'public_holiday_id' => $weekendHoliday->id,
            'date' => '2025-05-10',
        ]);

        $inactiveHoliday = PublicHoliday::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Inactive Report Holiday',
            'country_id' => $this->country->id,
            'date_start' => '2025-05-09',
            'date_end' => '2025-05-09',
            'year' => 2025,
            'holiday_type' => 'national',
            'is_active' => false,
        ]);

        PublicHolidayDay::query()->create([
            'id' => (string) Str::uuid(),
            'public_holiday_id' => $inactiveHoliday->id,
            'date' => '2025-05-09',
        ]);
    }

    protected function reportQuery(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => (string) $this->employee->id,
            'from_date' => '2025-05-01',
            'to_date' => '2025-05-31',
        ], $overrides);
    }
}
