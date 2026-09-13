<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoCloseAttendanceService;
use Modules\Attendance\Support\AutoCloseGrace;

class AutoCloseStaleShiftsCommand extends Command
{
    protected $signature = 'attendance:auto-close-stale-shifts
                            {--dry-run : Show which shifts would be closed without writing to DB}';

    protected $description = 'Auto clock-out shifts at expected end. clock_out_time is the shift end '
        . '(or expected minus 2 hours when attendance.auto_close_grace_enabled is on).';

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
        $graceEnabled = AutoCloseGrace::enabledFromConfig();

        foreach ($activeAttendances as $attendance) {
            $user = $attendance->user;

            if (! $user) {
                $this->warn("  skip attendance {$attendance->id} — no user found");
                $skipped++;
                continue;
            }

            $timezone = $attendance->timezone ?? config('app.timezone');

            // Rules V2: close at expected_clock_out_time (when the required hours complete).
            // Rows predating V2 fall back to the scheduled end_time.
            $closeAtRaw = $attendance->expected_clock_out_time ?? $attendance->end_time;
            $closeAtRaw = $closeAtRaw instanceof \DateTimeInterface
                ? $closeAtRaw->format('Y-m-d H:i:s')
                : (string) $closeAtRaw;

            $expectedCarbon   = Carbon::parse($closeAtRaw, $timezone);
            $maxOverTimeHours = (float) ($attendance->max_over_time ?? 0);
            $triggerAt        = $expectedCarbon->copy()->addMinutes(
                AutoCloseGrace::delayMinutes($maxOverTimeHours, $graceEnabled)
            );
            $now              = Carbon::now($timezone);

            if (! $now->gte($triggerAt)) {
                continue;
            }

            $clockIn = $attendance->clock_in_time
                ? CarbonImmutable::parse(
                    $attendance->clock_in_time instanceof \DateTimeInterface
                        ? $attendance->clock_in_time->format('Y-m-d H:i:s')
                        : (string) $attendance->clock_in_time,
                    $timezone,
                )
                : null;
            $storedClose = AutoCloseGrace::storedClockOutAt(
                CarbonImmutable::parse($expectedCarbon->toDateTimeString(), $timezone),
                $clockIn,
                $graceEnabled,
            );

            if ($isDryRun) {
                $this->line("  WOULD CLOSE attendance {$attendance->id} (user: {$user->name})"
                    . " — deadline: {$triggerAt->toDateTimeString()} store: {$storedClose->toDateTimeString()} TZ={$timezone}");
                $closed++;
                continue;
            }

            $didClose  = $autoCloseService->closeIfExpired($attendance, $storedClose, 'auto_max_ot');

            if ($didClose) {
                $closed++;
                Log::info('Auto close stale shift', [
                    'attendance_id'  => $attendance->id,
                    'user_id'        => $user->id,
                    'clock_out_time' => $storedClose->format('Y-m-d H:i:s'),
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
