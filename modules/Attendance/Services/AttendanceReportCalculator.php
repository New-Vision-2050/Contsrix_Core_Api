<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Single source of truth for HR Attendance Report formulas.
 *
 * @see modules/Attendance/docs/attendance-reports/FORMULAS.md
 */
final class AttendanceReportCalculator
{
    public const BASE_ANNUAL_LEAVE_ALLOWANCE = 21;
    public const SENIOR_ANNUAL_LEAVE_ALLOWANCE = 30;
    public const SENIOR_SERVICE_YEARS_THRESHOLD = 5;

    public static function contractRequiredHours(int $attendanceDays, float $dailyWorkingHours): float
    {
        return round($attendanceDays * $dailyWorkingHours, 1);
    }

    public static function remainingAttendanceDays(int $contractDays, int $actualDays): int
    {
        return max(0, $contractDays - $actualDays);
    }

    public static function remainingHours(float $requiredHours, float $actualHours): float
    {
        return round(max(0, $requiredHours - $actualHours), 1);
    }

    public static function remainingLeaves(float $allowance, float $used): float
    {
        return round(max(0, $allowance - $used), 1);
    }

    public static function minutesToHours(int $minutes): float
    {
        return round($minutes / 60, 1);
    }

    public static function sumWorkedHours(iterable $records): float
    {
        $total = 0.0;
        foreach ($records as $record) {
            $total += (float) ($record->total_work_hours ?? 0);
        }

        return round($total, 1);
    }

    public static function sumOvertimeHours(iterable $records): float
    {
        $total = 0.0;
        foreach ($records as $record) {
            $total += (float) ($record->overtime_hours ?? 0);
        }

        return round($total, 1);
    }

    /**
     * COUNT distinct business_date WHERE present (not absent, not holiday, clocked in).
     */
    public static function countActualAttendanceDays(iterable $records): int
    {
        $dates = [];
        foreach ($records as $record) {
            if (self::isPresentDay($record)) {
                $dates[(string) $record->business_date] = true;
            }
        }

        return count($dates);
    }

    public static function isPresentDay(object $record): bool
    {
        return ! (bool) ($record->is_absent ?? false)
            && ! (bool) ($record->is_holiday ?? false)
            && $record->clock_in_time !== null;
    }

    public static function countUsedHolidays(iterable $records): int
    {
        $dates = [];
        foreach ($records as $record) {
            if ((bool) ($record->is_holiday ?? false) && $record->business_date !== null) {
                $dates[(string) $record->business_date] = true;
            }
        }

        return count($dates);
    }

    public static function countDelays(iterable $records): int
    {
        $count = 0;
        foreach ($records as $record) {
            if ((bool) ($record->is_late ?? false)) {
                $count++;
            }
        }

        return $count;
    }

    public static function monthlyRequiredHours(int $requiredAttendanceDays, float $dailyWorkingHours): float
    {
        return self::contractRequiredHours($requiredAttendanceDays, $dailyWorkingHours);
    }

    public static function serviceYears(?string $serviceStartDate, string $asOfDate): ?int
    {
        if ($serviceStartDate === null || trim($serviceStartDate) === '') {
            return null;
        }

        $startDate = Carbon::parse($serviceStartDate)->startOfDay();
        $endDate = Carbon::parse($asOfDate)->startOfDay();

        if ($endDate->lt($startDate)) {
            return 0;
        }

        return (int) $startDate->diffInYears($endDate);
    }

    public static function annualLeaveEntitlement(?string $serviceStartDate, string $asOfDate): int
    {
        $serviceYears = self::serviceYears($serviceStartDate, $asOfDate);

        if ($serviceYears !== null && $serviceYears > self::SENIOR_SERVICE_YEARS_THRESHOLD) {
            return self::SENIOR_ANNUAL_LEAVE_ALLOWANCE;
        }

        return self::BASE_ANNUAL_LEAVE_ALLOWANCE;
    }

    public static function earnedLeaveDays(float|int $leaveAllowance): float
    {
        return round((float) $leaveAllowance / 12, 2);
    }

    public static function requiredAttendanceDays(string $fromDate, string $toDate, int $publicHolidays): int
    {
        $workingDays = 0;

        foreach (CarbonPeriod::create(Carbon::parse($fromDate), Carbon::parse($toDate)) as $day) {
            if (! $day->isWeekend()) {
                $workingDays++;
            }
        }

        return max(0, $workingDays - $publicHolidays);
    }

    public static function aggregateMonthlyStatus(iterable $records): string
    {
        $statuses = [];
        foreach ($records as $record) {
            $statuses[] = (string) ($record->status ?? '');
        }

        if ($statuses === []) {
            return 'pending';
        }

        if (in_array('rejected', $statuses, true)) {
            return 'rejected';
        }

        if (in_array('pending_approval', $statuses, true)) {
            return 'pending_approval';
        }

        if (in_array('approved', $statuses, true) && ! in_array('completed', $statuses, true)) {
            return 'approved';
        }

        if (in_array('approved', $statuses, true) || in_array('completed', $statuses, true)) {
            return 'approved';
        }

        return $statuses[0] ?: 'pending';
    }

    public static function aggregateMonthlyStatusFromCounts(object $aggregate): string
    {
        if ((int) ($aggregate->rejected_count ?? 0) > 0) {
            return 'rejected';
        }

        if ((int) ($aggregate->pending_approval_count ?? 0) > 0) {
            return 'pending_approval';
        }

        if ((int) ($aggregate->approved_count ?? 0) > 0 || (int) ($aggregate->completed_count ?? 0) > 0) {
            return 'approved';
        }

        return (string) ($aggregate->fallback_status ?: 'pending');
    }

    public static function resolveDailyWorkingHours(?int $workingHours): float
    {
        if ($workingHours !== null && $workingHours > 0) {
            return (float) $workingHours;
        }

        return 8.0;
    }
}
