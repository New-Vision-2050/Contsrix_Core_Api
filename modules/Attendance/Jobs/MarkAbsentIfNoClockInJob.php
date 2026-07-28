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
use Modules\Attendance\Services\AbsenceMarkingService;

/**
 * Fires at the first-clock-in deadline (absentAt) and marks the row absent when the
 * employee never clocked in. Idempotent — safe to race against a real clock-in (INV-27).
 */
class MarkAbsentIfNoClockInJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
        /** ISO 8601 instant — the deadline that produced the absence (INV-15). */
        public readonly string $absentAtIso,
    ) {}

    public function handle(AbsenceMarkingService $absenceService): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $attendance = Attendance::query()->find($this->attendanceId);

            if (!$attendance) {
                Log::warning("MarkAbsentIfNoClockInJob: attendance {$this->attendanceId} not found.");
                return;
            }

            $absentAt = CarbonImmutable::parse($this->absentAtIso);
            $absenceService->markAbsentIfNoClockIn($attendance, $absentAt);
        } finally {
            tenancy()->end();
        }
    }
}
