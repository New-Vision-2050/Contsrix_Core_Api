<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;

class Period
{
    public string $start_time;
    public string $end_time;

    public function __construct(string $startTime, string $endTime)
    {
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime)) {
            throw new InvalidArgumentException("Invalid start_time format: {$startTime}. Expected HH:MM.");
        }
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) {
            throw new InvalidArgumentException("Invalid end_time format: {$endTime}. Expected HH:MM.");
        }

        $this->start_time = $startTime;
        $this->end_time = $endTime;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['start_time']) || !isset($data['end_time'])) {
            throw new InvalidArgumentException("Period data must contain 'start_time' and 'end_time'.");
        }
        return new self($data['start_time'], $data['end_time']);
    }

    public function toArray(): array
    {
        return [
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}