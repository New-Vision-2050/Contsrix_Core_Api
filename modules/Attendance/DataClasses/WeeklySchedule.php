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

        return new self($schedule);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->schedule as $day => $daySchedule) {
            $result[$day] = $daySchedule->toArray();
        }
        return $result;
    }

    /**
     * Validate and set schedule
     */
    private function validateAndSetSchedule(array $schedule): array
    {
        $validatedSchedule = [];

        foreach ($schedule as $day => $daySchedule) {
            $normalizedDay = strtolower(trim($day));
            
            // Validate day name
            if (!in_array($normalizedDay, self::VALID_DAYS)) {
                throw new InvalidArgumentException("Invalid day: {$day}. Must be one of: " . implode(', ', self::VALID_DAYS));
            }

            // Validate day schedule
            if (!$daySchedule instanceof DaySchedule) {
                throw new InvalidArgumentException("Day schedule for {$day} must be a DaySchedule instance");
            }

            $validatedSchedule[$normalizedDay] = $daySchedule;
        }

        // Ensure at least one day is configured
        if (empty($validatedSchedule)) {
            throw new InvalidArgumentException('Weekly schedule must have at least one day configured');
        }

        return $validatedSchedule;
    }

    /**
     * Get schedule for a specific day
     */
    public function getDaySchedule(string $day): ?DaySchedule
    {
        $normalizedDay = strtolower(trim($day));
        return $this->schedule[$normalizedDay] ?? null;
    }

    /**
     * Set schedule for a specific day
     */
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

    /**
     * Remove a day from the schedule
     */
    public function removeDaySchedule(string $day): self
    {
        $normalizedDay = strtolower(trim($day));
        $newSchedule = $this->schedule;
        unset($newSchedule[$normalizedDay]);

        if (empty($newSchedule)) {
            throw new InvalidArgumentException('Cannot remove the last day from the schedule');
        }

        return new self($newSchedule);
    }

    /**
     * Get all configured days
     */
    public function getConfiguredDays(): array
    {
        return array_keys($this->schedule);
    }

    /**
     * Get all enabled days
     */
    public function getEnabledDays(): array
    {
        return array_keys(array_filter(
            $this->schedule,
            fn(DaySchedule $daySchedule) => $daySchedule->enabled
        ));
    }

    /**
     * Get all disabled days
     */
    public function getDisabledDays(): array
    {
        return array_keys(array_filter(
            $this->schedule,
            fn(DaySchedule $daySchedule) => !$daySchedule->enabled
        ));
    }

    /**
     * Check if a day is configured
     */
    public function hasDaySchedule(string $day): bool
    {
        $normalizedDay = strtolower(trim($day));
        return isset($this->schedule[$normalizedDay]);
    }

    /**
     * Check if a day is enabled
     */
    public function isDayEnabled(string $day): bool
    {
        $daySchedule = $this->getDaySchedule($day);
        return $daySchedule !== null && $daySchedule->enabled;
    }

    /**
     * Get total number of configured days
     */
    public function getDayCount(): int
    {
        return count($this->schedule);
    }

    /**
     * Get total number of enabled days
     */
    public function getEnabledDayCount(): int
    {
        return count($this->getEnabledDays());
    }

    /**
     * Get total number of periods across all days
     */
    public function getTotalPeriodCount(): int
    {
        $total = 0;
        foreach ($this->schedule as $daySchedule) {
            $total += $daySchedule->getPeriodCount();
        }
        return $total;
    }

    /**
     * Get all period names across all days (with day prefix)
     */
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

    /**
     * Check if schedule has any cross-day periods
     */
    public function hasCrossDayPeriods(): bool
    {
        foreach ($this->schedule as $daySchedule) {
            if ($daySchedule->hasCrossDayPeriods()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get total work hours per week (excluding cross-day periods)
     */
    public function getTotalWeeklyWorkMinutes(): int
    {
        $total = 0;
        foreach ($this->schedule as $daySchedule) {
            $total += $daySchedule->getTotalWorkMinutes();
        }
        return $total;
    }

    /**
     * Get total work hours per week in hours format
     */
    public function getTotalWeeklyWorkHours(): float
    {
        return $this->getTotalWeeklyWorkMinutes() / 60;
    }

    /**
     * Create a standard 5-day work week (Monday-Friday)
     */
    public static function standardWorkWeek(TimePeriod $workPeriod): self
    {
        $workDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $schedule = [];

        foreach ($workDays as $day) {
            $schedule[$day] = DaySchedule::enabled($workPeriod);
        }

        // Add disabled weekend
        $schedule['saturday'] = DaySchedule::disabled();
        $schedule['sunday'] = DaySchedule::disabled();

        return new self($schedule);
    }

    /**
     * Create a 24/7 schedule with day and night shifts
     */
    public static function twentyFourSeven(TimePeriod $dayShift, TimePeriod $nightShift): self
    {
        $schedule = [];
        
        foreach (self::VALID_DAYS as $day) {
            $schedule[$day] = DaySchedule::enabled($dayShift, $nightShift);
        }

        return new self($schedule);
    }

    /**
     * Validate the entire schedule for consistency
     */
    public function validate(): array
    {
        $issues = [];

        // Check for at least one enabled day
        if ($this->getEnabledDayCount() === 0) {
            $issues[] = 'Schedule must have at least one enabled day';
        }

        // Check for reasonable work hours (if applicable)
        $weeklyHours = $this->getTotalWeeklyWorkHours();
        if ($weeklyHours > 168) { // More than 24*7 hours
            $issues[] = "Weekly work hours ({$weeklyHours}) exceed total hours in a week";
        }

        // Validate cross-day period consistency
        foreach ($this->schedule as $day => $daySchedule) {
            if ($daySchedule->hasCrossDayPeriods()) {
                $nextDay = $this->getNextDay($day);
                if ($nextDay && $this->hasDaySchedule($nextDay)) {
                    $nextDaySchedule = $this->getDaySchedule($nextDay);
                    if (!$nextDaySchedule->enabled) {
                        $issues[] = "Day {$day} has cross-day periods but {$nextDay} is disabled";
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Get the next day in the week
     */
    private function getNextDay(string $day): ?string
    {
        $dayIndex = array_search($day, self::VALID_DAYS);
        if ($dayIndex === false) {
            return null;
        }

        $nextIndex = ($dayIndex + 1) % count(self::VALID_DAYS);
        return self::VALID_DAYS[$nextIndex];
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        $lines = [];
        $lines[] = "Weekly Schedule ({$this->getEnabledDayCount()}/{$this->getDayCount()} days enabled):";
        
        foreach (self::VALID_DAYS as $day) {
            if ($this->hasDaySchedule($day)) {
                $daySchedule = $this->getDaySchedule($day);
                $lines[] = "  " . ucfirst($day) . ": " . $daySchedule->__toString();
            }
        }

        $lines[] = "Total periods: {$this->getTotalPeriodCount()}";
        $lines[] = "Weekly work hours: " . number_format($this->getTotalWeeklyWorkHours(), 1);

        return implode("\n", $lines);
    }
}
