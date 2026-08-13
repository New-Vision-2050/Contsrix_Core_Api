<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Builds the all-day window used when the employee is attendance_type=flexible.
 */
final class FlexibleWorkDay
{
    public const DEFAULT_REQUIRED_MINUTES = 540; // 9 hours

    /**
     * @param  array<string, mixed>  $workRules
     * @return array<string, mixed>
     */
    public static function applyToWorkRules(array $workRules, string $date, string $timezone): array
    {
        $workRules['attendance_type'] = AttendanceType::FLEXIBLE;

        if (($workRules['day_status'] ?? null) !== 'work_day') {
            return $workRules;
        }

        $requiredMinutes = self::requiredMinutesFromWorkRules($workRules);
        $hours = round($requiredMinutes / 60, 2);

        $period = [
            'date' => $date,
            'start_time' => '00:00',
            'end_time' => '23:59',
            'extends_to_next_day' => false,
            'total_work_hours' => $hours,
            'attendance_type' => AttendanceType::FLEXIBLE,
        ];

        $workRules['all_work_periods'] = [$period];
        $workRules['current_work_period'] = $period;
        $workRules['active_or_next_period'] = $period;
        $workRules['early_clock_in_minutes'] = 0;
        $workRules['early_clock_in_rules'] = [
            'prevent_early_clock_in' => false,
            'early_period' => 0,
            'early_unit' => 'minutes',
        ];
        $workRules['extension_minutes'] = 0;
        $workRules['can_clock_in_before_minutes'] = null;
        $workRules['flexible_required_work_minutes'] = $requiredMinutes;
        $workRules['total_work_hours'] = $hours;

        return $workRules;
    }

    /**
     * @param  array<string, mixed>  $workRules
     */
    public static function requiredMinutesFromWorkRules(array $workRules): int
    {
        if (isset($workRules['flexible_required_work_minutes']) && is_numeric($workRules['flexible_required_work_minutes'])) {
            return max(1, (int) $workRules['flexible_required_work_minutes']);
        }

        $hours = $workRules['total_work_hours'] ?? null;
        if (is_numeric($hours) && (float) $hours > 0) {
            return max(1, (int) round((float) $hours * 60));
        }

        $periods = $workRules['all_work_periods'] ?? [];
        if (is_array($periods)) {
            foreach ($periods as $period) {
                if (! is_array($period)) {
                    continue;
                }
                if (isset($period['total_work_hours']) && is_numeric($period['total_work_hours']) && (float) $period['total_work_hours'] > 0) {
                    return max(1, (int) round((float) $period['total_work_hours'] * 60));
                }
            }
        }

        return self::DEFAULT_REQUIRED_MINUTES;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dayBounds(string $date, string $timezone): array
    {
        $start = Carbon::parse($date.' 00:00:00', $timezone);
        $end = Carbon::parse($date.' 23:59:59', $timezone);

        return [$start, $end];
    }

    public static function dayStartImmutable(string $date, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::parse($date.' 00:00:00', $timezone);
    }

    public static function dayEndImmutable(string $date, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::parse($date.' 23:59:59', $timezone);
    }
}
