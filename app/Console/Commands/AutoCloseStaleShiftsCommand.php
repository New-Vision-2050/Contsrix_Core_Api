<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoCloseAttendanceService;

class AutoCloseStaleShiftsCommand extends Command
{
    protected $signature = 'attendance:auto-close-stale-shifts
                            {--dry-run : Show which shifts would be closed without writing to DB}';

    protected $description = 'Auto clock-out shifts whose deadline (end_time + max_over_time) has passed. '
        . 'Runs every 5 minutes. clock_out_time is set to the exact deadline, not now(), so overtime '
        . 'is capped deterministically regardless of cron jitter.';

    public function handle(AutoCloseAttendanceService $autoCloseService): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] No DB writes will occur.');
        }

        $activeAttendances = Attendance::query()
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->whereNotNull('end_time')
            ->with('user')
            ->get();

        $this->line("Found {$activeAttendances->count()} active shifts with an end_time.");

        $closed  = 0;
        $skipped = 0;

        foreach ($activeAttendances as $attendance) {
            $user = $attendance->user;

            if (! $user) {
                $this->warn("  skip attendance {$attendance->id} — no user found");
                $skipped++;
                continue;
            }

            $timezone = $attendance->timezone ?? config('app.timezone');

            $endTimeRaw = $attendance->end_time instanceof \DateTimeInterface
                ? $attendance->end_time->format('Y-m-d H:i:s')
                : (string) $attendance->end_time;

            // end_time is stored in branch TZ; max_over_time is HOURS (decimal).
            $endTime         = Carbon::parse($endTimeRaw, $timezone);
            $maxOverTimeHours = (float) ($attendance->max_over_time ?? 0);
            $triggerAt        = $endTime->copy()->addMinutes((int) round($maxOverTimeHours * 60));
            $now              = Carbon::now($timezone);

            if (! $now->gte($triggerAt)) {
                continue;
            }

            if ($isDryRun) {
                $this->line("  WOULD CLOSE attendance {$attendance->id} (user: {$user->name})"
                    . " — deadline: {$triggerAt->toDateTimeString()} TZ={$timezone}");
                $closed++;
                continue;
            }

            $closeAt   = CarbonImmutable::parse($endTime->toDateTimeString(), $timezone);
            $didClose  = $autoCloseService->closeIfExpired($attendance, $closeAt, 'auto_max_ot');

            if ($didClose) {
                $closed++;
                Log::info('Auto close stale shift', [
                    'attendance_id'  => $attendance->id,
                    'user_id'        => $user->id,
                    'clock_out_time' => $endTime->format('Y-m-d H:i:s'),
                    'timezone'       => $timezone,
                ]);
                $this->line("  closed attendance {$attendance->id} (user: {$user->name})");
            } else {
                $skipped++;
                $this->line("  skip attendance {$attendance->id} — already closed by another process");
            }
        }

        $this->info("Done — closed: {$closed}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
