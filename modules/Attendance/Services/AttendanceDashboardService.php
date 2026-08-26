<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Repositories\AttendanceReportRepository;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

class AttendanceDashboardService
{
    public function __construct(
        private AttendanceReportRepository $repository,
    ) {}

    /**
     * @return array{contract: array, achieved: array, remaining: array}
     */
    public function buildSummary(AttendanceReportFilterDTO $filters): array
    {
        $user = $this->repository->getEmployeeForCompany($filters->company_id, $filters->employee_id);
        $contract = $this->resolveContract($filters->company_id, $user);
        $serviceStartDate = $this->resolveServiceStartDate($filters->company_id, $user);
        $countryId = $contract?->country_id !== null ? (string) $contract->country_id : null;
        $publicHolidays = $this->repository->countPublicHolidayDaysInPeriod(
            $filters->periodStart(),
            $filters->periodEnd(),
            $countryId,
        );

        $attendanceDays = AttendanceReportCalculator::requiredAttendanceDays(
            $filters->periodStart(),
            $filters->periodEnd(),
            $publicHolidays,
        );
        $dailyHours = AttendanceReportCalculator::resolveDailyWorkingHours(
            $contract?->working_hours !== null ? (int) $contract->working_hours : null,
        );
        $leaveAllowance = AttendanceReportCalculator::annualLeaveEntitlement(
            $serviceStartDate,
            $filters->periodEnd(),
        );
        $requiredHours = AttendanceReportCalculator::contractRequiredHours($attendanceDays, $dailyHours);

        $attendanceTotals = $this->repository->getAttendanceTotals($filters);
        $actualDays = (int) ($attendanceTotals->actual_attendance_days ?? 0);
        $workedHours = round((float) ($attendanceTotals->actual_worked_hours ?? 0), 1);
        $usedLeaves = $this->repository->sumApprovedLeaveDays(
            $filters->company_id,
            $filters->employee_id,
            $filters->periodStart(),
            $filters->periodEnd(),
        );
        $usedHolidays = (int) ($attendanceTotals->used_holidays ?? 0);

        return [
            'contract' => [
                'attendance_days' => $attendanceDays,
                'required_hours' => $requiredHours,
                'leave_allowance' => (int) $leaveAllowance,
            ],
            'achieved' => [
                'attendance_days' => $actualDays,
                'worked_hours' => $workedHours,
                'used_leaves' => (int) round($usedLeaves),
                'used_holidays' => $usedHolidays,
            ],
            'remaining' => [
                'attendance_days' => AttendanceReportCalculator::remainingAttendanceDays($attendanceDays, $actualDays),
                'worked_hours' => AttendanceReportCalculator::remainingHours($requiredHours, $workedHours),
                'remaining_leaves' => (int) AttendanceReportCalculator::remainingLeaves($leaveAllowance, $usedLeaves),
            ],
        ];
    }

    private function resolveContract(string $companyId, User $user): ?EmploymentContract
    {
        if ($user->global_company_user_id === null) {
            return null;
        }

        return $this->repository->getEmploymentContract($companyId, (string) $user->global_company_user_id);
    }

    private function resolveServiceStartDate(string $companyId, User $user): ?string
    {
        if ($user->global_company_user_id === null) {
            return null;
        }

        return $this->repository->getOfficialServiceStartDate($companyId, (string) $user->global_company_user_id);
    }
}
