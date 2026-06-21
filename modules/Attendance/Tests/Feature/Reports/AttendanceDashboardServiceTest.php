<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Illuminate\Support\Str;
use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Services\AttendanceDashboardService;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

class AttendanceDashboardServiceTest extends BaseAttendanceReportTestCase
{
    public function test_builds_contract_summary_from_employment_contract(): void
    {
        EmploymentContract::query()
            ->where('company_id', $this->company->id)
            ->where('global_id', $this->globalId)
            ->update(['annual_leave' => 0]);

        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $summary = app(AttendanceDashboardService::class)->buildSummary($filters);

        $this->assertSame(21, $summary['contract']['attendance_days']);
        $this->assertSame(168.0, $summary['contract']['required_hours']);
        $this->assertSame(21, $summary['contract']['leave_allowance']);
        $this->assertArrayNotHasKey('holiday_allowance', $summary['contract']);
    }

    public function test_leave_allowance_is_thirty_for_more_than_five_service_years(): void
    {
        EmploymentContract::query()
            ->where('company_id', $this->company->id)
            ->where('global_id', $this->globalId)
            ->update([
                'commencement_date' => '2018-01-01',
                'annual_leave' => 0,
            ]);

        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $summary = app(AttendanceDashboardService::class)->buildSummary($filters);

        $this->assertSame(30, $summary['contract']['leave_allowance']);
        $this->assertSame(28, $summary['remaining']['remaining_leaves']);
    }

    public function test_leave_allowance_uses_earliest_contract_date_with_start_date_fallback(): void
    {
        EmploymentContract::query()
            ->where('company_id', $this->company->id)
            ->where('global_id', $this->globalId)
            ->update([
                'commencement_date' => '2024-01-01',
                'start_date' => '2024-01-01',
                'annual_leave' => 0,
            ]);

        EmploymentContract::query()->create([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'global_id' => $this->globalId,
            'contract_number' => 'C-000',
            'start_date' => '2018-01-01',
            'commencement_date' => null,
            'contract_duration' => '12',
            'notice_period' => 30,
            'probation_period' => 90,
            'nature_work_id' => null,
            'type_working_hour_id' => null,
            'working_hours' => 8,
            'annual_leave' => 0,
            'country_id' => $this->country->id,
            'right_terminate_id' => null,
        ]);

        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $summary = app(AttendanceDashboardService::class)->buildSummary($filters);

        $this->assertSame(30, $summary['contract']['leave_allowance']);
    }

    public function test_builds_achieved_attendance_metrics(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $summary = app(AttendanceDashboardService::class)->buildSummary($filters);

        $this->assertSame(5, $summary['achieved']['attendance_days']);
        $this->assertSame(40.0, $summary['achieved']['worked_hours']);
        $this->assertSame(2, $summary['achieved']['used_leaves']);
        $this->assertSame(1, $summary['achieved']['used_holidays']);
    }

    public function test_builds_remaining_balances(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $summary = app(AttendanceDashboardService::class)->buildSummary($filters);

        $this->assertSame(16, $summary['remaining']['attendance_days']);
        $this->assertSame(128.0, $summary['remaining']['worked_hours']);
        $this->assertSame(19, $summary['remaining']['remaining_leaves']);
        $this->assertArrayNotHasKey('remaining_holidays', $summary['remaining']);
    }
}
