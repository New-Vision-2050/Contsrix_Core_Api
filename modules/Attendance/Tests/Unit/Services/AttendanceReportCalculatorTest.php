<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Services\AttendanceReportCalculator;
use PHPUnit\Framework\TestCase;

class AttendanceReportCalculatorTest extends TestCase
{
    public function test_contract_required_hours(): void
    {
        $this->assertSame(1760.0, AttendanceReportCalculator::contractRequiredHours(220, 8));
    }

    public function test_remaining_attendance_days(): void
    {
        $this->assertSame(36, AttendanceReportCalculator::remainingAttendanceDays(220, 184));
    }

    public function test_remaining_hours(): void
    {
        $this->assertSame(412.5, AttendanceReportCalculator::remainingHours(2400, 1987.5));
    }

    public function test_remaining_leaves(): void
    {
        $this->assertSame(9.0, AttendanceReportCalculator::remainingLeaves(21, 12));
    }

    public function test_annual_leave_entitlement_for_five_or_fewer_service_years(): void
    {
        $this->assertSame(21, AttendanceReportCalculator::annualLeaveEntitlement('2020-06-21', '2025-06-21'));
    }

    public function test_annual_leave_entitlement_for_more_than_five_service_years(): void
    {
        $this->assertSame(30, AttendanceReportCalculator::annualLeaveEntitlement('2019-01-01', '2025-05-31'));
    }

    public function test_annual_leave_entitlement_defaults_to_minimum_when_start_date_is_missing(): void
    {
        $this->assertSame(21, AttendanceReportCalculator::annualLeaveEntitlement(null, '2025-05-31'));
    }

    public function test_earned_leave_days_are_monthly_fraction_of_annual_entitlement(): void
    {
        $this->assertSame(1.75, AttendanceReportCalculator::earnedLeaveDays(21));
        $this->assertSame(2.5, AttendanceReportCalculator::earnedLeaveDays(30));
    }

    public function test_minutes_to_hours(): void
    {
        $this->assertSame(0.5, AttendanceReportCalculator::minutesToHours(30));
    }

    public function test_required_attendance_days_subtracts_public_holidays_from_weekdays(): void
    {
        $this->assertSame(21, AttendanceReportCalculator::requiredAttendanceDays('2025-05-01', '2025-05-31', 1));
    }

    public function test_is_present_day(): void
    {
        $present = (object) [
            'is_absent' => false,
            'is_holiday' => false,
            'clock_in_time' => '2025-05-01 08:00:00',
        ];
        $holiday = (object) [
            'is_absent' => false,
            'is_holiday' => true,
            'clock_in_time' => null,
        ];

        $this->assertTrue(AttendanceReportCalculator::isPresentDay($present));
        $this->assertFalse(AttendanceReportCalculator::isPresentDay($holiday));
    }

    public function test_count_actual_attendance_days_deduplicates_business_dates(): void
    {
        $records = [
            (object) ['business_date' => '2025-05-01', 'is_absent' => false, 'is_holiday' => false, 'clock_in_time' => 'x'],
            (object) ['business_date' => '2025-05-01', 'is_absent' => false, 'is_holiday' => false, 'clock_in_time' => 'y'],
            (object) ['business_date' => '2025-05-02', 'is_absent' => false, 'is_holiday' => false, 'clock_in_time' => 'z'],
        ];

        $this->assertSame(2, AttendanceReportCalculator::countActualAttendanceDays($records));
    }

    public function test_sum_worked_overtime_and_delay_count(): void
    {
        $records = [
            (object) ['total_work_hours' => 8, 'overtime_hours' => 1.5, 'is_late' => true],
            (object) ['total_work_hours' => 7.5, 'overtime_hours' => 0.5, 'is_late' => false],
        ];

        $this->assertSame(15.5, AttendanceReportCalculator::sumWorkedHours($records));
        $this->assertSame(2.0, AttendanceReportCalculator::sumOvertimeHours($records));
        $this->assertSame(1, AttendanceReportCalculator::countDelays($records));
    }

    public function test_aggregate_monthly_status_prefers_rejected(): void
    {
        $records = [
            (object) ['status' => 'approved'],
            (object) ['status' => 'rejected'],
        ];

        $this->assertSame('rejected', AttendanceReportCalculator::aggregateMonthlyStatus($records));
    }

    public function test_resolve_working_hours_fallback(): void
    {
        $this->assertSame(9.0, AttendanceReportCalculator::resolveDailyWorkingHours(9));
        $this->assertSame(8.0, AttendanceReportCalculator::resolveDailyWorkingHours(null));
    }
}
