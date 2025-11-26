<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService
    ) {}

    /**
     * Get work rules/constraints for a user
     *
     * @param UuidInterface|string $userId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
     * @throws ModelNotFoundException
     */
    public function getUserConstraints(UuidInterface|string $userId, ?string $date = null): array
    {
        $user = User::findOrFail($userId);
        $targetDate = $date ?? Carbon::now()->format('Y-m-d');
        $dateCarbon = Carbon::parse($targetDate);

        $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $date);
        $attendances = $this->getAttendancesForDate($user, $dateCarbon);

        if (isset($workRules['all_work_periods']) && is_array($workRules['all_work_periods'])) {
            $workRules['all_work_periods'] = $this->enhancePeriodsWithAttendance(
                $workRules['all_work_periods'],
                $attendances,
                $dateCarbon
            );
        }

        return [
            'user_id' => (string) $user->id,
            'user_name' => $user->name,
            'date' => $targetDate,
            'work_rules' => $this->filterWorkRules($workRules),
        ];
    }

    /**
     * Check if user is clocked in
     *
     * @param UuidInterface|string $userId
     * @return array
     * @throws ModelNotFoundException|AttendanceException
     */
    public function checkClockInStatus(UuidInterface|string $userId): array
    {
        $user = User::findOrFail($userId);
        $attendance = $this->getCurrentAttendanceSafely($userId);

        return [
            'user_id' => (string) $user->id,
            'user_name' => $user->name,
            'is_clocked_in' => $attendance?->isActive() ?? false,
            'is_on_break' => $attendance?->isOnBreak() ?? false,
            'attendance_id' => $attendance ? (string) $attendance->id : null,
            'clock_in_time' => $attendance?->clock_in_time?->format('Y-m-d H:i:s'),
            'status' => $attendance?->status ?? 'not_clocked_in',
        ];
    }

    /**
     * Get attendance records for a user on a specific date
     *
     * @param User $user
     * @param Carbon $date
     * @return Collection
     */
    private function getAttendancesForDate(User $user, Carbon $date): Collection
    {
        return Attendance::where('user_id', $user->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('start_time', $date->format('Y-m-d'))
                    ->orWhereDate('clock_in_time', $date->format('Y-m-d'));
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Enhance periods with attendance records
     *
     * @param array $periods
     * @param Collection $attendances
     * @param Carbon $date
     * @return array
     */
    private function enhancePeriodsWithAttendance(array $periods, Collection $attendances, Carbon $date): array
    {
        $now = Carbon::now();
        
        return array_map(function ($period) use ($attendances, $date, $now) {
            $periodStart = $this->parsePeriodTime($period, 'start', $date);
            $periodEnd = $this->parsePeriodTime($period, 'end', $date);

            $totalWorkHours = $this->calculatePeriodWorkHours($periodStart, $periodEnd);
            $periodAttendances = $this->findAttendancesInPeriod($attendances, $periodStart, $periodEnd);
            $isActive = $this->isPeriodActive($periodStart, $periodEnd, $now);

            return $this->mergePeriodData($period, $totalWorkHours, $periodAttendances, $isActive);
        }, $periods);
    }

    /**
     * Parse period time from period data
     *
     * @param array $period
     * @param string $type 'start' or 'end'
     * @param Carbon $date
     * @return Carbon
     */
    private function parsePeriodTime(array $period, string $type, Carbon $date): Carbon
    {
        $carbonKey = "period_{$type}_time_carbon";
        $timeKey = "{$type}_time";

        if (isset($period[$carbonKey])) {
            $time = $period[$carbonKey];
            return $time instanceof Carbon ? $time : Carbon::parse($time);
        }

        $time = Carbon::parse($date->format('Y-m-d') . ' ' . $period[$timeKey]);

        if ($type === 'end' && ($period['extends_to_next_day'] ?? false)) {
            $time->addDay();
        }

        return $time;
    }

    /**
     * Find attendances that fall within a period
     *
     * @param Collection $attendances
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return array
     */
    private function findAttendancesInPeriod(Collection $attendances, Carbon $periodStart, Carbon $periodEnd): array
    {
        return $attendances
            ->filter(function ($attendance) use ($periodStart, $periodEnd) {
                $attendanceStart = $this->getAttendanceTime($attendance, 'start');
                return $attendanceStart && $attendanceStart->between($periodStart, $periodEnd, true);
            })
            ->map(fn($attendance) => $this->formatAttendanceForPeriod($attendance))
            ->values()
            ->toArray();
    }

    /**
     * Get attendance time (start or end)
     *
     * @param Attendance $attendance
     * @param string $type 'start' or 'end'
     * @return Carbon|null
     */
    private function getAttendanceTime(Attendance $attendance, string $type): ?Carbon
    {
        $time = $type === 'start'
            ? ($attendance->start_time ?? $attendance->clock_in_time)
            : ($attendance->end_time ?? $attendance->clock_out_time);

        if (!$time) {
            return null;
        }

        return $time instanceof Carbon ? $time : Carbon::parse($time);
    }

    /**
     * Format attendance data for period response
     *
     * @param Attendance $attendance
     * @return array
     */
    private function formatAttendanceForPeriod(Attendance $attendance): array
    {
        $startTime = $this->getAttendanceTime($attendance, 'start');
        $endTime = $this->getAttendanceTime($attendance, 'end');

        return [
            'status' => $attendance->status ?? 'scheduled',
            'date' => $startTime?->format('Y-m-d'),
            'start_time' => $startTime?->format('H:i'),
            'end_time' => $endTime?->format('H:i'),
        ];
    }

    /**
     * Merge period data with calculated values
     *
     * @param array $period
     * @param float $totalWorkHours
     * @param array $attendance
     * @param bool $isActive
     * @return array
     */
    private function mergePeriodData(array $period, float $totalWorkHours, array $attendance, bool $isActive): array
    {
        $cleanedPeriod = $period;
        unset($cleanedPeriod['period_start_time_carbon'], $cleanedPeriod['period_end_time_carbon']);

        return array_merge($cleanedPeriod, [
            'total_work_hours' => $totalWorkHours,
            'is_active' => $isActive,
            'attendance' => $attendance,
        ]);
    }

    /**
     * Filter work rules to only include required fields
     *
     * @param array $workRules
     * @return array
     */
    private function filterWorkRules(array $workRules): array
    {
        return [
            'day_status' => $workRules['day_status'] ?? null,
            'day_name' => $workRules['day_name'] ?? null,
            'is_holiday' => $workRules['is_holiday'] ?? false,
            'reason' => $workRules['reason'] ?? null,
            'all_work_periods' => $workRules['all_work_periods'] ?? [],
        ];
    }

    /**
     * Calculate work hours for a period
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return float
     */
    private function calculatePeriodWorkHours(Carbon $start, Carbon $end): float
    {
        return round($start->diffInMinutes($end) / 60, 2);
    }

    /**
     * Check if period is currently active (current time is within period range)
     *
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param Carbon $now
     * @return bool
     */
    private function isPeriodActive(Carbon $periodStart, Carbon $periodEnd, Carbon $now): bool
    {
        return $now->between($periodStart, $periodEnd, true);
    }

    /**
     * Get current attendance safely, handling exceptions
     *
     * @param UuidInterface|string $userId
     * @return Attendance|null
     */
    private function getCurrentAttendanceSafely(UuidInterface|string $userId): ?Attendance
    {
        try {
            $userIdUuid = is_string($userId) ? Uuid::fromString($userId) : $userId;
            return $this->attendanceService->getCurrentAttendance($userIdUuid);
        } catch (\Exception $e) {
            return null;
        }
    }
}

