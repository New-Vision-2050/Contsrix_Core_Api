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
 * When a later work period starts, close any still-open prior shift for the same user.
 * Scheduled from {@see AttendanceService::clockIn} at the next period's start time.
 *
 * Delegates all write logic to {@see AutoCloseAttendanceService} which holds the row
 * lock and guarantees a single close even when multiple callers race.
 */
class AutoClockOutAtNextShiftStartJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
        /** ISO 8601 instant used as clock_out_time (boundary of next shift). */
        public readonly string $clockOutAtIso,
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
                Log::warning('AutoClockOutAtNextShiftStartJob: attendance not found', [
                    'attendance_id' => $this->attendanceId,
                    'company_id'    => $this->companyId,
                ]);

                return;
            }

            $closeAt = CarbonImmutable::parse($this->clockOutAtIso);

            $closed = $autoCloseService->closeIfExpired($attendance, $closeAt, 'auto_next_shift');

            if (!$closed) {
                Log::debug('AutoClockOutAtNextShiftStartJob: attendance already closed or inactive', [
                    'attendance_id' => $this->attendanceId,
                ]);
            }
        } finally {
            tenancy()->end();
        }
    }
}
