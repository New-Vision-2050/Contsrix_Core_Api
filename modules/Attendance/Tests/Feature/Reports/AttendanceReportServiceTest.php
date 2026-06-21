<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\Reports;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Attendance\Services\AttendanceDashboardService;
use Modules\Attendance\Services\AttendanceReportService;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

class AttendanceReportServiceTest extends BaseAttendanceReportTestCase
{
    public function test_lists_monthly_report_row_for_may(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $result = app(AttendanceReportService::class)->listMonthlyReports($filters);
        $this->assertCount(1, $result['data']);

        $row = $result['data'][0];
        $this->assertSame('May 2025', $row['month']);
        $this->assertSame(31, $row['days_in_month']);
        $this->assertSame(5, $row['actual_attendance_days']);
        $this->assertSame(40.0, $row['actual_worked_hours']);
        $this->assertSame(2, $row['delays']);
        $this->assertSame(2.0, $row['overtime']);
        $this->assertSame(1.75, $row['earned_leave_days']);
        $this->assertArrayNotHasKey('deductions', $row);
        $this->assertArrayNotHasKey('additions', $row);
        $this->assertSame('approved', $row['status']);
    }

    public function test_monthly_earned_leave_days_uses_senior_entitlement(): void
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

        $row = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'][0];

        $this->assertSame(2.5, $row['earned_leave_days']);
        $this->assertSame(28, $row['remaining_leave_balance']);
    }

    public function test_monthly_used_leaves_matches_approved_requests(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $row = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'][0];
        $this->assertSame(2, $row['used_leaves']);
    }

    public function test_partial_leave_overlap_is_counted_inside_each_month_only(): void
    {
        LeaveRequest::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => (string) $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2025-05-30',
            'end_date' => '2025-06-03',
            'total_days' => 5,
            'reason' => 'Cross-month leave',
            'status' => LeaveRequest::STATUS_APPROVED,
            'requested_by' => (string) $this->employee->id,
        ]);

        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-06-30',
        );

        $rows = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'];
        $rowsByMonth = collect($rows)->keyBy('month');

        $this->assertSame(4, $rowsByMonth['May 2025']['used_leaves']);
        $this->assertSame(3, $rowsByMonth['June 2025']['used_leaves']);
    }

    public function test_monthly_required_days_counts_only_active_holidays_for_contract_country(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $row = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'][0];

        $this->assertSame(1, $row['month_holidays']);
        $this->assertSame(21, $row['required_attendance_days']);
    }

    public function test_missing_contract_country_does_not_count_global_holidays(): void
    {
        EmploymentContract::query()
            ->where('company_id', $this->company->id)
            ->where('global_id', $this->globalId)
            ->update(['country_id' => null]);

        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $row = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'][0];

        $this->assertSame(0, $row['month_holidays']);
        $this->assertSame(22, $row['required_attendance_days']);
    }

    public function test_monthly_remaining_fields_are_calculated(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $row = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'][0];

        $this->assertSame(16, $row['remaining_attendance_days']);
        $this->assertSame(128.0, $row['remaining_hours']);
    }

    public function test_returns_all_monthly_rows_for_single_employee_period(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-01-01',
            to_date: '2025-12-31',
        );

        $result = app(AttendanceReportService::class)->listMonthlyReports($filters);

        $this->assertCount(12, $result['data']);
        $this->assertSame(1, $result['pagination']['current_page']);
        $this->assertSame(12, $result['pagination']['per_page']);
        $this->assertSame(12, $result['pagination']['total']);
        $this->assertSame(1, $result['pagination']['last_page']);
    }

    public function test_monthly_rows_are_sorted_newest_first(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-01-01',
            to_date: '2025-06-30',
        );

        $rows = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'];

        $this->assertSame('June 2025', $rows[0]['month']);
        $this->assertSame('May 2025', $rows[1]['month']);
        $this->assertSame('January 2025', $rows[5]['month']);
    }

    public function test_monthly_pagination_limits_rows_and_reports_metadata(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-01-01',
            to_date: '2025-12-31',
            page: 1,
            per_page: 5,
        );

        $result = app(AttendanceReportService::class)->listMonthlyReports($filters);

        $this->assertCount(5, $result['data']);
        $this->assertSame('December 2025', $result['data'][0]['month']);
        $this->assertSame('August 2025', $result['data'][4]['month']);
        $this->assertSame([
            'current_page' => 1,
            'per_page' => 5,
            'total' => 12,
            'last_page' => 3,
        ], $result['pagination']);
    }

    public function test_page_two_returns_older_months(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-01-01',
            to_date: '2025-12-31',
            page: 2,
            per_page: 5,
        );

        $rows = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'];

        $this->assertSame('July 2025', $rows[0]['month']);
        $this->assertSame('March 2025', $rows[4]['month']);
    }

    public function test_current_month_is_first_record_when_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-21 12:00:00'));

        try {
            $filters = new AttendanceReportFilterDTO(
                company_id: $this->company->id,
                employee_id: (string) $this->employee->id,
                per_page: 1,
            );

            $result = app(AttendanceReportService::class)->listMonthlyReports($filters);

            $this->assertSame('June 2026', $result['data'][0]['month']);
            $this->assertSame(6, $result['pagination']['total']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_and_monthly_required_days_use_same_formula(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            from_date: '2025-05-01',
            to_date: '2025-05-31',
        );

        $summary = app(AttendanceDashboardService::class)->buildSummary($filters);
        $row = app(AttendanceReportService::class)->listMonthlyReports($filters)['data'][0];

        $this->assertSame($row['required_attendance_days'], $summary['contract']['attendance_days']);
    }

    public function test_year_filter_limits_period(): void
    {
        $filters = new AttendanceReportFilterDTO(
            company_id: $this->company->id,
            employee_id: (string) $this->employee->id,
            year: 2025,
        );

        $result = app(AttendanceReportService::class)->listMonthlyReports($filters);
        $this->assertNotEmpty($result['data']);
    }
}
