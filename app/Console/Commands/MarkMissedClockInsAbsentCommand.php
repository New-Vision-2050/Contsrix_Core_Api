<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AbsenceMarkingService;

/**
 * Cron safety net for MarkAbsentIfNoClockInJob: sweeps un-clocked-in rows whose absent_at
 * deadline has passed. Runs every 5 minutes next to attendance:auto-close-stale-shifts.
 */
class MarkMissedClockInsAbsentCommand extends Command
{
    protected $signature = 'attendance:mark-missed-clock-ins-absent
                            {--dry-run : Show which rows would be marked absent without writing to DB}';

    protected $description = 'Mark rows absent when no clock-in happened before the can_clock_in_before deadline.';

    public function handle(AbsenceMarkingService $absenceService): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] No DB writes will occur.');
        }

        // Rollout phase 5: disabled → sweep reports what WOULD happen (same as dry-run).
        if (! config('attendance.absence_marking_enabled', true)) {
            $isDryRun = true;
            $this->warn('absence_marking_enabled=false — reporting only.');
        }

        $candidates = Attendance::query()
            ->whereNull('clock_in_time')
            ->where('is_absent', 0)
            ->whereNotNull('absent_at')
            ->whereNotNull('start_time')
            ->whereNotIn('status', [
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_COMPLETED,
                Attendance::STATUS_HOLIDAY,
            ])
            ->with('user')
            ->get();

        $marked  = 0;
        $skipped = 0;

        foreach ($candidates as $attendance) {
            $timezone = $attendance->timezone ?? config('app.timezone');

            $absentAtRaw = $attendance->absent_at instanceof \DateTimeInterface
                ? $attendance->absent_at->format('Y-m-d H:i:s')
                : (string) $attendance->absent_at;

            $absentAt = CarbonImmutable::parse($absentAtRaw, $timezone);

            if (Carbon::now($timezone)->lt($absentAt)) {
                continue;
            }

            if ($isDryRun) {
                $this->line("  WOULD MARK ABSENT attendance {$attendance->id}"
                    . " — absent_at: {$absentAt->toDateTimeString()} TZ={$timezone}");
                $marked++;
                continue;
            }

            $didMark = $absenceService->markAbsentIfNoClockIn($attendance, $absentAt);

            if ($didMark) {
                $marked++;
                Log::info('Marked absent (missed clock-in deadline)', [
                    'attendance_id' => $attendance->id,
                    'user_id'       => $attendance->user_id,
                    'absent_at'     => $absentAt->toDateTimeString(),
                    'timezone'      => $timezone,
                ]);
            } else {
                $skipped++;
            }
        }

        $this->info("Done — marked absent: {$marked}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
