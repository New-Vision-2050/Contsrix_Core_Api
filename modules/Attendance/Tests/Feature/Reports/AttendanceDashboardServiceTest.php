<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Services\AttendanceDashboardService;

class AttendanceDashboardServiceTest extends BaseAttendanceReportTestCase
{
    public function test_builds_contract_summary_from_employment_contract(): void
    {
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
