<?php

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;

/**
 * Data class representing a complete weekly schedule with multiple periods per day
 */
class WeeklySchedule
{
    private const VALID_DAYS = [
        'sunday', 'monday', 'tuesday', 'wednesday',
        'thursday', 'friday', 'saturday'
    ];

    /** @var array<string, DaySchedule> */
    public readonly array $schedule;

    public function __construct(array $schedule = [])
    {
        $this->schedule = $this->validateAndSetSchedule($schedule);
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $schedule = [];

        foreach ($data as $day => $dayData) {
            $normalizedDay = strtolower(trim($day));

            if (!in_array($normalizedDay, self::VALID_DAYS)) {
                throw new InvalidArgumentException("Invalid day: {$day}. Must be one of: " . implode(', ', self::VALID_DAYS));
            }

            $schedule[$normalizedDay] = DaySchedule::fromArray($dayData);
        }

        foreach (self::VALID_DAYS as $validDay) {
            if (!isset($schedule[$validDay])) {
                $schedule[$validDay] = DaySchedule::disabled();
            }
        }

        return new self($schedule);
    }

    public function toArray(): array
    {
        $result = [];
        foreach (self::VALID_DAYS as $day) {
            if (isset($this->schedule[$day])) {
                $result[$day] = $this->schedule[$day]->toArray();
            } else {
                $result[$day] = DaySchedule::disabled()->toArray();
            }
        }
        return $result;
    }

    private function validateAndSetSchedule(array $schedule): array
    {
        $validatedSchedule = [];

        foreach ($schedule as $day => $daySchedule) {
            $normalizedDay = strtolower(trim($day));

            if (!in_array($normalizedDay, self::VALID_DAYS)) {
                throw new InvalidArgumentException("Invalid day: {$day}. Must be one of: " . implode(', ', self::VALID_DAYS));
            }

            if (!$daySchedule instanceof DaySchedule) {
                throw new InvalidArgumentException("Day schedule for {$day} must be a DaySchedule instance");
            }

            $validatedSchedule[$normalizedDay] = $daySchedule;
        }

        foreach (self::VALID_DAYS as $validDay) {
            if (!isset($validatedSchedule[$validDay])) {
                $validatedSchedule[$validDay] = DaySchedule::disabled();
            }
        }

        return $validatedSchedule;
    }

    public function getDaySchedule(string $day): DaySchedule
    {
        $normalizedDay = strtolower(trim($day));
        // This should always return a DaySchedule due to validateAndSetSchedule ensuring all days exist
        return $this->schedule[$normalizedDay];
    }

    public function setDaySchedule(string $day, DaySchedule $daySchedule): self
    {
        $normalizedDay = strtolower(trim($day));

        if (!in_array($normalizedDay, self::VALID_DAYS)) {
            throw new InvalidArgumentException("Invalid day: {$day}");
        }

        $newSchedule = $this->schedule;
        $newSchedule[$normalizedDay] = $daySchedule;

        return new self($newSchedule);
    }

    public function removeDaySchedule(string $day): self
    {
        $normalizedDay = strtolower(trim($day));
        $newSchedule = $this->schedule;
        unset($newSchedule[$normalizedDay]);

        if (empty($newSchedule)) {
            throw new InvalidArgumentException('Cannot remove the last day from the schedule. Consider disabling it instead.');
        }

        return new self($newSchedule);
    }

    public function getConfiguredDays(): array
    {
        return self::VALID_DAYS;
    }

    public function getEnabledDays(): array
    {
        return array_keys(array_filter(
            $this->schedule,
            fn(DaySchedule $daySchedule) => $daySchedule->enabled
        ));
    }

    public function getDisabledDays(): array
    {
        return array_keys(array_filter(
            $this->schedule,
            fn(DaySchedule $daySchedule) => !$daySchedule->enabled
        ));
    }

    public function hasDaySchedule(string $day): bool
    {
        $normalizedDay = strtolower(trim($day));
        return in_array($normalizedDay, self::VALID_DAYS);
    }

    public function isDayEnabled(string $day): bool
    {
        $daySchedule = $this->getDaySchedule($day);
        return $daySchedule->enabled;
    }

    public function getDayCount(): int
    {
        return count(self::VALID_DAYS);
    }

    public function getEnabledDayCount(): int
    {
        return count($this->getEnabledDays());
    }

