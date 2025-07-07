<?php

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;

/**
 * Data class representing a single time period within a day
 */
class TimePeriod
{
    public function __construct(
        public readonly string $name,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly bool $spansNextDay = false,
        public readonly int $gracePeriodBefore = 0,
        public readonly int $gracePeriodAfter = 0
    ) {
        $this->validate();
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            startTime: $data['start_time'] ?? '',
            endTime: $data['end_time'] ?? '',
            spansNextDay: $data['spans_next_day'] ?? false,
            gracePeriodBefore: $data['grace_period_before'] ?? 0,
            gracePeriodAfter: $data['grace_period_after'] ?? 0
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'spans_next_day' => $this->spansNextDay,
            'grace_period_before' => $this->gracePeriodBefore,
            'grace_period_after' => $this->gracePeriodAfter,
        ];
    }

    /**
     * Validate the time period data
     */
    private function validate(): void
    {
        // Validate name
        if (empty(trim($this->name))) {
            throw new InvalidArgumentException('Period name cannot be empty');
        }

        if (strlen($this->name) > 100) {
            throw new InvalidArgumentException('Period name cannot exceed 100 characters');
        }

        // Validate time format
        if (!$this->isValidTimeFormat($this->startTime)) {
            throw new InvalidArgumentException("Invalid start time format: {$this->startTime}. Expected HH:MM");
        }

        if (!$this->isValidTimeFormat($this->endTime)) {
            throw new InvalidArgumentException("Invalid end time format: {$this->endTime}. Expected HH:MM");
        }

        // Validate grace periods
        if ($this->gracePeriodBefore < 0) {
            throw new InvalidArgumentException('Grace period before cannot be negative');
        }

        if ($this->gracePeriodAfter < 0) {
            throw new InvalidArgumentException('Grace period after cannot be negative');
        }

        if ($this->gracePeriodBefore > 1440) { // 24 hours in minutes
            throw new InvalidArgumentException('Grace period before cannot exceed 24 hours (1440 minutes)');
        }

        if ($this->gracePeriodAfter > 1440) {
            throw new InvalidArgumentException('Grace period after cannot exceed 24 hours (1440 minutes)');
        }

        // Validate logical time ordering for same-day periods
        if (!$this->spansNextDay) {
            $startMinutes = $this->timeToMinutes($this->startTime);
            $endMinutes = $this->timeToMinutes($this->endTime);
            
            if ($endMinutes <= $startMinutes) {
                throw new InvalidArgumentException(
                    "End time ({$this->endTime}) must be after start time ({$this->startTime}) for same-day periods"
                );
            }
        }
    }

    /**
     * Check if time format is valid (HH:MM)
     */
    private function isValidTimeFormat(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }

    /**
     * Convert time string to minutes since midnight
     */
    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * Get duration in minutes (for same-day periods only)
     */
    public function getDurationMinutes(): ?int
    {
        if ($this->spansNextDay) {
            return null; // Cannot calculate duration for cross-day periods
        }

        return $this->timeToMinutes($this->endTime) - $this->timeToMinutes($this->startTime);
    }

    /**
     * Check if this period overlaps with another period
     */
    public function overlapsWith(TimePeriod $other): bool
    {
        // If either period spans next day, overlap detection is complex
        if ($this->spansNextDay || $other->spansNextDay) {
            return false; // Skip overlap check for cross-day periods for now
        }

        $thisStart = $this->timeToMinutes($this->startTime);
        $thisEnd = $this->timeToMinutes($this->endTime);
        $otherStart = $other->timeToMinutes($other->startTime);
        $otherEnd = $other->timeToMinutes($other->endTime);

        return !($thisEnd <= $otherStart || $otherEnd <= $thisStart);
    }

    /**
     * Get effective start time including grace period
     */
    public function getEffectiveStartTime(): string
    {
        $startMinutes = $this->timeToMinutes($this->startTime) - $this->gracePeriodBefore;
        
        // Handle negative minutes (previous day)
        if ($startMinutes < 0) {
            $startMinutes += 1440; // Add 24 hours
        }

        return $this->minutesToTime($startMinutes);
    }

    /**
     * Get effective end time including grace period
     */
    public function getEffectiveEndTime(): string
    {
        $endMinutes = $this->timeToMinutes($this->endTime) + $this->gracePeriodAfter;
        
        // Handle overflow (next day)
        if ($endMinutes >= 1440) {
            $endMinutes -= 1440; // Subtract 24 hours
        }

        return $this->minutesToTime($endMinutes);
    }

    /**
     * Convert minutes since midnight to time string
     */
    private function minutesToTime(int $minutes): string
    {
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        $spanText = $this->spansNextDay ? ' (spans next day)' : '';
        $graceText = '';
        
        if ($this->gracePeriodBefore > 0 || $this->gracePeriodAfter > 0) {
            $graceText = " [Grace: -{$this->gracePeriodBefore}m, +{$this->gracePeriodAfter}m]";
        }

        return "{$this->name}: {$this->startTime}-{$this->endTime}{$spanText}{$graceText}";
    }
}
