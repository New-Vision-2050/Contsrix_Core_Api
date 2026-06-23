<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\User\Models\User;

class DashboardOverviewService
{
    private const WEEK_START_DAY = Carbon::SATURDAY;

    public function __construct(
        private readonly AttendanceConstraintService $constraintService,
    ) {}

    public function overview(User $user): array
    {
        $timezone = $this->resolveTimezone();
        $now = Carbon::now($timezone);
        [$weekStart, $weekEnd] = $this->currentSaturdayFridayWeek($now);
        $previousWeekStart = $weekStart->copy()->subWeek();
        $previousWeekEnd = $weekEnd->copy()->subWeek();

        $workedMinutes = $this->sumWorkedMinutes($user, $weekStart, $weekEnd);
        $previousWorkedMinutes = $this->sumWorkedMinutes($user, $previousWeekStart, $previousWeekEnd);
        $requiredMinutes = $this->requiredMinutesForWeek($user, $weekStart, $timezone);
        $remainingMinutes = max($requiredMinutes - $workedMinutes, 0);

        return [
            'timezone' => $timezone,
            'week' => [
                'starts_on' => 'saturday',
                'from_date' => $weekStart->toDateString(),
                'to_date' => $weekEnd->toDateString(),
            ],
            'tasks' => $this->taskCard($user, $now),
            'attendance' => [
                'period' => 'current_week',
                'worked_minutes' => $workedMinutes,
                'worked' => $this->formatMinutes($workedMinutes),
                'required_minutes' => $requiredMinutes,
                'remaining_minutes' => $remainingMinutes,
                'previous_worked_minutes' => $previousWorkedMinutes,
                'percentage_change' => $this->percentageChange($workedMinutes, $previousWorkedMinutes),
                'trend' => $this->trend($workedMinutes, $previousWorkedMinutes),
                'donut' => [
                    ['key' => 'worked', 'value' => $workedMinutes],
                    ['key' => 'remaining', 'value' => $remainingMinutes],
                ],
            ],
        ];
    }

    private function taskCard(User $user, Carbon $now): array
    {
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();

        $counts = EmployeeTaskRequest::query()
            ->where('company_id', (string) $user->company_id)
            ->where('user_id', (string) $user->id)
            ->whereBetween('task_date', [$previousMonthStart->toDateString(), $currentMonthEnd->toDateString()])
            ->selectRaw(
                '
                COALESCE(SUM(CASE WHEN task_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS current_count,
                COALESCE(SUM(CASE WHEN task_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS previous_count
                ',
                [
                    $currentMonthStart->toDateString(),
                    $currentMonthEnd->toDateString(),
                    $previousMonthStart->toDateString(),
                    $previousMonthEnd->toDateString(),
                ],
            )
            ->first();

        $currentCount = (int) ($counts?->current_count ?? 0);
        $previousCount = (int) ($counts?->previous_count ?? 0);

        return [
            'period' => 'current_month',
            'count' => $currentCount,
            'previous_count' => $previousCount,
            'percentage_change' => $this->percentageChange($currentCount, $previousCount),
            'trend' => $this->trend($currentCount, $previousCount),
        ];
    }

    private function sumWorkedMinutes(User $user, Carbon $from, Carbon $to): int
    {
        $hours = Attendance::query()
            ->where('company_id', (string) $user->company_id)
            ->where('user_id', (string) $user->id)
            ->whereBetween('business_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(total_work_hours), 0) AS worked_hours')
            ->value('worked_hours');

        return (int) round(((float) $hours) * 60);
    }

    private function requiredMinutesForWeek(User $user, Carbon $weekStart, string $timezone): int
    {
        $requiredMinutes = 0;

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $weekStart->copy()->addDays($offset);
            $rules = $this->constraintService->getTodaysWorkRulesForUser(
                $user,
                $date->toDateString(),
                $timezone,
            );

            if (($rules['day_status'] ?? null) !== 'work_day') {
                continue;
            }

            $requiredMinutes += $this->sumScheduledPeriodMinutes($rules['all_work_periods'] ?? []);
        }

        return $requiredMinutes;
    }

    private function sumScheduledPeriodMinutes(array $periods): int
    {
        $total = 0;

        foreach ($periods as $period) {
            if (($period['status'] ?? null) === 'spillover') {
                continue;
            }

            $total += $this->periodDurationMinutes($period);
        }

        return $total;
    }

    private function periodDurationMinutes(array $period): int
    {
        $start = $period['period_start_time_carbon'] ?? null;
        $end = $period['period_end_time_carbon'] ?? null;

        if ($start instanceof Carbon && $end instanceof Carbon) {
            return max(0, (int) $start->diffInMinutes($end, false));
        }

        if (! isset($period['start_time'], $period['end_time'])) {
            return 0;
        }

        $startTime = Carbon::createFromFormat('H:i', (string) $period['start_time']);
        $endTime = Carbon::createFromFormat('H:i', (string) $period['end_time']);

        if (($period['extends_to_next_day'] ?? false) || $endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        return max(0, (int) $startTime->diffInMinutes($endTime, false));
    }

    private function currentSaturdayFridayWeek(Carbon $now): array
    {
        $start = $now->copy()
            ->startOfDay()
            ->subDays(($now->dayOfWeek - self::WEEK_START_DAY + 7) % 7);

        return [$start, $start->copy()->addDays(6)];
    }

    private function formatMinutes(int $minutes): array
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return [
            'hours' => $hours,
            'minutes' => $remainingMinutes,
            'label' => sprintf('%dh %02dm', $hours, $remainingMinutes),
        ];
    }

    private function percentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function trend(int $current, int $previous): string
    {
        return match (true) {
            $current > $previous => 'up',
            $current < $previous => 'down',
            default => 'neutral',
        };
    }

    private function resolveTimezone(): string
    {
        $timezone = function_exists('getTimeZoneBranchByRequest')
            ? getTimeZoneBranchByRequest()
            : null;

        return $timezone ?: config('app.timezone', 'UTC');
    }
}