    public function getTotalPeriodCount(): int
    {
        $total = 0;
        foreach ($this->schedule as $daySchedule) {
            $total += $daySchedule->getPeriodCount();
        }
        return $total;
    }

    public function getAllPeriodNames(): array
    {
        $allPeriods = [];
        foreach ($this->schedule as $day => $daySchedule) {
            foreach ($daySchedule->getPeriodNames() as $periodName) {
                $allPeriods[] = ucfirst($day) . ': ' . $periodName;
            }
        }
        return $allPeriods;
    }

    public function hasCrossDayPeriods(): bool
    {
        foreach ($this->schedule as $daySchedule) {
            if ($daySchedule->hasCrossDayPeriods()) {
                return true;
            }
        }
        return false;
    }

    public function getTotalWeeklyWorkMinutes(): int
    {
        $total = 0;
        foreach ($this->schedule as $daySchedule) {
            $total += $daySchedule->getTotalWorkMinutes();
        }
        return $total;
    }

    public function getTotalWeeklyWorkHours(): float
    {
        return $this->getTotalWeeklyWorkMinutes() / 60;
    }

    public static function standardWorkWeek(TimePeriod $workPeriod): self
    {
        $schedule = [];
        $workDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        foreach (self::VALID_DAYS as $day) {
            if (in_array($day, $workDays)) {
                $schedule[$day] = DaySchedule::enabled($workPeriod);
            } else {
                $schedule[$day] = DaySchedule::disabled();
            }
        }
        return new self($schedule);
    }

    public static function twentyFourSeven(TimePeriod $dayShift, TimePeriod $nightShift): self
    {
        $schedule = [];

        foreach (self::VALID_DAYS as $day) {
            $schedule[$day] = DaySchedule::enabled($dayShift, $nightShift);
        }

        return new self($schedule);
    }

    public function validate(): array
    {
        $issues = [];

        if ($this->getEnabledDayCount() === 0) {
            $issues[] = 'Schedule must have at least one enabled day.';
        }

        $weeklyHours = $this->getTotalWeeklyWorkHours();
        if ($weeklyHours > 168) {
            $issues[] = "Weekly work hours ({$weeklyHours}) exceed total hours in a week (168 hours).";
        }
        if ($weeklyHours < 0) {
            $issues[] = "Calculated weekly work hours ({$weeklyHours}) are negative. Please check period definitions.";
        }

        $spilloverPeriodsFromPreviousDay = [];

        foreach (self::VALID_DAYS as $dayName) {
            $daySchedule = $this->schedule[$dayName];

            $issues = array_merge($issues, $daySchedule->validate(ucfirst($dayName), $spilloverPeriodsFromPreviousDay));

            $spilloverPeriodsFromPreviousDay = $daySchedule->getPeriodsCrossingToNextDay(ucfirst($dayName));
        }

        foreach ($this->schedule as $day => $daySchedule) {
            if ($daySchedule->hasCrossDayPeriods()) {
                $nextDay = $this->getNextDay($day);
                if ($nextDay) {
                    $nextDaySchedule = $this->getDaySchedule($nextDay);
                    if (!$nextDaySchedule->enabled) {
                        $issues[] = "Day " . ucfirst($day) . " has periods extending into " . ucfirst($nextDay) . ", but " . ucfirst($nextDay) . " is disabled. This might lead to missed attendance records.";
                    }
                }
            }
        }

        return $issues;
    }

    private function getNextDay(string $day): ?string
    {
        $dayIndex = array_search($day, self::VALID_DAYS);
        if ($dayIndex === false) {
            return null;
        }

        $nextIndex = ($dayIndex + 1) % count(self::VALID_DAYS);
        return self::VALID_DAYS[$nextIndex];
    }

    public function __toString(): string
    {
        $lines = [];
        $lines[] = "Weekly Schedule ({$this->getEnabledDayCount()}/{$this->getDayCount()} days enabled):";

        foreach (self::VALID_DAYS as $day) {
            $daySchedule = $this->getDaySchedule($day);
            $lines[] = "  " . ucfirst($day) . ": " . $daySchedule->__toString();
        }

        $lines[] = "Total periods: {$this->getTotalPeriodCount()}";
        $lines[] = "Weekly work hours: " . number_format($this->getTotalWeeklyWorkHours(), 1);

        return implode("\n", $lines);
    }
}
