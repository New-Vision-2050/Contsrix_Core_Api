<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;
use JsonSerializable;

class TimePeriod implements JsonSerializable
{
    public function __construct(
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly bool $extends_to_next_day,
        public readonly int $gracePeriodBefore = 0,
        public readonly int $gracePeriodAfter = 0
    ) {
        $this->validateTimes();
    }

    private function validateTimes(): void
    {
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $this->startTime)) {
            throw new InvalidArgumentException("Invalid start_time format: {$this->startTime}. Expected HH:MM.");
        }
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $this->endTime)) {
            throw new InvalidArgumentException("Invalid end_time format: {$this->endTime}. Expected HH:MM.");
        }

        if (!$this->extends_to_next_day && $this->timeToMinutes($this->endTime) <= $this->timeToMinutes($this->startTime)) {
            throw new InvalidArgumentException("For non-spanning periods, end_time ({$this->endTime}) must be strictly after start_time ({$this->startTime}).");
        }
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['startTime'], $data['endTime'], $data['extends_to_next_day'])) {
            throw new InvalidArgumentException('TimePeriod data missing required fields.');
        }
        return new self(
            $data['startTime'],
            $data['endTime'],
            (bool)($data['extends_to_next_day']),
            $data['gracePeriodBefore'] ?? 0,
            $data['gracePeriodAfter'] ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'extends_to_next_day' => $this->extends_to_next_day,
            'gracePeriodBefore' => $this->gracePeriodBefore,
            'gracePeriodAfter' => $this->gracePeriodAfter,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function timeToMinutes(string $time): int
    {
        list($hours, $minutes) = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    public function getDurationMinutes(): int
    {
        $start = $this->timeToMinutes($this->startTime);
        $end = $this->timeToMinutes($this->endTime);
        if ($this->extends_to_next_day || $end < $start) { // If it spans next day or ends before it starts
            return (24 * 60 - $start) + $end;
        }
        return $end - $start;
    }

    public function __toString(): string
    {
        return "{$this->startTime}-{$this->endTime}" . ($this->extends_to_next_day ? ' (cross-day)' : '');
    }
}
