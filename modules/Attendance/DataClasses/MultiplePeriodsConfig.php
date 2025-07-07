<?php

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Main data class for Multiple Periods constraint configuration
 * This is the top-level configuration class that contains the weekly schedule
 */
class MultiplePeriodsConfig implements JsonSerializable
{
    public function __construct(
        public readonly WeeklySchedule $weeklySchedule,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {
        $this->validate();
    }

    /**
     * Create from array data (typically from JSON)
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['weekly_schedule'])) {
            throw new InvalidArgumentException('Missing required field: weekly_schedule');
        }

        return new self(
            weeklySchedule: WeeklySchedule::fromArray($data['weekly_schedule']),
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? null
        );
    }

    /**
     * Create from JSON string
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return self::fromArray($data);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $result = [
            'weekly_schedule' => $this->weeklySchedule->toArray(),
        ];

        if ($this->description !== null) {
            $result['description'] = $this->description;
        }

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $flags);
    }

    /**
     * JsonSerializable implementation
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate the configuration
     */
    private function validate(): void
    {
        // Validate description length if provided
        if ($this->description !== null && strlen($this->description) > 500) {
            throw new InvalidArgumentException('Description cannot exceed 500 characters');
        }

        // Validate weekly schedule
        $validationIssues = $this->weeklySchedule->validate();
        if (!empty($validationIssues)) {
            throw new InvalidArgumentException('Weekly schedule validation failed: ' . implode(', ', $validationIssues));
        }

        // Validate metadata if provided
        if ($this->metadata !== null) {
            if (!is_array($this->metadata)) {
                throw new InvalidArgumentException('Metadata must be an array');
            }

            // Check metadata size (prevent excessive data)
            $metadataJson = json_encode($this->metadata);
            if (strlen($metadataJson) > 10000) { // 10KB limit
                throw new InvalidArgumentException('Metadata size cannot exceed 10KB');
            }
        }
    }

    /**
     * Get schedule for a specific day
     */
    public function getDaySchedule(string $day): ?DaySchedule
    {
        return $this->weeklySchedule->getDaySchedule($day);
    }

    /**
     * Check if a day is enabled
     */
    public function isDayEnabled(string $day): bool
    {
        return $this->weeklySchedule->isDayEnabled($day);
    }

    /**
     * Get all enabled days
     */
    public function getEnabledDays(): array
    {
        return $this->weeklySchedule->getEnabledDays();
    }

    /**
     * Get total number of periods across all days
     */
    public function getTotalPeriodCount(): int
    {
        return $this->weeklySchedule->getTotalPeriodCount();
    }

    /**
     * Check if configuration has any cross-day periods
     */
    public function hasCrossDayPeriods(): bool
    {
        return $this->weeklySchedule->hasCrossDayPeriods();
    }

    /**
     * Get total weekly work hours
     */
    public function getTotalWeeklyWorkHours(): float
    {
        return $this->weeklySchedule->getTotalWeeklyWorkHours();
    }

    /**
     * Create a copy with updated description
     */
    public function withDescription(string $description): self
    {
        return new self($this->weeklySchedule, $description, $this->metadata);
    }

    /**
     * Create a copy with updated metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self($this->weeklySchedule, $this->description, $metadata);
    }

    /**
     * Create a copy with updated weekly schedule
     */
    public function withWeeklySchedule(WeeklySchedule $weeklySchedule): self
    {
        return new self($weeklySchedule, $this->description, $this->metadata);
    }

    /**
     * Factory method: Create standard office hours configuration
     */
    public static function standardOfficeHours(
        string $startTime = '09:00',
        string $endTime = '17:00',
        int $gracePeriod = 15
    ): self {
        $workPeriod = new TimePeriod(
            name: 'Standard Office Hours',
            startTime: $startTime,
            endTime: $endTime,
            spansNextDay: false,
            gracePeriodBefore: $gracePeriod,
            gracePeriodAfter: $gracePeriod
        );

        $weeklySchedule = WeeklySchedule::standardWorkWeek($workPeriod);

        return new self(
            weeklySchedule: $weeklySchedule,
            description: 'Standard 5-day office hours with weekend off'
        );
    }

    /**
     * Factory method: Create restaurant service hours
     */
    public static function restaurantServiceHours(
        array $serviceDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
    ): self {
        $lunchPeriod = new TimePeriod(
            name: 'Lunch Service',
            startTime: '11:00',
            endTime: '15:00',
            spansNextDay: false,
            gracePeriodBefore: 30,
            gracePeriodAfter: 30
        );

        $dinnerPeriod = new TimePeriod(
            name: 'Dinner Service',
            startTime: '17:00',
            endTime: '22:00',
            spansNextDay: false,
            gracePeriodBefore: 30,
            gracePeriodAfter: 30
        );

        $schedule = [];
        foreach ($serviceDays as $day) {
            $schedule[$day] = DaySchedule::enabled($lunchPeriod, $dinnerPeriod);
        }

        // Add closed day (typically Sunday)
        if (!in_array('sunday', $serviceDays)) {
            $schedule['sunday'] = DaySchedule::disabled();
        }

        return new self(
            weeklySchedule: new WeeklySchedule($schedule),
            description: 'Restaurant lunch and dinner service periods'
        );
    }

    /**
     * Factory method: Create 24/7 security shifts
     */
    public static function securityShifts(): self
    {
        $dayShift = new TimePeriod(
            name: 'Day Shift',
            startTime: '06:00',
            endTime: '18:00',
            spansNextDay: false,
            gracePeriodBefore: 15,
            gracePeriodAfter: 15
        );

        $nightShift = new TimePeriod(
            name: 'Night Shift',
            startTime: '18:00',
            endTime: '06:00',
            spansNextDay: true,
            gracePeriodBefore: 15,
            gracePeriodAfter: 15
        );

        $weeklySchedule = WeeklySchedule::twentyFourSeven($dayShift, $nightShift);

        return new self(
            weeklySchedule: $weeklySchedule,
            description: '24/7 security coverage with day and night shifts'
        );
    }

    /**
     * Factory method: Create flexible office hours
     */
    public static function flexibleOfficeHours(): self
    {
        $earlyBird = new TimePeriod(
            name: 'Early Bird',
            startTime: '07:00',
            endTime: '15:00',
            spansNextDay: false,
            gracePeriodBefore: 30,
            gracePeriodAfter: 30
        );

        $standard = new TimePeriod(
            name: 'Standard Hours',
            startTime: '09:00',
            endTime: '17:00',
            spansNextDay: false,
            gracePeriodBefore: 30,
            gracePeriodAfter: 30
        );

        $lateStart = new TimePeriod(
            name: 'Late Start',
            startTime: '11:00',
            endTime: '19:00',
            spansNextDay: false,
            gracePeriodBefore: 30,
            gracePeriodAfter: 30
        );

        $workDays = ['monday', 'tuesday', 'wednesday', 'thursday'];
        $schedule = [];

        // Use different periods on different days to avoid overlaps
        foreach ($workDays as $index => $day) {
            switch ($index % 3) {
                case 0:
                    $schedule[$day] = DaySchedule::enabled($earlyBird);
                    break;
                case 1:
                    $schedule[$day] = DaySchedule::enabled($standard);
                    break;
                case 2:
                    $schedule[$day] = DaySchedule::enabled($lateStart);
                    break;
            }
        }

        // Friday gets all options as separate shifts
        $fridayEarly = new TimePeriod('Friday Early', '07:00', '11:00', false, 15, 15);
        $fridayLate = new TimePeriod('Friday Late', '13:00', '17:00', false, 15, 15);
        $schedule['friday'] = DaySchedule::enabled($fridayEarly, $fridayLate);

        $schedule['saturday'] = DaySchedule::disabled();
        $schedule['sunday'] = DaySchedule::disabled();

        return new self(
            weeklySchedule: new WeeklySchedule($schedule),
            description: 'Flexible office hours with different options per day'
        );
    }

    /**
     * Get configuration summary
     */
    public function getSummary(): array
    {
        return [
            'description' => $this->description,
            'enabled_days' => $this->getEnabledDays(),
            'total_periods' => $this->getTotalPeriodCount(),
            'weekly_work_hours' => $this->getTotalWeeklyWorkHours(),
            'has_cross_day_periods' => $this->hasCrossDayPeriods(),
            'all_periods' => $this->weeklySchedule->getAllPeriodNames(),
        ];
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        $lines = [];
        
        if ($this->description) {
            $lines[] = "Description: {$this->description}";
        }
        
        $lines[] = $this->weeklySchedule->__toString();
        
        if ($this->hasCrossDayPeriods()) {
            $lines[] = "⚠️  Contains cross-day periods";
        }

        return implode("\n", $lines);
    }
}
