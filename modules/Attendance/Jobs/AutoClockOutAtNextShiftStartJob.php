<?php

declare(strict_types=1);

namespace Modules\Attendance\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;

/**
 * When a later work period starts the same day, clock out the still-open previous shift.
 * Scheduled from {@see AttendanceService::clockIn} at the next period's start time.
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

    public function handle(): void
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
                    'company_id' => $this->companyId,
                ]);

                return;
            }

            if ($attendance->clock_out_time !== null || $attendance->clock_in_time === null) {
                return;
            }

            $clockOutAt = Carbon::parse($this->clockOutAtIso)->utc();

            $trackingPoints = $attendance->location_tracking ?? [];
            $latestPoint = !empty($trackingPoints) ? end($trackingPoints) : $attendance->clock_in_location;

            $activeBreak = $attendance->activeBreak();
            if ($activeBreak) {
                $activeBreak->end_time = $clockOutAt->copy();
                $activeBreak->calculateDuration();
                $activeBreak->save();
            }

            $noteLine = '[Auto] Clock-out: next work period started';
            $attendance->update([
                'clock_out_time' => $clockOutAt,
                'clock_out_location' => $latestPoint,
                'status' => Attendance::STATUS_COMPLETED,
                'day_status' => 'clocked_out',
                'notes' => trim(($attendance->notes ?? '') . "\n" . $noteLine),
            ]);

            $attendance->refresh();
            $attendance->updateTotalBreakHours();
            $attendance->calculateWorkHours();
        } finally {
            tenancy()->end();
        }
    }
}
