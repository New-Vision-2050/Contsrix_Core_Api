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
 * Closes a shift at its max-overtime deadline (end_time + max_over_time_hours * 60 min).
 *
 * Dispatched with a future delay at clock-in time so the exact deadline is honoured
 * regardless of cron-command jitter.  The AutoCloseStaleShiftsCommand acts as a
 * safety net: if this job is lost or delayed, the command will catch the shift on
 * its next run (at most 5 minutes late).
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
        /** ISO 8601 instant — equals end_time + max_over_time. Stored as clock_out_time. */
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

            $closeAt = CarbonImmutable::parse($this->closeAtIso);
            $closed  = $autoCloseService->closeIfExpired($attendance, $closeAt, 'auto_max_ot');

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
