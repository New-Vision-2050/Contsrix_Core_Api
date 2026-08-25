<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Which calendar dates the employee's constraint actually schedules work on.
 *
 * Read once per request and reused across a whole month, so a calendar or history page
 * does not pay for a per-day rule build just to answer "is this a weekend?".
 *
 * Used to separate the two Arabic labels that were previously collapsed into one: عطلة
 * belongs to days the schedule does not work (disabled weekday, or a date listed in
 * `time_rules.holidays`), while إجازة belongs to time off granted to the person
 * (approved leave request, or the sub-entity attendance-status override). See INV-18.
 */
final readonly class ScheduledWorkDays
{
    /**
     * @param array<string, bool> $enabledWeekdays lowercase English weekday => enabled
     * @param array<string, true> $holidayDates    Y-m-d => true
     */
    private function __construct(
        private array $enabledWeekdays,
        private array $holidayDates,
        private bool $hasSchedule,
    ) {}

    /**
     * No constraint or no weekly schedule. Every date is treated as schedulable, because
     * with nothing configured there is no basis to call a day عطلة.
     */
    public static function unknown(): self
    {
        return new self([], [], false);
    }

    public static function fromConstraint(?AttendanceConstraint $constraint): self
    {
        if (! $constraint) {
            return self::unknown();
        }

        $timeRules = $constraint->constraint_config['time_rules'] ?? null;
        if (! is_array($timeRules)) {
            return self::unknown();
        }

        $weeklySchedule = is_array($timeRules['weekly_schedule'] ?? null) ? $timeRules['weekly_schedule'] : [];
        if ($weeklySchedule === []) {
            return self::unknown();
        }

        $enabledWeekdays = [];
        foreach ($weeklySchedule as $day => $config) {
            $enabledWeekdays[strtolower((string) $day)] = is_array($config) && (bool) ($config['enabled'] ?? false);
        }

        $holidayDates = [];
        foreach (is_array($timeRules['holidays'] ?? null) ? $timeRules['holidays'] : [] as $holiday) {
            $raw = is_array($holiday) ? ($holiday['date'] ?? null) : $holiday;
            if (empty($raw)) {
                continue;
            }

            try {
                $holidayDates[Carbon::parse((string) $raw)->toDateString()] = true;
            } catch (\Exception) {
            }
        }

        return new self($enabledWeekdays, $holidayDates, true);
    }

    public function hasSchedule(): bool
    {
        return $this->hasSchedule;
    }

    public function isWorkDay(Carbon|string $date): bool
    {
        if (! $this->hasSchedule) {
            return true;
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        if (isset($this->holidayDates[$carbon->toDateString()])) {
            return false;
        }

        return $this->enabledWeekdays[strtolower($carbon->englishDayOfWeek)] ?? false;
    }
}
