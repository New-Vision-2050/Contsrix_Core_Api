<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;

/**
 * Data class representing the schedule for a single day
 */
class DaySchedule
{
    public readonly bool $enabled;
    /** @var TimePeriod[] */
    public readonly array $periods;
    public readonly array $early_clock_in_rules;
    public readonly array $lateness_rules;
    public readonly float $total_work_hours;

    /**
     * @param TimePeriod[] $periods
     */
    public function __construct(
        bool $enabled,
        array $periods,
        array $earlyClockInRules = [],
        array $latenessRules = []
    ) {
        $this->enabled = $enabled;
        $this->periods = $this->validateAndSetPeriods($periods);
        $this->early_clock_in_rules = $earlyClockInRules;
        $this->lateness_rules = $latenessRules;
        $this->total_work_hours = $this->calculateTotalWorkHours();
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $periods = array_map(
            function(array $p_data) {
                if (!isset($p_data['start_time'], $p_data['end_time'])) {
                    throw new InvalidArgumentException("Period data must contain 'start_time' and 'end_time'.");
                }
                return TimePeriod::fromArray([
                    'startTime' => $p_data['start_time'],
                    'endTime' => $p_data['end_time'],
                    'extends_to_next_day' => (bool)($p_data['extends_to_next_day'] ?? false), // Ensure boolean conversion
                    'gracePeriodBefore' => $p_data['grace_period_before'] ?? 0,
                    'gracePeriodAfter' => $p_data['grace_period_after'] ?? 0,
                ]);
            },
            $data['periods'] ?? []
        );

        return new self(
            $data['enabled'] ?? false,
            $periods,
            $data['early_clock_in_rules'] ?? [],
            $data['lateness_rules'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'periods' => array_map(fn($p) => $p->toArray(), $this->periods),
            'early_clock_in_rules' => $this->early_clock_in_rules,
            'lateness_rules' => $this->lateness_rules,
            'total_work_hours' => $this->total_work_hours,
        ];
    }

    private function validateAndSetPeriods(array $periods): array
    {
        $processedPeriods = [];
        foreach ($periods as $period) {
            if (!$period instanceof TimePeriod) {
                throw new InvalidArgumentException('Each period must be a TimePeriod instance.');
            }
            $startMinutes = $period->timeToMinutes($period->startTime);
            $endMinutes = $period->timeToMinutes($period->endTime);

            $adjustedEndMinutes = $endMinutes;
            if ($period->extends_to_next_day) {
                 $adjustedEndMinutes += 24 * 60;
            } elseif ($endMinutes <= $startMinutes) {
                throw new InvalidArgumentException("Period '{$period->startTime}-{$period->endTime}' is invalid. End time must be after start time or marked as extends_to_next_day.");
            }

            if ($adjustedEndMinutes <= $startMinutes) {
                 throw new InvalidArgumentException("Period '{$period->startTime}-{$period->endTime}' has zero or negative duration.");
            }

            $processedPeriods[] = [
                'start' => $startMinutes,
                'end' => $adjustedEndMinutes,
                'original_period' => $period,
            ];
        }

        usort($processedPeriods, fn($a, $b) => $a['start'] <=> $b['start']);

        for ($i = 0; $i < count($processedPeriods) - 1; $i++) {
            $current = $processedPeriods[$i];
            $next = $processedPeriods[$i + 1];

            if ($current['end'] > $next['start']) {
                throw new InvalidArgumentException(
                    "Periods overlap within this day: '{$current['original_period']->startTime}-{$current['original_period']->endTime}' overlaps with '{$next['original_period']->startTime}-{$next['original_period']->endTime}'."
                );
            }
        }
        return $periods;
    }

    public static function enabled(TimePeriod ...$periods): self
    {
        return new self(true, $periods);
    }

    public static function disabled(): self
    {
        return new self(false, []);
    }

    public function getPeriodCount(): int
    {
        return count($this->periods);
    }

    public function getPeriods(): array
    {
        return $this->periods;
    }

    public function getPeriodNames(): array
    {
        return array_map(fn(TimePeriod $p) => (string)$p, $this->periods);
    }

    public function hasCrossDayPeriods(): bool
    {
        foreach ($this->periods as $period) {
            if ($period->extends_to_next_day) {
                return true;
            }
        }
        return false;
    }

    public function getTotalWorkMinutes(): int
    {
        $totalMinutes = 0;
        foreach ($this->periods as $period) {
            $totalMinutes += $period->getDurationMinutes();
        }
        return $totalMinutes;
    }

    private function calculateTotalWorkHours(): float
    {
        return $this->getTotalWorkMinutes() / 60;
    }

    public function validate(string $dayName, array $previousDaySpilloverPeriods = []): array
    {
        $errors = [];
        $processedPeriods = [];

        foreach ($previousDaySpilloverPeriods as $spillover) {
            $processedPeriods[] = [
                'start' => $spillover['start_minutes'],
                'end' => $spillover['end_minutes'],
                'original_context' => [
                    'type' => 'spillover',
                    'original_day' => $spillover['original_day'],
                    'original_period_start' => $spillover['original_period_start'],
                    'original_period_end' => $spillover['original_period_end']
                ]
            ];
        }

        foreach ($this->periods as $period) {
            $startMinutes = $period->timeToMinutes($period->startTime);
            $endMinutes = $period->timeToMinutes($period->endTime);

            $adjustedEndMinutes = $endMinutes;
            if ($period->extends_to_next_day) {
                 $adjustedEndMinutes += 24 * 60;
            }

            if ($adjustedEndMinutes <= $startMinutes) {
                $errors[] = "Period '{$period->startTime}-{$period->endTime}' for {$dayName} has an invalid duration (start time is not before end time).";
                continue;
            }

            $processedPeriods[] = [
                'start' => $startMinutes,
                'end' => $adjustedEndMinutes,
                'original_context' => [
                    'type' => 'current_day',
                    'day_name' => $dayName,
                    'original_period_start' => $period->startTime,
                    'original_period_end' => $period->endTime
                ]
            ];
        }

        if (empty($processedPeriods)) {
            return $errors;
        }

        usort($processedPeriods, fn($a, $b) => $a['start'] <=> $b['start']);

        for ($i = 0; $i < count($processedPeriods) - 1; $i++) {
            $currentPeriod = $processedPeriods[$i];
            $nextPeriod = $processedPeriods[$i + 1];

            if ($currentPeriod['end'] > $nextPeriod['start']) {
                $isPreviousDaySpillover = ($currentPeriod['original_context']['type'] === 'spillover' && $currentPeriod['start'] === 0);
                $isCurrentDayStartsAtMidnight = ($nextPeriod['original_context']['type'] === 'current_day' && $nextPeriod['start'] === 0);

                if ($isPreviousDaySpillover && $isCurrentDayStartsAtMidnight) {
                    if ($nextPeriod['end'] <= $currentPeriod['end']) {
                        continue;
                    }
                }

                $errorMsg = "Periods overlap for {$dayName}. ";

                if ($currentPeriod['original_context']['type'] === 'spillover') {
                    $errorMsg .= "A period from {$currentPeriod['original_context']['original_day']} ('{$currentPeriod['original_context']['original_period_start']}-{$currentPeriod['original_context']['original_period_end']}') extending into {$dayName} ";
                } else {
                    $errorMsg .= "Period '{$currentPeriod['original_context']['original_period_start']}-{$currentPeriod['original_period']->endTime}' "; // original_period->endTime
                }

                if ($nextPeriod['original_context']['type'] === 'spillover') {
                    $errorMsg .= "overlaps with a period from {$nextPeriod['original_context']['original_day']} ('{$nextPeriod['original_context']['original_period_start']}-{$nextPeriod['original_context']['original_period_end']}') extending into {$dayName}.";
                } else {
                    $errorMsg .= "overlaps with '{$nextPeriod['original_context']['original_period_start']}-{$nextPeriod['original_context']['original_period_end']}'.";
                }
                $errors[] = $errorMsg;
            }
        }
        return $errors;
    }

    public function getPeriodsCrossingToNextDay(string $dayName): array
    {
        $spillovers = [];
        if (!$this->enabled) {
            return $spillovers;
        }

        foreach ($this->periods as $period) {
            if ($period->extends_to_next_day) {
                $spillovers[] = [
                    'start_minutes' => 0,
                    'end_minutes' => $period->timeToMinutes($period->endTime),
                    'original_day' => $dayName,
                    'original_period_start' => $period->startTime,
                    'original_period_end' => $period->endTime,
                ];
            }
        }
        return $spillovers;
    }

    public function __toString(): string
    {
        $status = $this->enabled ? 'Enabled' : 'Disabled';
        $periodStrings = array_map(fn($p) => (string)$p, $this->periods);
        $totalHours = number_format($this->total_work_hours, 1);
        return "{$status} ({$totalHours} hours): " . implode(', ', $periodStrings);
    }
}
