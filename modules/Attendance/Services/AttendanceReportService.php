<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Repositories\AttendanceReportRepository;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

class AttendanceReportService
{
    public function __construct(
        private AttendanceReportRepository $repository,
    ) {}

    public function getEmployee(AttendanceReportFilterDTO $filters): User
    {
        return $this->repository->getEmployeeForCompany($filters->company_id, $filters->employee_id);
    }

    /**
     * @return array{data: array<int, array>, pagination: array<string, int>}
     */
    public function listMonthlyReports(AttendanceReportFilterDTO $filters): array
    {
        $totalMonths = $this->totalMonths($filters);
        $months = $this->resolvePaginatedMonthKeys($filters, $totalMonths);

        $user = $this->repository->getEmployeeForCompany($filters->company_id, $filters->employee_id);
        $contract = $this->resolveContract($filters->company_id, $user);
        $serviceStartDate = $this->resolveServiceStartDate($filters->company_id, $user);
        $dailyHours = AttendanceReportCalculator::resolveDailyWorkingHours(
            $contract?->working_hours !== null ? (int) $contract->working_hours : null,
        );
        $leaveAllowance = AttendanceReportCalculator::annualLeaveEntitlement(
            $serviceStartDate,
            $filters->periodEnd(),
        );
        $countryId = $contract?->country_id !== null ? (string) $contract->country_id : null;

        $monthlyAggregates = $this->repository->getMonthlyAttendanceAggregates($filters, $months);

        $monthLeaveUsed = [];
        $chronologicalMonths = array_reverse($months);
        foreach ($chronologicalMonths as $monthKey) {
            [$year, $month] = array_map('intval', explode('-', $monthKey));
            $monthLeaveUsed[$monthKey] = $this->repository->sumApprovedLeaveDaysForMonth(
                $filters->company_id,
                $filters->employee_id,
                $year,
                $month,
            );
        }

        $runningPrior = $this->sumLeaveUsedBeforePage($filters, $chronologicalMonths);
        $rowByMonth = [];
        foreach ($chronologicalMonths as $monthKey) {
            $rowByMonth[$monthKey] = $this->buildMonthlyRow(
                $monthKey,
                $monthlyAggregates->get($monthKey),
                $filters,
                $dailyHours,
                $leaveAllowance,
                $runningPrior,
                $countryId,
            );
            $runningPrior += (float) ($monthLeaveUsed[$monthKey] ?? 0);
        }

        return [
            'data' => array_values(array_map(fn ($key) => $rowByMonth[$key], $months)),
            'pagination' => [
                'current_page' => $filters->page,
                'per_page' => $filters->per_page,
                'total' => $totalMonths,
                'last_page' => max(1, (int) ceil($totalMonths / $filters->per_page)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMonthlyRow(
        string $monthKey,
        ?object $monthAggregate,
        AttendanceReportFilterDTO $filters,
        float $dailyHours,
        float $leaveAllowance,
        float $priorLeaveUsed,
        ?string $countryId,
    ): array {
        [$year, $month] = array_map('intval', explode('-', $monthKey));
        $carbon = Carbon::create($year, $month, 1);
        $daysInMonth = $carbon->daysInMonth;

        // 1. أيام نهاية الأسبوع في هذا الشهر
        $weekendDays = $this->repository->countWeekendDaysInMonth($year, $month);

        // 2. جميع أيام العطل الرسمية في هذا الشهر (بما في ذلك الواقعة في نهاية الأسبوع)
        $officialHolidaysAll = $this->repository->countAllPublicHolidayDaysInPeriod(
            $carbon->toDateString(),
            $carbon->copy()->endOfMonth()->toDateString(),
            $countryId
        );

        // 3. إجمالي أيام العطل الشهرية = نهاية الأسبوع + العطل الرسمية
        $monthHolidays = $weekendDays + $officialHolidaysAll;

        // 4. أيام الحضور المطلوبة تبقى معتمدة على الدالة القديمة التي تستثني نهاية الأسبوع
        $officialHolidaysWorkdays = $this->repository->countPublicHolidayDaysInMonth($year, $month, $countryId);
        $requiredAttendanceDays = AttendanceReportCalculator::requiredAttendanceDays(
            $carbon->toDateString(),
            $carbon->copy()->endOfMonth()->toDateString(),
            $officialHolidaysWorkdays,
        );

        $usedLeaves = $this->repository->sumApprovedLeaveDaysForMonth(
            $filters->company_id,
            $filters->employee_id,
            $year,
            $month,
        );
        $leaveBalanceUsed = (int) round($usedLeaves);
        $remainingLeaveBalance = (int) AttendanceReportCalculator::remainingLeaves(
            $leaveAllowance,
            $priorLeaveUsed + $usedLeaves,
        );

        $requiredHours = AttendanceReportCalculator::monthlyRequiredHours($requiredAttendanceDays, $dailyHours);
        $actualDays = (int) ($monthAggregate->actual_attendance_days ?? 0);
        $workedHours = round((float) ($monthAggregate->actual_worked_hours ?? 0), 1);

        return [
            'month' => $carbon->format('F Y'),
            'days_in_month' => $daysInMonth,
            'required_attendance_days' => $requiredAttendanceDays,
            'used_leaves' => (int) round($usedLeaves),
            'earned_leave_days' => AttendanceReportCalculator::earnedLeaveDays($leaveAllowance),
            'month_holidays' => $monthHolidays,
            'required_hours' => $requiredHours,
            'actual_attendance_days' => $actualDays,
            'remaining_attendance_days' => AttendanceReportCalculator::remainingAttendanceDays($requiredAttendanceDays, $actualDays),
            'leave_balance_used' => $leaveBalanceUsed,
            'remaining_leave_balance' => $remainingLeaveBalance,
            'actual_worked_hours' => $workedHours,
            'calculated_hours' => round((float) ($monthAggregate->calculated_hours ?? 0), 1),
            'remaining_hours' => AttendanceReportCalculator::remainingHours($requiredHours, $workedHours),
            'delays' => (int) ($monthAggregate->delays ?? 0),
            'overtime' => round((float) ($monthAggregate->overtime ?? 0), 1),
            'status' => $monthAggregate === null
                ? 'pending'
                : AttendanceReportCalculator::aggregateMonthlyStatusFromCounts($monthAggregate),
        ];
    }

    /**
     * @return list<string>
     */
    private function resolvePaginatedMonthKeys(AttendanceReportFilterDTO $filters, int $totalMonths): array
    {
        $end = Carbon::parse($filters->periodEnd())->startOfMonth();
        $lowerBound = Carbon::parse($filters->periodStart())->startOfMonth();
        $offset = ($filters->page - 1) * $filters->per_page;

        if ($offset >= $totalMonths) {
            return [];
        }

        $cursor = $end->copy()->subMonths($offset);
        $months = [];

        while (count($months) < $filters->per_page && $cursor->gte($lowerBound)) {
            $months[] = $cursor->format('Y-m');
            $cursor->subMonth();
        }

        return $months;
    }

    private function totalMonths(AttendanceReportFilterDTO $filters): int
    {
        $start = Carbon::parse($filters->periodStart())->startOfMonth();
        $end = Carbon::parse($filters->periodEnd())->startOfMonth();

        if ($end->lt($start)) {
            return 0;
        }

        return (int) $start->diffInMonths($end) + 1;
    }

    /**
     * @param list<string> $chronologicalMonths
     */
    private function sumLeaveUsedBeforePage(AttendanceReportFilterDTO $filters, array $chronologicalMonths): float
    {
        if ($chronologicalMonths === []) {
            return 0.0;
        }

        $periodStart = Carbon::parse($filters->periodStart())->toDateString();
        $firstPageMonth = Carbon::createFromFormat('Y-m-d', $chronologicalMonths[0].'-01')->startOfMonth();
        $beforePageEnd = $firstPageMonth->copy()->subDay();

        if ($beforePageEnd->lt(Carbon::parse($periodStart))) {
            return 0.0;
        }

        return $this->repository->sumApprovedLeaveDays(
            $filters->company_id,
            $filters->employee_id,
            $periodStart,
            $beforePageEnd->toDateString(),
        );
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
