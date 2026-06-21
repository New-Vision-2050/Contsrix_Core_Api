<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\LeaveRequest;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;

/**
 * Service for building attendance calendar data for a user across a date range.
 */
class AttendanceCalendarService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private UserAttendanceService $userAttendanceService,
    ) {}

    /**
     * Get attendance calendar for a user across a date range.
     *
     * @param User|UuidInterface|string $userOrId
     * @param string|null $fromDate Y-m-d format
     * @param string|null $toDate Y-m-d format
     * @param int|null $month
     * @param int|null $year
     * @return array{days: array, summary: array}
     */
    public function getCalendar(
        User|UuidInterface|string $userOrId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $month = null,
        ?int $year = null,
    ): array {
        $user = $userOrId instanceof User
            ? $userOrId
            : User::findOrFail((string) $userOrId);

        $timezone = $this->getTimezone();
        $now = Carbon::now($timezone);

        // Resolve date range
        [$rangeStart, $rangeEnd] = $this->resolveDateRange(
            $fromDate,
            $toDate,
            $month,
            $year,
            $now,
            $timezone
        );

        $dateStartStr = $rangeStart->toDateString();
        $dateEndStr   = $rangeEnd->toDateString();

        // Bulk fetch attendance records for the entire range
        $attendances = $this->fetchAttendancesForRange($user, $dateStartStr, $dateEndStr);

        // Bulk fetch approved leave requests for the range
        $leaveDates = $this->fetchLeaveDatesForRange($user, $dateStartStr, $dateEndStr);

        // Build calendar day-by-day
        $days = [];
        $cursor = $rangeStart->copy();

        $presentCount = 0;
        $lateCount    = 0;
        $absentCount  = 0;
        $leaveCount   = 0;
        $offCount     = 0;
        $requiredCount = 0;
        $totalWorkHours = $this->calculateTotalWorkHoursFromGroupedAttendances($attendances);

        while ($cursor->lte($rangeEnd)) {
            $dateString = $cursor->toDateString();
            $isFuture   = $cursor->isAfter($now->copy()->startOfDay());
            $isToday    = $cursor->isSameDay($now);

            $dayAttendances = $attendances->get($dateString, collect());
            $hasLeave       = in_array($dateString, $leaveDates, true);

            $dayData = $this->buildDayData(
                $user,
                $cursor,
                $dateString,
                $isFuture,
                $isToday,
                $dayAttendances,
                $hasLeave,
                $timezone
            );

            $days[] = $dayData;

            // Tally for summary
            switch ($dayData['status_key']) {
                case 'present':
                    $presentCount++;
                    break;
                case 'late':
                    $lateCount++;
                    break;
                case 'absent':
                    $absentCount++;
                    break;
                case 'leave':
                    $leaveCount++;
                    break;
                case 'off':
                    $offCount++;
                    break;
                case 'required':
                    $requiredCount++;
                    break;
            }

            $cursor->addDay();
        }

        return [
            'days' => $days,
            'summary' => [
                'total_days'        => count($days),
                'present_count'     => $presentCount,
                'late_count'        => $lateCount,
                'absent_count'      => $absentCount,
                'leave_count'       => $leaveCount,
                'off_count'         => $offCount,
                'required_count'    => $requiredCount,
                'total_work_hours'  => $totalWorkHours,
            ],
        ];
    }

    /**
     * Build data for a single calendar day.
     */
    private function buildDayData(
        User $user,
        Carbon $date,
        string $dateString,
        bool $isFuture,
        bool $isToday,
        Collection $dayAttendances,
        bool $hasLeave,
        string $timezone
    ): array {
        $dayName = $this->getDayNameArabic($date);

        // Future dates: status depends on constraints
        if ($isFuture) {
            $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $dateString, $timezone);
            $dayStatus = $workRules['day_status'] ?? 'Undefined';

            if ($hasLeave) {
                return $this->formatDay(
                    $dateString,
                    $dayName,
                    'leave',
                    'إجازة',
                    null,
                    $dayAttendances
                );
            }

            if ($dayStatus === 'work_day') {
                $workHours = $this->calculateTotalScheduledHours($workRules['all_work_periods'] ?? []);
                return $this->formatDay(
                    $dateString,
                    $dayName,
                    'required',
                    'مطلوب للحضور',
                    $workHours,
                    $dayAttendances
                );
            }

            return $this->formatDay(
                $dateString,
                $dayName,
                'off',
                'عطلة',
                null,
                $dayAttendances
            );
        }

        // Current or past dates: use actual attendance records
        if ($hasLeave) {
            $workHours = $this->calculateWorkHoursFromAttendances($dayAttendances);
            return $this->formatDay(
                $dateString,
                $dayName,
                'leave',
                'إجازة',
                $workHours,
                $dayAttendances
            );
        }

        // Determine status from attendance records
        if ($dayAttendances->isEmpty()) {
            // No attendance - check if it was a work day via constraints
            $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $dateString, $timezone);
            $dayStatus = $workRules['day_status'] ?? 'Undefined';

            if ($dayStatus === 'work_day') {
                return $this->formatDay(
                    $dateString,
                    $dayName,
                    'absent',
                    'غائب',
                    null,
                    $dayAttendances
                );
            }

            return $this->formatDay(
                $dateString,
                $dayName,
                'off',
                'عطلة',
                null,
                $dayAttendances
            );
        }

        // Has attendance records
        $hasHoliday = $dayAttendances->contains(fn ($a) =>
            ($a->is_holiday ?? false) || ($a->day_status ?? null) === 'holiday' || ($a->status ?? null) === Attendance::STATUS_HOLIDAY
        );

        if ($hasHoliday) {
            return $this->formatDay(
                $dateString,
                $dayName,
                'off',
                'عطلة',
                null,
                $dayAttendances
            );
        }

        $hasLate = $dayAttendances->contains(fn ($a) => $this->isTruthy($a->is_late ?? null));
        $hasAbsent = $dayAttendances->contains(fn ($a) =>
            $this->isTruthy($a->is_absent ?? null) || ($a->status ?? null) === Attendance::STATUS_ABSENT
        );

        if ($hasAbsent && !$hasLate) {
            return $this->formatDay(
                $dateString,
                $dayName,
                'absent',
                'غائب',
                null,
                $dayAttendances
            );
        }

        $workHours = $this->calculateWorkHoursFromAttendances($dayAttendances);

        if ($hasLate) {
            return $this->formatDay(
                $dateString,
                $dayName,
                'late',
                'متأخر',
                $workHours,
                $dayAttendances
            );
        }

        return $this->formatDay(
            $dateString,
            $dayName,
            'present',
            'حاضر',
            $workHours,
            $dayAttendances
        );
    }

    /**
     * Format a single day entry for the response.
     */
    private function formatDay(
        string $date,
        string $dayName,
        string $statusKey,
        string $statusLabel,
        ?float $workHours,
        Collection $attendances
    ): array {
        $durationFormatted = null;
        if ($workHours !== null && $workHours > 0) {
            $hours = (int) $workHours;
            $minutes = (int) round(($workHours - $hours) * 60);
            $durationFormatted = sprintf('%02dh %02dm', $hours, $minutes);
        }

        return [
            'date'               => $date,
            'day_name'           => $dayName,
            'day_number'         => (int) substr($date, 8, 2),
            'status_key'         => $statusKey,
            'status'             => $statusLabel,
            'work_hours'         => $workHours,
            'duration_formatted' => $durationFormatted,
            'dot_color'          => $this->resolveDotColor($statusKey),
            'attendance_count'   => $attendances->count(),
        ];
    }

    /**
     * Resolve the dot color hex code based on status key.
     */
    private function resolveDotColor(string $statusKey): string
    {
        return match ($statusKey) {
            'present'  => '#4CAF50',
            'late'     => '#FF9800',
            'absent'   => '#F44336',
            'leave'    => '#9C27B0',
            'off'      => '#9E9E9E',
            'required' => '#2196F3',
            default    => '#9E9E9E',
        };
    }

    /**
     * Calculate total work hours from attendances.
     */
    private function calculateWorkHoursFromAttendances(Collection $attendances): ?float
    {
        if ($attendances->isEmpty()) {
            return null;
        }

        $totalMinutes = 0;
        foreach ($attendances as $attendance) {
            $totalMinutes += $this->calculateWorkedMinutes($attendance);
        }

        return $totalMinutes > 0 ? round($totalMinutes / 60, 2) : null;
    }

    /**
     * Calculate total work hours from grouped attendances.
     *
     * @param Collection<string, Collection<int, Attendance>> $groupedAttendances
     */
    private function calculateTotalWorkHoursFromGroupedAttendances(Collection $groupedAttendances): float
    {
        $totalMinutes = 0;

        foreach ($groupedAttendances as $attendances) {
            foreach ($attendances as $attendance) {
                $totalMinutes += $this->calculateWorkedMinutes($attendance);
            }
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate worked minutes for an attendance record.
     */
    private function calculateWorkedMinutes(Attendance $attendance): int
    {
        foreach (['worked_minutes', 'work_duration'] as $attribute) {
            $minutes = $this->durationAttributeToMinutes($attendance->getAttribute($attribute));
            if ($minutes !== null) {
                return $minutes;
            }
        }

        $totalWorkHours = $attendance->getAttribute('total_work_hours');
        if (is_numeric($totalWorkHours) && (float) $totalWorkHours > 0) {
            return max(0, (int) round((float) $totalWorkHours * 60));
        }

        $clockIn = $attendance->getAttribute('clock_in_time');
        $clockOut = $attendance->getAttribute('clock_out_time');
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        try {
            $timezone = $attendance->getAttribute('timezone') ?: null;
            $workedMinutes = $this->toCarbon($clockIn, $timezone)
                ->diffInMinutes($this->toCarbon($clockOut, $timezone), false);
        } catch (\Exception) {
            return 0;
        }

        return max(
            0,
            (int) round($workedMinutes - $this->calculateBreakMinutes($attendance, $timezone))
        );
    }

    /**
     * Convert a persisted duration attribute into minutes.
     */
    private function durationAttributeToMinutes(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) round((float) $value));
        }

        if (is_string($value) && preg_match('/^(\d{1,3}):([0-5]\d)(?::([0-5]\d))?$/', $value, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = isset($matches[3]) ? (int) $matches[3] : 0;

            return max(0, ($hours * 60) + $minutes + (int) round($seconds / 60));
        }

        return null;
    }

    /**
     * Calculate break minutes from persisted totals or loaded break records.
     */
    private function calculateBreakMinutes(Attendance $attendance, ?string $timezone = null): int
    {
        $totalBreakHours = $attendance->getAttribute('total_break_hours');
        if (is_numeric($totalBreakHours) && (float) $totalBreakHours > 0) {
            return max(0, (int) round((float) $totalBreakHours * 60));
        }

        if (!$attendance->relationLoaded('breaks')) {
            return 0;
        }

        $totalMinutes = 0;

        foreach ($attendance->getRelation('breaks') as $break) {
            $durationMinutes = $break->duration_minutes ?? null;
            if (is_numeric($durationMinutes) && (int) $durationMinutes > 0) {
                $totalMinutes += (int) $durationMinutes;
                continue;
            }

            $startTime = $break->start_time ?? null;
            $endTime = $break->end_time ?? null;
            if (!$startTime || !$endTime) {
                continue;
            }

            try {
                $totalMinutes += max(
                    0,
                    (int) round(
                        $this->toCarbon($startTime, $timezone)
                            ->diffInMinutes($this->toCarbon($endTime, $timezone), false)
                    )
                );
            } catch (\Exception) {
            }
        }

        return $totalMinutes;
    }

    /**
     * Calculate total scheduled hours from work periods.
     *
     * @param array<int, array<string, mixed>> $periods
     */
    private function calculateTotalScheduledHours(array $periods): ?float
    {
        if (empty($periods)) {
            return null;
        }

        $total = 0.0;
        foreach ($periods as $period) {
            if (!empty($period['period_start_time_carbon']) && !empty($period['period_end_time_carbon'])) {
                $start = $period['period_start_time_carbon'];
                $end   = $period['period_end_time_carbon'];
                if ($start instanceof Carbon && $end instanceof Carbon) {
                    $total += max(0, $start->diffInMinutes($end, false)) / 60;
                }
            } elseif (isset($period['start_time'], $period['end_time'])) {
                try {
                    $start = Carbon::parse($period['start_time']);
                    $end   = Carbon::parse($period['end_time']);
                    $total += max(0, $start->diffInMinutes($end, false)) / 60;
                } catch (\Exception) {
                }
            }
        }

        return $total > 0 ? round($total, 2) : null;
    }

    /**
     * Fetch attendance records grouped by date for the range.
     *
     * @return Collection<string, Collection<int, Attendance>>
     */
    private function fetchAttendancesForRange(User $user, string $dateStart, string $dateEnd): Collection
    {
        $historyColumns = [
            'id', 'user_id', 'company_id', 'status', 'timezone',
            'start_time', 'end_time', 'clock_in_time', 'clock_out_time',
            'late_minutes', 'overtime_hours', 'total_work_hours', 'total_break_hours',
            'business_date', 'day_status', 'is_late', 'is_absent', 'is_holiday',
        ];

        foreach (['worked_minutes', 'work_duration'] as $durationColumn) {
            if (Schema::hasColumn('attendances', $durationColumn)) {
                $historyColumns[] = $durationColumn;
            }
        }

        $records = Attendance::query()
            ->select($historyColumns)
            ->with(['breaks' => function ($query) {
                $query->select(
                    'id',
                    'attendance_id',
                    'company_id',
                    'start_time',
                    'end_time',
                    'duration_minutes'
                )
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time');
            }])
            ->where('user_id', $user->id)
            ->where('status', '!=', Attendance::STATUS_WAITING)
            ->where(function ($q) use ($dateStart, $dateEnd) {
                $q->where(function ($q1) use ($dateStart, $dateEnd) {
                    $q1->whereNotNull('business_date')
                        ->whereBetween('business_date', [$dateStart, $dateEnd]);
                })->orWhere(function ($q2) use ($dateStart, $dateEnd) {
                    $q2->whereNull('business_date')
                        ->whereNotNull('start_time')
                        ->whereBetween('start_time', [
                            $dateStart . ' 00:00:00',
                            $dateEnd . ' 23:59:59',
                        ]);
                })->orWhere(function ($q3) use ($dateStart, $dateEnd) {
                    $q3->whereNull('business_date')
                        ->whereNull('start_time')
                        ->whereNotNull('clock_in_time')
                        ->whereBetween('clock_in_time', [
                            $dateStart . ' 00:00:00',
                            $dateEnd . ' 23:59:59',
                        ]);
                });
            })
            ->get();

        $timezone = $this->getTimezone();

        return $records
            ->groupBy(function (Attendance $a) use ($timezone) {
                if (!empty($a->business_date)) {
                    $d = $a->business_date;
                    return $d instanceof Carbon ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
                }
                if ($a->clock_in_time) {
                    return $this->toCarbon($a->clock_in_time, $timezone)->format('Y-m-d');
                }
                if ($a->start_time) {
                    $s = (string) $a->start_time;
                    if (preg_match('/\d{4}-\d{2}-\d{2}/', $s)) {
                        return $this->toCarbon($s, $timezone)->format('Y-m-d');
                    }
                }
                return null;
            })
            ->filter(fn ($group, $key) => $key !== null);
    }

    /**
     * Fetch approved leave request dates as an array of Y-m-d strings.
     *
     * @return array<int, string>
     */
    private function fetchLeaveDatesForRange(User $user, string $dateStart, string $dateEnd): array
    {
        $leaveRequests = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where(function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('start_date', [$dateStart, $dateEnd])
                  ->orWhereBetween('end_date', [$dateStart, $dateEnd])
                  ->orWhere(function ($q2) use ($dateStart, $dateEnd) {
                      $q2->where('start_date', '<=', $dateStart)
                         ->where('end_date', '>=', $dateEnd);
                  });
            })
            ->get(['start_date', 'end_date']);

        $dates = [];
        foreach ($leaveRequests as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end   = Carbon::parse($leave->end_date);
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $dates[] = $cursor->toDateString();
                $cursor->addDay();
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * Resolve the date range from request parameters.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(
        ?string $fromDate,
        ?string $toDate,
        ?int $month,
        ?int $year,
        Carbon $now,
        string $timezone
    ): array {
        if ($fromDate && $toDate) {
            $start = Carbon::parse($fromDate, $timezone)->startOfDay();
            $end   = Carbon::parse($toDate, $timezone)->endOfDay();
            return [$start, $end];
        }

        $targetYear  = $year ?? $now->year;
        $targetMonth = $month ?? $now->month;

        $start = Carbon::create($targetYear, $targetMonth, 1, 0, 0, 0, $timezone)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        return [$start, $end];
    }

    /**
     * Get Arabic day name.
     */
    private function getDayNameArabic(Carbon $date): string
    {
        $dayNames = [
            'Sunday'    => 'الأحد',
            'Monday'    => 'الاثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
            'Saturday'  => 'السبت',
        ];

        return $dayNames[$date->format('l')] ?? $date->format('l');
    }

    /**
     * Get the resolved timezone.
     */
    private function getTimezone(): string
    {
        return getTimeZoneBranchByRequest() ?? config('app.timezone');
    }

    /**
     * Parse a datetime value to Carbon instance.
     */
    private function toCarbon(mixed $value, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? $this->getTimezone();
        return $value instanceof Carbon ? $value->copy()->setTimezone($tz) : Carbon::parse($value, $tz);
    }

    /**
     * Check if a value is truthy.
     */
    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }
        return false;
    }
}
