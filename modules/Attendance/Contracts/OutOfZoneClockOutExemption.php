<?php

declare(strict_types=1);

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

interface OutOfZoneClockOutExemption
{
    public function appliesTo(Attendance $attendance): bool;
}
