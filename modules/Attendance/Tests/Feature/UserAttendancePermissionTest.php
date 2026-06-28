<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature;

use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\RoleAndPermission\Enums\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;

class UserAttendancePermissionTest extends BaseAttendanceReportTestCase
{
    public function test_user_constraint_today_requires_self_view_permission(): void
    {
        $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/user-constraint/today')
            ->assertNotFound();
    }

    public function test_attendance_calendar_requires_self_view_permission(): void
    {
        $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/user-attendance/calendar?month=6&year=2026')
            ->assertNotFound();
    }

    public function test_user_with_self_view_permission_can_get_constraint_today(): void
    {
        $this->grantSelfViewPermission();

        $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/user-constraint/today')
            ->assertOk();
    }

    public function test_user_with_self_view_permission_can_get_attendance_calendar(): void
    {
        $this->grantSelfViewPermission();

        $this->actingAs($this->employee, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/attendance/user-attendance/calendar?month=6&year=2026')
            ->assertOk();
    }

    private function grantSelfViewPermission(): void
    {
        setPermissionsTeamId($this->company->id);

        SpatiePermission::firstOrCreate(
            ['name' => Permission::EMPLOYEE_ATTENDANCE_SELF_VIEW(), 'guard_name' => 'api'],
            [
                'name' => Permission::EMPLOYEE_ATTENDANCE_SELF_VIEW(),
                'guard_name' => 'api',
                'company_id' => $this->company->id,
            ],
        );

        $this->employee->givePermissionTo(Permission::EMPLOYEE_ATTENDANCE_SELF_VIEW());
    }
}
