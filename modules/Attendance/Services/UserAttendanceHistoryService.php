<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;

/**
 * Stateless singleton — safe under Octane/RoadRunner.
 * No mutable instance state; every method is pure request-scoped computation.
 */
final class UserAttendanceHistoryService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
    ) {}

    // =============================================================================
    // Public API
    // =============================================================================

    public function getUserAttendanceHistoryMobileApi(
        UuidInterface|string $userId,
        ?int $month = null,
        ?int $year = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $user = $this->getUserWithRelations($userId);
        $timezone = $this->getTimezone();
        $now = $this->now();

        // Last 3 calendar days in branch timezone: today, yesterday, day-before (e.g. 22, 21, 20).
        // month/year query params are ignored for this mobile history endpoint.
        $rangeStart = $now->copy()->startOfDay()->subDays(2);
        $rangeEnd = $now->copy()->endOfDay();

        $dateStart = $rangeStart->toDateString();
        $dateEnd = $rangeEnd->toDateString();
        $naiveStart = $rangeStart->format('Y-m-d H:i:s');
        $naiveEnd = $rangeEnd->format('Y-m-d H:i:s');

        $historyColumns = [
            'id', 'user_id', 'company_id', 'status', 'timezone',
            'start_time', 'end_time', 'clock_in_time', 'clock_out_time',
            'late_minutes', 'overtime_hours', 'total_work_hours',
            'clock_in_location', 'clock_out_location',
            'business_date', 'is_late', 'is_absent', 'is_holiday',
        ];

        $allAttendances = Attendance::query()
            ->select($historyColumns)
            ->where('user_id', $user->id)
            ->where('status', '!=', Attendance::STATUS_WAITING)
            ->where(function ($q) use ($dateStart, $dateEnd, $naiveStart, $naiveEnd) {
                $q->where(function ($q1) use ($dateStart, $dateEnd) {
                    $q1->whereNotNull('business_date')
                        ->whereBetween('business_date', [$dateStart, $dateEnd]);
                })->orWhere(function ($q2) use ($naiveStart, $naiveEnd) {
                    $q2->whereNull('business_date')
                        ->whereNotNull('start_time')
                        ->whereBetween('start_time', [$naiveStart, $naiveEnd]);
                })->orWhere(function ($q3) use ($naiveStart, $naiveEnd) {
                    $q3->whereNull('business_date')
                        ->whereNull('start_time')
                        ->whereNotNull('clock_in_time')
                        ->whereBetween('clock_in_time', [$naiveStart, $naiveEnd]);
                });
            })
            ->get()
            ->sortByDesc(function (Attendance $a) {
                $ref = $a->start_time ?? $a->clock_in_time;
                if ($ref === null) {
                    return 0;
                }
                return $ref instanceof Carbon ? $ref->timestamp : Carbon::parse($ref)->timestamp;
            })
            ->values();

        $attendancesByDate = $allAttendances
            ->groupBy(fn (Attendance $a) => $this->attendanceWorkDayYmd($a, $timezone))
            ->filter(fn ($group, $key) => $key !== null);

        $allDates = $this->buildLastThreeCalendarDaysKeysDescending($now)->reverse();
        $totalDates = $allDates->count();
        $lastPage = (int) ceil($totalDates / $perPage);
        $offset = ($page - 1) * $perPage;

        $paginatedDates = $allDates->slice($offset, $perPage)->values();

        $result = [];
        foreach ($paginatedDates as $dateValue) {
            $dateString = (string) $dateValue;
            $dateCarbon = $this->parseDateTime($dateString, $timezone);
            $attendances = $attendancesByDate->get($dateString, collect());

            $periodsWithAttendance = $this->buildHistoryPeriodsForDay($user, $dateString, $dateCarbon, $attendances, $timezone);

            $dayStatus = $this->determineDayStatus($attendances);
            $dayName = $this->getDayNameArabic($dateCarbon);

            $result[] = [
                'date'          => $dateString,
                'day_name'      => $dayName,
                'status'        => $dayStatus,
                'is_late'       => (int) $attendances->contains(fn ($a) => (bool) $a->is_late),
                'is_absent'     => (int) $attendances->contains(fn ($a) => (bool) $a->is_absent),
                'is_holiday'    => (int) $attendances->contains(fn ($a) => (bool) $a->is_holiday),
                'periods_count' => count($periodsWithAttendance),
                'periods'       => $periodsWithAttendance,
            ];
        }

        return [
            'data'       => $result,
            'pagination' => [
                'page'         => $page,
                'per_page'     => $perPage,
                'total'        => $totalDates,
                'last_page'    => $lastPage,
                'next_page'    => $page < $lastPage ? $page + 1 : null,
                'result_count' => count($result),
            ],
        ];
    }

    public function getUserAttendanceHistory(
        UuidInterface|string $userId,
        ?int $month = null,
        ?int $year = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $user = $this->getUserWithRelations($userId);
        $timezone = $this->getTimezone();
        $now = $this->now();
        $currentYear = $year ?? $now->year;
        $currentMonth = $month ?? $now->month;

        $rangeStart = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->startOfMonth();
        $monthEnd = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->endOfMonth();

        $dateStart = $rangeStart->toDateString();
        $dateEnd = $monthEnd->toDateString();
        $naiveStart = $rangeStart->format('Y-m-d 00:00:00');
        $naiveEnd = $monthEnd->format('Y-m-d 23:59:59');

        $historyColumns = [
            'id', 'user_id', 'company_id', 'status', 'timezone',
            'start_time', 'end_time', 'clock_in_time', 'clock_out_time',
            'late_minutes', 'overtime_hours', 'total_work_hours',
            'clock_in_location', 'clock_out_location',
            'business_date', 'is_late', 'is_absent', 'is_holiday',
        ];

        $allAttendances = Attendance::query()
            ->select($historyColumns)
            ->where('user_id', $user->id)
            ->where('status', '!=', Attendance::STATUS_WAITING)
            ->where(function ($q) use ($dateStart, $dateEnd, $naiveStart, $naiveEnd) {
                $q->where(function ($q1) use ($dateStart, $dateEnd) {
                    $q1->whereNotNull('business_date')
                        ->whereBetween('business_date', [$dateStart, $dateEnd]);
                })->orWhere(function ($q2) use ($naiveStart, $naiveEnd) {
                    $q2->whereNull('business_date')
                        ->whereNotNull('start_time')
                        ->whereBetween('start_time', [$naiveStart, $naiveEnd]);
                })->orWhere(function ($q3) use ($naiveStart, $naiveEnd) {
                    $q3->whereNull('business_date')
                        ->whereNull('start_time')
                        ->whereNotNull('clock_in_time')
                        ->whereBetween('clock_in_time', [$naiveStart, $naiveEnd]);
                });
            })
            ->get()
            ->sortByDesc(function (Attendance $a) {
                $ref = $a->start_time ?? $a->clock_in_time;
                if ($ref === null) {
                    return 0;
                }
                return $ref instanceof Carbon ? $ref->timestamp : Carbon::parse($ref)->timestamp;
            })
            ->values();

        $attendancesByDate = $allAttendances
            ->groupBy(fn (Attendance $a) => $this->attendanceWorkDayYmd($a, $timezone))
            ->filter(fn ($group, $key) => $key !== null);

        $allDates = $this->buildMonthDateKeysAsc($rangeStart, $monthEnd);
        $totalDates = $allDates->count();
        $lastPage = (int) ceil($totalDates / $perPage);
        $offset = ($page - 1) * $perPage;

        $paginatedDates = $allDates->slice($offset, $perPage)->values();

        $result = [];
        foreach ($paginatedDates as $dateValue) {
            $dateString = (string) $dateValue;
            $dateCarbon = $this->parseDateTime($dateString, $timezone);
            $attendances = $attendancesByDate->get($dateString, collect());

            $periodsWithAttendance = $this->buildHistoryPeriodsForDay($user, $dateString, $dateCarbon, $attendances, $timezone);

            $dayStatus = $this->determineDayStatus($attendances);
            $dayName = $this->getDayNameArabic($dateCarbon);

            $result[] = [
                'date'          => $dateString,
                'day_name'      => $dayName,
                'status'        => $dayStatus,
                'is_late'       => (int) $attendances->contains(fn($a) => (bool) $a->is_late),
                'is_absent'     => (int) $attendances->contains(fn($a) => (bool) $a->is_absent),
                'is_holiday'    => (int) $attendances->contains(fn($a) => (bool) $a->is_holiday),
                'periods_count' => count($periodsWithAttendance),
                'periods'       => $periodsWithAttendance,
            ];
        }

        return [
            'data'       => $result,
            'pagination' => [
                'page'         => $page,
                'per_page'     => $perPage,
                'total'        => $totalDates,
                'last_page'    => $lastPage,
                'next_page'    => $page < $lastPage ? $page + 1 : null,
                'result_count' => count($result),
            ],
        ];
    }

    // =============================================================================
    // Date helpers
    // =============================================================================

    private function buildLastThreeCalendarDaysKeysDescending(Carbon $nowAtBranch): Collection
    {
        $todayStart = $nowAtBranch->copy()->startOfDay();

        return collect([
            $todayStart->toDateString(),
            $todayStart->copy()->subDay()->toDateString(),
            $todayStart->copy()->subDays(2)->toDateString(),
        ])->values();
    }

    private function buildMonthDateKeysAsc(Carbon $rangeStart, Carbon $monthEnd): Collection
    {
        $dates = [];
        $cursor = $rangeStart->copy()->startOfDay();
        $end = $monthEnd->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return collect($dates)->values();
    }

    // =============================================================================
    // Period building
    // =============================================================================

    private function buildHistoryPeriodsForDay(
        User $user,
        string $dateString,
        Carbon $dateCarbon,
        Collection $attendances,
        string $timezone
    ): array {
        $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $dateString, $timezone);
        $scheduled = $workRules['all_work_periods'] ?? [];
        $isScheduledWorkDay = ($workRules['day_status'] ?? null) === 'work_day';

        if (!$isScheduledWorkDay || !is_array($scheduled) || $scheduled === []) {
            return $this->buildPeriodsFromAttendances($attendances);
        }

        $earlyRules = is_array($workRules['early_clock_in_rules'] ?? null)
            ? $workRules['early_clock_in_rules']
            : [];

        return $this->mergeScheduledPeriodsWithAggregatedAttendances(
            $dateString, $dateCarbon, $attendances, $scheduled, $timezone, $earlyRules
        );
    }

    /** @param array<int, array<string, mixed>> $scheduled */
    private function mergeScheduledPeriodsWithAggregatedAttendances(
        string $dateString,
        Carbon $dateCarbon,
        Collection $attendances,
        array $scheduled,
        string $timezone,
        array $earlyClockInRules
    ): array {
        $aggregatedMap = $this->buildAggregatedPeriodsKeyed($attendances);
        $filtered = $this->sortAndFilterScheduledPeriodsForHistory($scheduled, $dateString, $timezone);

        if ($filtered === []) {
            return $this->buildPeriodsFromAttendances($attendances);
        }

        $remaining = $aggregatedMap;
        $periods = [];

        foreach ($filtered as $period) {
            $exactKey = $this->scheduledPeriodMatchKeyFromWorkPeriod($period, $dateCarbon);
            $pickedKey = null;

            if (isset($remaining[$exactKey])) {
                $pickedKey = $exactKey;
            } else {
                $fuzzyKey = $this->findBestRemainingGroupKeyForPeriodWindow(
                    $remaining, $period, $dateCarbon, $earlyClockInRules
                );
                if ($fuzzyKey !== null) {
                    $pickedKey = $fuzzyKey;
                }
            }

            if ($pickedKey !== null) {
                $row = $remaining[$pickedKey];
                unset($remaining[$pickedKey]);
                $periods[] = $this->mergeAttendanceRowWithSchedulePeriod($row, $period, $dateCarbon);
            } else {
                $periods[] = $this->emptyScheduledPeriodRow($period, $dateCarbon);
            }
        }

        // Drop placeholder groups that never matched a schedule slot and carry no punch data.
        foreach ($remaining as $key => $row) {
            if ($this->isOrphanEmptyHistoryAggregatedRow($row)) {
                unset($remaining[$key]);
            }
        }

        foreach ($remaining as $row) {
            $periods[] = $this->stripInternalHistoryPeriodKeys($row);
        }

        return $periods;
    }

    /** @param array<string, mixed> $row */
    private function isOrphanEmptyHistoryAggregatedRow(array $row): bool
    {
        if (($row['clock_in_time'] ?? null) !== null || ($row['clock_out_time'] ?? null) !== null) {
            return false;
        }

        $wh = (string) ($row['work_hours'] ?? '00:00');
        $dh = (string) ($row['delay_hours'] ?? '00:00');
        $oh = (string) ($row['overtime_hours'] ?? '00:00');

        return $wh === '00:00' && $dh === '00:00' && $oh === '00:00';
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $schedulePeriod
     */
    private function mergeAttendanceRowWithSchedulePeriod(array $row, array $schedulePeriod, Carbon $dateCarbon): array
    {
        unset($row['first_clock_in_timestamp']);

        if (!empty($schedulePeriod['period_start_time_carbon']) && !empty($schedulePeriod['period_end_time_carbon'])) {
            $tz = $this->getTimezone();
            $start = $schedulePeriod['period_start_time_carbon']->copy()->setTimezone($tz);
            $end = $schedulePeriod['period_end_time_carbon']->copy()->setTimezone($tz);
        } else {
            $start = $this->parsePeriodTime($schedulePeriod, 'start', $dateCarbon);
            $end = $this->parsePeriodTime($schedulePeriod, 'end', $dateCarbon);
        }

        $row['start_time'] = $start->format('H:i');
        $row['end_time'] = $end->format('H:i');

        return $row;
    }

    /** @param array<string, mixed> $row */
    private function stripInternalHistoryPeriodKeys(array $row): array
    {
        unset($row['first_clock_in_timestamp']);
        return $row;
    }

    /** @param array<string, array<string, mixed>> $remaining */
    private function findBestRemainingGroupKeyForPeriodWindow(
        array $remaining,
        array $schedulePeriod,
        Carbon $dateCarbon,
        array $earlyClockInRules
    ): ?string {
        $tz = $this->getTimezone();
        $candidates = [];
        foreach ($remaining as $key => $row) {
            $ts = $row['first_clock_in_timestamp'] ?? null;
            if ($ts === null) {
                continue;
            }
            $clockIn = Carbon::createFromTimestamp($ts, $tz);
            if ($this->clockInFallsInScheduledPeriodWindow($clockIn, $schedulePeriod, $dateCarbon, $earlyClockInRules)) {
                $candidates[] = ['key' => (string) $key, 'ts' => (int) $ts];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (array $a, array $b): int => $a['ts'] <=> $b['ts']);

        return $candidates[0]['key'];
    }

    private function clockInFallsInScheduledPeriodWindow(
        Carbon $clockIn,
        array $period,
        Carbon $dateCarbon,
        array $earlyClockInRules
    ): bool {
        $tz = $this->getTimezone();
        if (!empty($period['period_start_time_carbon']) && !empty($period['period_end_time_carbon'])) {
            $periodStart = $period['period_start_time_carbon']->copy()->setTimezone($tz);
            $periodEnd   = $period['period_end_time_carbon']->copy()->setTimezone($tz);
        } else {
            $periodStart = $this->parsePeriodTime($period, 'start', $dateCarbon);
            $periodEnd   = $this->parsePeriodTime($period, 'end', $dateCarbon);
        }

        $ci = $clockIn->copy()->setTimezone($tz);

        if ($ci->between($periodStart, $periodEnd, true)) {
            return true;
        }

        $earlyPeriod = (int) ($earlyClockInRules['early_period'] ?? 0);
        $earlyUnit   = (string) ($earlyClockInRules['early_unit'] ?? 'minutes');
        if ($earlyPeriod <= 0 || $earlyUnit === '') {
            return false;
        }
        if (strtolower($earlyUnit) === 'minute') {
            $earlyUnit = 'minutes';
        }

        return $ci->between($periodStart->copy()->sub($earlyPeriod, $earlyUnit), $periodEnd, true);
    }

    /** @param array<int, array<string, mixed>> $scheduled */
    private function sortAndFilterScheduledPeriodsForHistory(array $scheduled, string $dateString, string $timezone): array
    {
        $filtered = array_values(array_filter($scheduled, function (array $p) use ($dateString, $timezone) {
            return $this->periodAppliesToHistoryDate($p, $dateString, $timezone);
        }));

        usort($filtered, function (array $a, array $b) {
            $ta = isset($a['period_start_time_carbon']) ? $a['period_start_time_carbon']->timestamp : 0;
            $tb = isset($b['period_start_time_carbon']) ? $b['period_start_time_carbon']->timestamp : 0;
            return $ta <=> $tb;
        });

        return $filtered;
    }

    private function periodAppliesToHistoryDate(array $period, string $dateString, string $timezone): bool
    {
        if (($period['status'] ?? '') === 'spillover') {
            return false;
        }

        if (isset($period['date']) && (string) $period['date'] !== $dateString) {
            return false;
        }

        if (!empty($period['period_start_time_carbon'])) {
            return $period['period_start_time_carbon']->copy()->setTimezone($timezone)->toDateString() === $dateString;
        }

        return ($period['date'] ?? null) === $dateString;
    }

    private function scheduledPeriodMatchKeyFromWorkPeriod(array $period, Carbon $dateCarbon): string
    {
        if (!empty($period['period_start_time_carbon']) && !empty($period['period_end_time_carbon'])) {
            $ps = $period['period_start_time_carbon']->copy()->setTimezone($this->getTimezone());
            $pe = $period['period_end_time_carbon']->copy()->setTimezone($this->getTimezone());

            return $this->scheduleBoundsKey($ps, $pe);
        }

        $ps = $this->parsePeriodTime($period, 'start', $dateCarbon);
        $pe = $this->parsePeriodTime($period, 'end', $dateCarbon);

        return $this->scheduleBoundsKey($ps, $pe);
    }

    private function scheduleBoundsKey(Carbon $start, Carbon $end): string
    {
        return $start->timestamp . '|' . $end->timestamp;
    }

    private function emptyScheduledPeriodRow(array $period, Carbon $dateCarbon): array
    {
        if (!empty($period['period_start_time_carbon']) && !empty($period['period_end_time_carbon'])) {
            $tz    = $this->getTimezone();
            $start = $period['period_start_time_carbon']->copy()->setTimezone($tz);
            $end   = $period['period_end_time_carbon']->copy()->setTimezone($tz);
        } else {
            $start = $this->parsePeriodTime($period, 'start', $dateCarbon);
            $end   = $this->parsePeriodTime($period, 'end', $dateCarbon);
        }

        return [
            'start_time'         => $start->format('H:i'),
            'end_time'           => $end->format('H:i'),
            'first_clock_in_time'  => null,
            'last_clock_out_time'  => null,
            'clock_in_time'      => null,
            'clock_out_time'     => null,
            'work_hours'         => '00:00',
            'delay_hours'        => '00:00',
            'overtime_hours'     => '00:00',
            'clock_in_location'  => null,
            'clock_out_location' => null,
        ];
    }

    // =============================================================================
    // Aggregation
    // =============================================================================

    /** @return array<string, array<string, mixed>> */
    private function buildAggregatedPeriodsKeyed(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        $keyed = [];
        foreach ($attendances->groupBy(fn (Attendance $a) => $this->shiftScheduleGroupKey($a)) as $key => $group) {
            $keyed[$key] = $this->buildAggregatedPeriodFromShiftGroup($group);
        }

        return $keyed;
    }

    private function shiftScheduleGroupKey(Attendance $attendance): string
    {
        $tz = $this->getTimezone();
        if ($attendance->start_time === null || $attendance->end_time === null) {
            return 'id:' . $attendance->id;
        }

        $workYmd = $this->attendanceWorkDayYmd($attendance, $tz);
        if ($workYmd === null) {
            $start = $this->toCarbon($attendance->start_time, $tz);
            $end = $this->toCarbon($attendance->end_time, $tz);
        } else {
            $start = $this->anchorShiftNaiveToWorkDay($attendance->start_time, $workYmd, $tz);
            $end   = $this->anchorShiftNaiveToWorkDay($attendance->end_time, $workYmd, $tz);
        }
        if ($end->lessThan($start)) {
            $end = $end->copy()->addDay();
        }

        return $this->scheduleBoundsKey($start, $end);
    }

    /**
     * Work-day in branch context for history grouping. Prefer business_date, then full clock_in / start.
     */
    private function attendanceWorkDayYmd(Attendance $attendance, string $tz): ?string
    {
        if (!empty($attendance->business_date)) {
            $d = $attendance->business_date;
            if ($d instanceof Carbon) {
                return $d->format('Y-m-d');
            }
            if ($d instanceof \DateTimeInterface) {
                return $d->format('Y-m-d');
            }

            return substr((string) $d, 0, 10);
        }
        if ($attendance->clock_in_time) {
            return $this->toCarbon($attendance->clock_in_time, $tz)->format('Y-m-d');
        }
        if ($attendance->start_time) {
            $s = (string) $attendance->start_time;
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $s)) {
                return $this->toCarbon($s, $tz)->format('Y-m-d');
            }
        }

        return null;
    }

    private function anchorShiftNaiveToWorkDay(mixed $value, string $workYmd, string $tz): Carbon
    {
        $s = trim((string) $value);
        if ($s === '') {
            return $this->parseDateTime($workYmd . ' 00:00:00', $tz);
        }
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $s)) {
            return $this->toCarbon($s, $tz);
        }

        return Carbon::parse($workYmd . ' ' . $s, $tz);
    }

    private function buildPeriodsFromAttendances(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        $groups = $attendances->groupBy(fn (Attendance $a) => $this->shiftScheduleGroupKey($a));

        return $groups
            ->sortBy(fn (Collection $group) => $this->earliestClockInTimestampInGroup($group))
            ->map(function (Collection $group) {
                return $this->stripInternalHistoryPeriodKeys($this->buildAggregatedPeriodFromShiftGroup($group));
            })
            ->values()
            ->all();
    }

    /** @param Collection<int, Attendance> $group */
    private function earliestClockInTimestampInGroup(Collection $group): int
    {
        $min = null;
        foreach ($group as $attendance) {
            if (!$attendance->clock_in_time) {
                continue;
            }
            $ts  = $this->toCarbon($attendance->clock_in_time)->timestamp;
            $min = $min === null ? $ts : min($min, $ts);
        }

        return $min ?? PHP_INT_MAX;
    }

    /** @param Collection<int, Attendance> $group */
    private function buildAggregatedPeriodFromShiftGroup(Collection $group): array
    {
        $sortedByClockIn = $group->sortBy(function (Attendance $a) {
            if (!$a->clock_in_time) {
                return PHP_INT_MAX;
            }
            return $this->toCarbon($a->clock_in_time)->timestamp;
        })->values();

        $firstClockInCarbon     = null;
        $lastClockOutCarbon     = null;
        $firstClockInAttendance = null;
        $lastClockOutAttendance = null;
        $totalWorkHours         = 0.0;
        $totalLateMinutes       = 0;
        $totalOvertimeHours     = 0.0;

        foreach ($sortedByClockIn as $attendance) {
            $clock          = $this->extractAttendanceClockData($attendance);
            $clockInCarbon  = $clock['clock_in_carbon'];
            $clockOutCarbon = $clock['clock_out_carbon'];

            $totalWorkHours     += $this->calculateAttendanceWorkHours($attendance, $clockInCarbon, $clockOutCarbon);
            $totalLateMinutes   += (int) ($attendance->late_minutes ?? 0);
            $totalOvertimeHours += (float) ($attendance->overtime_hours ?? 0);

            if ($clockInCarbon !== null) {
                if ($firstClockInCarbon === null || $clockInCarbon->lt($firstClockInCarbon)) {
                    $firstClockInCarbon     = $clockInCarbon;
                    $firstClockInAttendance = $attendance;
                }
            }

            if ($clockOutCarbon !== null) {
                if ($lastClockOutCarbon === null || $clockOutCarbon->gt($lastClockOutCarbon)) {
                    $lastClockOutCarbon     = $clockOutCarbon;
                    $lastClockOutAttendance = $attendance;
                }
            }
        }

        $representative = $sortedByClockIn->first();
        $scheduledStart = $representative->start_time
            ? $this->toCarbon($representative->start_time)->format('H:i')
            : null;
        $scheduledEnd = $representative->end_time
            ? $this->toCarbon($representative->end_time)->format('H:i')
            : null;

        return [
            'start_time'              => $scheduledStart,
            'end_time'                => $scheduledEnd,
            'clock_in_time'           => $firstClockInCarbon?->format('H:i'),
            'clock_out_time'          => $lastClockOutCarbon?->format('H:i'),
            'work_hours'              => $this->formatHoursToTime($totalWorkHours),
            'delay_hours'             => $this->formatMinutesToTime($totalLateMinutes),
            'overtime_hours'          => $this->formatHoursToTime($totalOvertimeHours),
            'clock_in_location'       => $firstClockInAttendance?->clock_in_location,
            'clock_out_location'      => $lastClockOutAttendance?->clock_out_location,
            'first_clock_in_timestamp' => $firstClockInCarbon?->timestamp,
        ];
    }

    private function calculateAttendanceWorkHours(Attendance $attendance, ?Carbon $clockInCarbon, ?Carbon $clockOutCarbon): float
    {
        if (isset($attendance->total_work_hours) && $attendance->total_work_hours > 0) {
            return (float) $attendance->total_work_hours;
        }
        if (!$clockInCarbon) {
            return 0.0;
        }
        $endRef = $clockOutCarbon ?? $this->now();
        return round(max(0, $clockInCarbon->diffInMinutes($endRef, true)) / 60, 2);
    }

    // =============================================================================
    // Display helpers
    // =============================================================================

    private function determineDayStatus(Collection $attendances): string
    {
        if ($attendances->isEmpty()) {
            return 'غائب';
        }

        $hasCompleted = $attendances->contains(fn($a) => $a->clock_out_time !== null);
        $hasActive    = $attendances->contains(fn($a) => $a->clock_out_time === null && $a->status === 'active');

        if ($hasActive) {
            return 'نشط';
        }

        if ($hasCompleted) {
            return 'تم الخروج';
        }

        return 'تم الخروج';
    }

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

    private function formatHoursToTime(float $hours): string
    {
        $totalMinutes = (int) round($hours * 60);
        return sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }

    private function formatMinutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    // =============================================================================
    // Shared utilities (duplicated from UserAttendanceService — kept local to avoid coupling)
    // =============================================================================

    private function getUserWithRelations(UuidInterface|string $userId): User
    {
        $userIdString = is_string($userId) ? $userId : $userId->toString();

        return User::query()
            ->with([
                'professionalData.attendanceConstraint',
                'userProfessionalData.branch.address.country.timezones',
                'userProfessionalData.department',
            ])
            ->findOrFail($userIdString);
    }

    private function getTimezone(): string
    {
        return getTimeZoneBranchByRequest() ?? config('app.timezone');
    }

    private function now(): Carbon
    {
        return Carbon::now($this->getTimezone());
    }

    private function parseDateTime(mixed $value, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? $this->getTimezone();
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone($tz);
        }
        return Carbon::parse($value, $tz);
    }

    private function toCarbon(mixed $value, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? $this->getTimezone();
        return $value instanceof Carbon ? $value->copy()->setTimezone($tz) : Carbon::parse($value, $tz);
    }

    private function parsePeriodTime(array $period, string $type, Carbon $date): Carbon
    {
        $carbonKey = "period_{$type}_time_carbon";
        $timeKey   = "{$type}_time";
        $timezone  = $this->getTimezone();

        if (isset($period[$carbonKey])) {
            $time = $period[$carbonKey];
            return ($time instanceof Carbon ? $time : Carbon::parse($time))->setTimezone($timezone);
        }

        $time = Carbon::parse($date->format('Y-m-d') . ' ' . $period[$timeKey], $timezone);

        if ($type === 'end' && ($period['extends_to_next_day'] ?? false)) {
            $time->addDay();
        }

        return $time;
    }

    private function extractAttendanceClockData(Attendance $attendance): array
    {
        $clockInCarbon  = $attendance->clock_in_time  ? $this->toCarbon($attendance->clock_in_time)  : null;
        $clockOutCarbon = $attendance->clock_out_time ? $this->toCarbon($attendance->clock_out_time) : null;

        return [
            'clock_in_carbon'  => $clockInCarbon,
            'clock_out_carbon' => $clockOutCarbon,
            'clock_in_time'    => $clockInCarbon?->format('H:i'),
            'clock_out_time'   => $clockOutCarbon?->format('H:i'),
        ];
    }
}
