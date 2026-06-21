<?php

declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\DTO\AttendanceReportFilterDTO;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Leave\PublicHoliday\Models\PublicHolidayDay;
use Modules\User\Models\User;
use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;

class AttendanceReportRepository extends BaseRepository
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function attendanceQuery(AttendanceReportFilterDTO $filters): Builder
    {
        $query = Attendance::query()
            ->where('company_id', $filters->company_id)
            ->where('user_id', $filters->employee_id)
            ->whereBetween('business_date', [$filters->periodStart(), $filters->periodEnd()]);

        if ($filters->year !== null) {
            $query->whereYear('business_date', $filters->year);
        }

        if ($filters->month !== null) {
            $query->whereMonth('business_date', $filters->month);
        }

        return $query;
    }

    public function getAttendanceRecords(AttendanceReportFilterDTO $filters)
    {
        return $this->attendanceQuery($filters)
            ->orderBy('business_date')
            ->get();
    }

    public function getEmployeeForCompany(string $companyId, string $employeeId): User
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('id', $employeeId)
            ->firstOrFail();
    }

    public function getAttendanceTotals(AttendanceReportFilterDTO $filters): object
    {
        return $this->attendanceQuery($filters)
            ->selectRaw('
                COUNT(DISTINCT CASE
                    WHEN COALESCE(is_absent, 0) = 0
                        AND COALESCE(is_holiday, 0) = 0
                        AND clock_in_time IS NOT NULL
                    THEN business_date
                END) AS actual_attendance_days,
                COALESCE(SUM(total_work_hours), 0) AS actual_worked_hours,
                COUNT(DISTINCT CASE
                    WHEN COALESCE(is_holiday, 0) = 1
                    THEN business_date
                END) AS used_holidays
            ')
            ->first();
    }

    public function getMonthlyAttendanceAggregates(AttendanceReportFilterDTO $filters, array $monthKeys): Collection
    {
        if ($monthKeys === []) {
            return collect();
        }

        return $this->attendanceQuery($filters)
            ->whereIn(DB::raw('SUBSTR(business_date, 1, 7)'), $monthKeys)
            ->selectRaw("
                SUBSTR(business_date, 1, 7) AS month_key,
                COUNT(DISTINCT CASE
                    WHEN COALESCE(is_absent, 0) = 0
                        AND COALESCE(is_holiday, 0) = 0
                        AND clock_in_time IS NOT NULL
                    THEN business_date
                END) AS actual_attendance_days,
                COALESCE(SUM(total_work_hours), 0) AS actual_worked_hours,
                COALESCE(SUM(overtime_hours), 0) AS overtime,
                COALESCE(SUM(CASE WHEN COALESCE(is_late, 0) = 1 THEN 1 ELSE 0 END), 0) AS delays,
                COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
                COALESCE(SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END), 0) AS pending_approval_count,
                COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_count,
                MIN(status) AS fallback_status
            ")
            ->groupBy('month_key')
            ->get()
            ->keyBy('month_key');
    }

    public function getEmploymentContract(string $companyId, string $globalId): ?EmploymentContract
    {
        return EmploymentContract::query()
            ->where('company_id', $companyId)
            ->where('global_id', $globalId)
            ->first();
    }

    public function getOfficialServiceStartDate(string $companyId, string $globalId): ?string
    {
        $result = EmploymentContract::query()
            ->where('company_id', $companyId)
            ->where('global_id', $globalId)
            ->where(function ($query) {
                $query->whereNotNull('commencement_date')
                    ->orWhereNotNull('start_date');
            })
            ->selectRaw('MIN(COALESCE(commencement_date, start_date)) AS service_start_date')
            ->first();

        $serviceStartDate = $result?->service_start_date;

        return $serviceStartDate !== null && $serviceStartDate !== ''
            ? (string) $serviceStartDate
            : null;
    }

    public function sumApprovedLeaveDays(
        string $companyId,
        string $userId,
        string $fromDate,
        string $toDate,
    ): float {
        $periodStart = Carbon::parse($fromDate)->startOfDay();
        $periodEnd = Carbon::parse($toDate)->startOfDay();

        return (float) LeaveRequest::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('start_date', [$fromDate, $toDate])
                    ->orWhereBetween('end_date', [$fromDate, $toDate])
                    ->orWhere(function ($inner) use ($fromDate, $toDate) {
                        $inner->where('start_date', '<=', $fromDate)
                            ->where('end_date', '>=', $toDate);
                    });
            })
            ->get(['start_date', 'end_date'])
            ->sum(function (LeaveRequest $leave) use ($periodStart, $periodEnd) {
                $start = Carbon::parse($leave->start_date)->startOfDay()->max($periodStart);
                $end = Carbon::parse($leave->end_date)->startOfDay()->min($periodEnd);

                if ($end->lt($start)) {
                    return 0;
                }

                return $start->diffInDays($end) + 1;
            });
    }

    public function sumApprovedLeaveDaysForMonth(
        string $companyId,
        string $userId,
        int $year,
        int $month,
    ): float {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return $this->sumApprovedLeaveDays($companyId, $userId, $start, $end);
    }

    public function countPublicHolidayDaysInMonth(int $year, int $month, ?string $countryId): int
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return $this->countPublicHolidayDaysInPeriod($start, $end, $countryId);
    }

    public function countPublicHolidayDaysInPeriod(string $fromDate, string $toDate, ?string $countryId): int
    {
        if ($countryId === null || $countryId === '') {
            return 0;
        }

        return PublicHolidayDay::query()
            ->join('public_holidays', 'public_holiday_days.public_holiday_id', '=', 'public_holidays.id')
            ->where('public_holidays.country_id', $countryId)
            ->where('public_holidays.is_active', true)
            ->whereBetween('public_holiday_days.date', [$fromDate, $toDate])
            ->distinct()
            ->pluck('public_holiday_days.date')
            ->unique(fn ($date) => Carbon::parse($date)->toDateString())
            ->filter(fn ($date) => Carbon::parse($date)->isWeekday())
            ->count();
    }
}
