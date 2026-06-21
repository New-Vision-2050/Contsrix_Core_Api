<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Illuminate\Support\Str;
use Modules\User\Models\User;

class AttendanceReportControllerTest extends BaseAttendanceReportTestCase
{
    public function test_index_returns_dashboard_and_monthly_payload(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/hr/attendance/reports?'.http_build_query($this->reportQuery()));

        $response->assertOk()
            ->assertJsonPath('payload.employee.id', (string) $this->employee->id)
            ->assertJsonPath('payload.employee.name', (string) $this->employee->name)
            ->assertJsonPath('payload.contract.attendance_days', 21)
            ->assertJsonPath('payload.achieved.attendance_days', 5)
            ->assertJsonPath('payload.remaining.remaining_leaves', 19)
            ->assertJsonMissingPath('payload.contract.holiday_allowance')
            ->assertJsonMissingPath('payload.remaining.remaining_holidays')
            ->assertJsonMissingPath('payload.data')
            ->assertJsonMissingPath('payload.pagination')
            ->assertJsonMissingPath('payload.monthly_reports.data.0.deductions')
            ->assertJsonMissingPath('payload.monthly_reports.data.0.additions')
            ->assertJsonStructure([
                'payload' => [
                    'employee' => ['id', 'name'],
                    'contract' => ['attendance_days', 'required_hours', 'leave_allowance'],
                    'achieved' => ['attendance_days', 'worked_hours', 'used_leaves', 'used_holidays'],
                    'remaining' => ['attendance_days', 'worked_hours', 'remaining_leaves'],
                    'monthly_reports' => [
                        'data',
                        'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
                    ],
                ],
            ]);
    }

    public function test_index_requires_permission(): void
    {
        $unauthorized = User::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($unauthorized, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/hr/attendance/reports?'.http_build_query($this->reportQuery()))
            ->assertNotFound();
    }

    public function test_index_requires_employee_id_validation_error(): void
    {
        $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/hr/attendance/reports?'.http_build_query([
                'from_date' => '2025-05-01',
                'to_date' => '2025-05-31',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_id']);
    }

    public function test_month_filter_on_index(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/hr/attendance/reports?'.http_build_query(array_merge(
                $this->reportQuery(),
                ['month' => 5, 'year' => 2025],
            )));

        $response->assertOk();
        $this->assertSame('May 2025', $response->json('payload.monthly_reports.data.0.month'));
    }

    public function test_index_rejects_employee_from_another_company(): void
    {
        $otherEmployee = User::factory()->create([
            'company_id' => (string) Str::uuid(),
        ]);

        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/hr/attendance/reports?'.http_build_query($this->reportQuery([
                'employee_id' => (string) $otherEmployee->id,
            ])));

        $response->assertForbidden();
    }

    public function test_monthly_reports_are_paginated_in_response(): void
    {
        $response = $this->actingAs($this->actor, 'api')
            ->withHeader('X-Tenant', $this->company->id)
            ->getJson('/api/v1/hr/attendance/reports?'.http_build_query($this->reportQuery([
                'from_date' => '2025-01-01',
                'to_date' => '2025-12-31',
                'page' => 2,
                'per_page' => 5,
            ])));

        $response->assertOk()
            ->assertJsonPath('payload.monthly_reports.pagination.current_page', 2)
            ->assertJsonPath('payload.monthly_reports.pagination.per_page', 5)
            ->assertJsonPath('payload.monthly_reports.pagination.total', 12)
            ->assertJsonPath('payload.monthly_reports.pagination.last_page', 3);

        $this->assertSame('July 2025', $response->json('payload.monthly_reports.data.0.month'));
    }
}
