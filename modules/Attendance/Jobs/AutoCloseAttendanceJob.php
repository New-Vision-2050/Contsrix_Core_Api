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
 * Closes a shift at the precomputed closeAtIso (expected clock-out by default).
 * When attendance.rule_based_auto_clock_out_enabled is on, the job is delayed
 * until expected clock-out + extension_minutes and closeAtIso is expected
 * clock-out minus the auto-clock-out penalty (percent of required minutes).
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
        /** ISO 8601 instant stored as clock_out_time (shift end, or expected minus 2h if grace is on). */
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

            // Audit trail: a close stored BEFORE the expected clock-out is a penalty
            // close (rule-based auto clock-out); anything else is a plain boundary close.
            $expectedRaw = $attendance->expected_clock_out_time;
            $expected    = $expectedRaw !== null && $expectedRaw !== ''
                ? CarbonImmutable::parse(
                    $expectedRaw instanceof \DateTimeInterface
                        ? $expectedRaw->format('Y-m-d H:i:s')
                        : (string) $expectedRaw,
                    $attendance->timezone ?: config('app.timezone'),
                )
                : null;
            $reason = $expected !== null && $closeAt->lessThan($expected)
                ? 'auto_extension_penalty'
                : 'auto_max_ot';

            $closed  = $autoCloseService->closeIfExpired($attendance, $closeAt, $reason);

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
