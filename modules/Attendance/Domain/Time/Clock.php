<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Carbon\CarbonImmutable;

interface Clock
{
    public function now(): CarbonImmutable;
}
