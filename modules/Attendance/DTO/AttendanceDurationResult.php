<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

/**
 * Immutable result of simple attendance duration rules (all durations in whole minutes).
 */
final readonly class AttendanceDurationResult
{
    public function __construct(
        public int $workMinutes,
        public int $delayMinutes,
        public int $overtimeMinutes,
        public bool $isEarlyDeparture = false,
        public int $earlyDepartureMinutes = 0,
    ) {
    }
}
