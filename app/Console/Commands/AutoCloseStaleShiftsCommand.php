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

    protected $description = 'Auto clock-out shifts that have reached their close moment. '
        . 'Runs every 5 minutes. Closes a shift once the user has worked max_working_hours of '
        . 'NET time (regular), or once overtime reaches max_over_time / the shift window ends '
        . '(overtime re-clock-in sessions). clock_out_time is set to the exact deterministic '
        . 'moment, not now(), so hours are capped regardless of cron jitter. Falls back to '
        . 'end_time + max_over_time for constraints without max_working_hours.';

    public function handle(AutoCloseAttendanceService $autoCloseService): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] No DB writes will occur.');
        }

        $activeAttendances = Attendance::query()
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->where('status', Attendance::STATUS_ACTIVE)
            ->with('user')
            ->get();

        $this->line("Found {$activeAttendances->count()} active shifts.");

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
            $now      = CarbonImmutable::now($timezone);

            $decision = $autoCloseService->resolveAutoCloseMoment($attendance, $now);
            if ($decision === null) {
                continue;
            }

            [$closeAt, $reason] = $decision;

            if ($isDryRun) {
                $this->line("  WOULD CLOSE attendance {$attendance->id} (user: {$user->name})"
                    . " — closeAt: {$closeAt->toDateTimeString()} reason: {$reason} TZ={$timezone}");
                $closed++;
                continue;
            }

            $didClose = $autoCloseService->closeIfExpired($attendance, $closeAt, $reason);

            if ($didClose) {
                $closed++;
                Log::info('Auto close stale shift', [
                    'attendance_id'  => $attendance->id,
                    'user_id'        => $user->id,
                    'clock_out_time' => $closeAt->format('Y-m-d H:i:s'),
                    'reason'         => $reason,
                    'timezone'       => $timezone,
                ]);
                $this->line("  closed attendance {$attendance->id} (user: {$user->name}) — {$reason}");
            } else {
                $skipped++;
                $this->line("  skip attendance {$attendance->id} — already closed by another process");
            }
        }

        $this->info("Done — closed: {$closed}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
