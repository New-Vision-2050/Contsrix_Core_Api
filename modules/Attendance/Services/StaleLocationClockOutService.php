<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\StaleLocationClockOut;

final class StaleLocationClockOutService
{
    public function __construct(
        private readonly AutoCloseAttendanceService $autoClose,
    ) {}

    /**
     * Close an open shift when mobile has not sent GPS for 45 minutes.
     *
     * @return bool true when this call closed the row
     */
    public function closeIfStale(Attendance $attendance): bool
    {
        if (! StaleLocationClockOut::isStale($attendance)) {
            return false;
        }

        $closeAt = StaleLocationClockOut::closeAt($attendance);
        if ($closeAt === null) {
            return false;
        }

        return $this->autoClose->closeIfExpired(
            $attendance,
            $closeAt,
            StaleLocationClockOut::METHOD,
        );
    }
}
