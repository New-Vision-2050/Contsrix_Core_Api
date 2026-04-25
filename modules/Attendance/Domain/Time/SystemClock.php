<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Carbon\CarbonImmutable;

final class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
