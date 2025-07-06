<?php

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;

/**
 * Data class representing a day's schedule with multiple periods
 */
class DaySchedule
{
    /** @var TimePeriod[] */
    public readonly array $periods;

    public function __construct(
        public readonly bool $enabled,
        array $periods = []
    ) {
        $this->periods = $this->validateAndSetPeriods($periods);
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $enabled = $data['enabled'] ?? false;
        $periodsData = $data['periods'] ?? [];

        $periods = [];
        foreach ($periodsData as $periodData) {
            $periods[] = TimePeriod::fromArray($periodData);
        }

        return new self($enabled, $periods);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'periods' => array_map(fn(TimePeriod $period) => $period->toArray(), $this->periods),
        ];
    }

    /**
     * Validate and set periods
     */
    private function validateAndSetPeriods(array $periods): array
    {
        // If day is disabled, periods should be empty
        if (!$this->enabled) {
            if (!empty($periods)) {
                throw new InvalidArgumentException('Disabled days cannot have periods');
            }
            return [];
        }

        // If day is enabled, must have at least one period
        if (empty($periods)) {
            throw new InvalidArgumentException('Enabled days must have at least one period');
        }

        // Validate each period is a TimePeriod instance
        $validatedPeriods = [];
        foreach ($periods as $period) {
            if (!$period instanceof TimePeriod) {
                throw new InvalidArgumentException('All periods must be TimePeriod instances');
            }
            $validatedPeriods[] = $period;
        }

        // Check for duplicate period names
        $periodNames = array_map(fn(TimePeriod $p) => $p->name, $validatedPeriods);
        if (count($periodNames) !== count(array_unique($periodNames))) {
            throw new InvalidArgumentException('Period names must be unique within a day');
        }

        // Check for overlapping periods (for same-day periods only)
        $this->validateNoOverlaps($validatedPeriods);

        return $validatedPeriods;
    }

    /**
     * Validate that periods don't overlap
     */
    private function validateNoOverlaps(array $periods): void
    {
        $sameDayPeriods = array_filter($periods, fn(TimePeriod $p) => !$p->spansNextDay);
        
        for ($i = 0; $i < count($sameDayPeriods); $i++) {
            for ($j = $i + 1; $j < count($sameDayPeriods); $j++) {
                if ($sameDayPeriods[$i]->overlapsWith($sameDayPeriods[$j])) {
                    throw new InvalidArgumentException(
                        "Periods '{$sameDayPeriods[$i]->name}' and '{$sameDayPeriods[$j]->name}' overlap"
                    );
                }
            }
        }
    }

    /**
     * Add a period to this day schedule
     */
    public function addPeriod(TimePeriod $period): self
    {
        if (!$this->enabled) {
            throw new InvalidArgumentException('Cannot add periods to disabled days');
        }

        $newPeriods = [...$this->periods, $period];
        return new self($this->enabled, $newPeriods);
    }

    /**
     * Remove a period by name
     */
    public function removePeriod(string $periodName): self
    {
        $newPeriods = array_filter(
            $this->periods,
            fn(TimePeriod $p) => $p->name !== $periodName
        );

        return new self($this->enabled, array_values($newPeriods));
    }

    /**
     * Get period by name
     */
    public function getPeriod(string $name): ?TimePeriod
    {
        foreach ($this->periods as $period) {
            if ($period->name === $name) {
                return $period;
            }
        }
        return null;
    }

    /**
     * Get all period names
     */
    public function getPeriodNames(): array
    {
        return array_map(fn(TimePeriod $p) => $p->name, $this->periods);
    }

    /**
     * Get total number of periods
     */
    public function getPeriodCount(): int
    {
        return count($this->periods);
    }

    /**
     * Check if day has any cross-day periods
     */
    public function hasCrossDayPeriods(): bool
    {
        foreach ($this->periods as $period) {
            if ($period->spansNextDay) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get total work time in minutes (excluding cross-day periods)
     */
    public function getTotalWorkMinutes(): int
    {
        $total = 0;
        foreach ($this->periods as $period) {
            $duration = $period->getDurationMinutes();
            if ($duration !== null) {
                $total += $duration;
            }
        }
        return $total;
    }

    /**
     * Create a disabled day schedule
     */
    public static function disabled(): self
    {
        return new self(false, []);
    }

    /**
     * Create an enabled day schedule with periods
     */
    public static function enabled(TimePeriod ...$periods): self
    {
        return new self(true, $periods);
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        if (!$this->enabled) {
            return 'Disabled';
        }

        if (empty($this->periods)) {
            return 'Enabled (no periods)';
        }

        $periodStrings = array_map(fn(TimePeriod $p) => $p->__toString(), $this->periods);
        return 'Enabled: ' . implode(', ', $periodStrings);
    }
}
