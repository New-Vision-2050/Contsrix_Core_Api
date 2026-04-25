<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Carbon\CarbonImmutable;

final class FixedClock implements Clock
{
    public function __construct(private readonly CarbonImmutable $instant) {}

    public function now(): CarbonImmutable
    {
        return $this->instant;
    }
}
