<?php

declare(strict_types=1);

namespace Modules\Attendance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Support\OutZoneClockOutWarning;

/**
 * Fires 5 minutes after the out-of-zone voice warning. Re-runs location
 * validation: if the employee is still outside, that path clocks them out.
 */
class ClockOutAfterOutZoneWarningJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
    ) {}

    public function handle(AttendanceConstraintService $constraintService): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $attendance = Attendance::query()->with('user')->find($this->attendanceId);

            if ($attendance === null
                || $attendance->clock_out_time
                || empty($attendance->out_zone_warning_at)
                || ! OutZoneClockOutWarning::graceExpired($attendance)
            ) {
                return;
            }

            $constraintService->validateAttendance($attendance, []);
        } catch (\Throwable $e) {
            Log::error('ClockOutAfterOutZoneWarningJob failed', [
                'attendance_id' => $this->attendanceId,
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            tenancy()->end();
        }
    }
}
