<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoCloseAttendanceService;
use Modules\Attendance\Support\AutoClockOutRules;

class AutoCloseStaleShiftsCommand extends Command
{
    protected $signature = 'attendance:auto-close-stale-shifts
                            {--dry-run : Show which shifts would be closed without writing to DB}';

    protected $description = 'Auto clock-out shifts at expected end. With rule-based auto clock-out on, fires at '
        . 'expected end + extension_minutes and stores expected end minus the penalty '
        . '(attendance.auto_clock_out_penalty_percent % of the required minutes).';

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
        $ruleBasedAutoClockOut = AutoClockOutRules::enabledFromConfig();

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

            $expectedCarbon = Carbon::parse($closeAtRaw, $timezone);
            $expected       = CarbonImmutable::parse($expectedCarbon->toDateTimeString(), $timezone);

            // Rule-based auto clock-out: wait out the extension window snapshotted on
            // the row, then store expected minus the penalty. Disabled: fire at the
            // expected end and store it unchanged.
            $extensionMinutes = (int) ($attendance->extension_minutes ?? 0);
            $requiredMinutes  = $attendance->required_work_minutes !== null
                ? (int) $attendance->required_work_minutes
                : ($attendance->start_time
                    ? (int) Carbon::parse((string) $attendance->start_time, $timezone)->diffInMinutes($expectedCarbon)
                    : 0);

            $triggerAt = AutoClockOutRules::triggerAt($expected, $extensionMinutes, $ruleBasedAutoClockOut);
            $now       = Carbon::now($timezone);

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
            $storedClose = AutoClockOutRules::storedClockOutAt(
                $expected,
                $clockIn,
                $requiredMinutes,
                $ruleBasedAutoClockOut,
            );

            // A close stored before the expected end is a penalty close.
            $reason = $storedClose->lessThan($expected) ? 'auto_extension_penalty' : 'auto_max_ot';

            if ($isDryRun) {
                $this->line("  WOULD CLOSE attendance {$attendance->id} (user: {$user->name})"
                    . " — deadline: {$triggerAt->toDateTimeString()} store: {$storedClose->toDateTimeString()}"
                    . " reason: {$reason} TZ={$timezone}");
                $closed++;
                continue;
            }

            $didClose  = $autoCloseService->closeIfExpired($attendance, $storedClose, $reason);

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
