<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Presenters\AttendanceDashboardPresenter;
use Modules\Attendance\Presenters\AttendanceReportRowPresenter;

class AttendanceReportPresenterTest extends BaseAttendanceReportTestCase
{
    public function test_dashboard_presenter_shapes_contract_block(): void
    {
        $presented = (new AttendanceDashboardPresenter([
            'contract' => ['attendance_days' => 21, 'required_hours' => 168, 'leave_allowance' => 21],
            'achieved' => ['attendance_days' => 5, 'worked_hours' => 40, 'used_leaves' => 2, 'used_holidays' => 1],
            'remaining' => ['attendance_days' => 16, 'worked_hours' => 128, 'remaining_leaves' => 19],
        ]))->getData();

        $this->assertSame(21, $presented['contract']['attendance_days']);
        $this->assertSame(168.0, $presented['contract']['required_hours']);
        $this->assertArrayNotHasKey('holiday_allowance', $presented['contract']);
        $this->assertArrayNotHasKey('remaining_holidays', $presented['remaining']);
    }

    public function test_report_row_presenter_shapes_monthly_fields(): void
    {
        $presented = (new AttendanceReportRowPresenter([
            'month' => 'May 2025',
            'days_in_month' => 31,
            'required_attendance_days' => 21,
            'used_leaves' => 2,
            'earned_leave_days' => 1.75,
            'month_holidays' => 8,
            'required_hours' => 168,
            'actual_attendance_days' => 21,
            'remaining_attendance_days' => 0,
            'leave_balance_used' => 2,
            'remaining_leave_balance' => 19,
            'actual_worked_hours' => 161,
            'remaining_hours' => 7,
            'delays' => 2,
            'overtime' => 12,
            'status' => 'approved',
        ]))->getData();

        $this->assertSame('May 2025', $presented['month']);
        $this->assertSame(1.75, $presented['earned_leave_days']);
        $this->assertSame(0, $presented['remaining_attendance_days']);
        $this->assertSame(7.0, $presented['remaining_hours']);
        $this->assertArrayNotHasKey('deductions', $presented);
        $this->assertArrayNotHasKey('additions', $presented);
        $this->assertSame('approved', $presented['status']);
    }

    public function test_filter_dto_resolves_period_from_year_and_month(): void
    {
        $dto = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            year: 2025,
            month: 5,
        );

        $this->assertSame('2025-05-01', $dto->periodStart());
        $this->assertSame('2025-05-31', $dto->periodEnd());
    }
}
