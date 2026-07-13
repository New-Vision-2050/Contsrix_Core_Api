<?php

declare(strict_types=1);

namespace Modules\Attendance\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoCloseAttendanceService;

/**
 * Fast-path auto clock-out for a shift that has reached its close moment.
 *
 * Dispatched with a future delay at clock-in time (earliest possible close moment =
 * clock_in + max_working_hours) so the shift is closed promptly. Because breaks taken
 * later push the real net-based moment forward, the actual moment is re-computed at run
 * time via {@see AutoCloseAttendanceService::autoCloseIfDue}; if it is not yet due the
 * job is a no-op and the AutoCloseStaleShiftsCommand safety net (every 5 min) will close
 * it once due.
 *
 * Delegates all write logic to {@see AutoCloseAttendanceService} which holds the
 * row-level lock and guarantees a single close even when concurrent callers race.
 */
class AutoCloseAttendanceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
        /**
         * ISO 8601 instant of the earliest possible close moment (clock_in + max_working_hours).
         * Kept for observability/back-compat; the effective moment is re-resolved at run time.
         */
        public readonly string $closeAtIso,
    ) {}

    public function handle(AutoCloseAttendanceService $autoCloseService): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $attendance = Attendance::query()->find($this->attendanceId);

            if (!$attendance) {
                Log::warning('AutoCloseAttendanceJob: attendance not found', [
                    'attendance_id' => $this->attendanceId,
                    'company_id'    => $this->companyId,
                ]);

                return;
            }

            $closed = $autoCloseService->autoCloseIfDue($attendance);

            if (!$closed) {
                Log::debug('AutoCloseAttendanceJob: attendance already closed or not active', [
                    'attendance_id' => $this->attendanceId,
                ]);
            }
        } finally {
            tenancy()->end();
        }
    }
}
