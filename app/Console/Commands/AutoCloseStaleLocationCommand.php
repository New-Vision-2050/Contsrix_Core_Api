<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\StaleLocationClockOutService;
use Modules\Attendance\Support\StaleLocationClockOut;

class AutoCloseStaleLocationCommand extends Command
{
    protected $signature = 'attendance:auto-close-stale-location
                            {--dry-run : Show which shifts would be closed without writing to DB}';

    protected $description = 'Auto clock-out employees who are still clocked in but have not sent GPS for 45 minutes.';

    public function handle(StaleLocationClockOutService $staleLocationClockOut): int
    {
        if (! config('attendance.stale_location_auto_clock_out_enabled', false)) {
            $this->info('Stale-location auto clock-out is disabled. No shifts will be closed.');

            return self::SUCCESS;
        }
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

        $this->line("Found {$activeAttendances->count()} active clocked-in shifts.");

        $closed = 0;
        $skipped = 0;

        foreach ($activeAttendances as $attendance) {
            if (! StaleLocationClockOut::isStale($attendance)) {
                continue;
            }

            $closeAt = StaleLocationClockOut::closeAt($attendance);
            $heartbeat = StaleLocationClockOut::lastHeartbeatAt($attendance);
            $userName = $attendance->user->name ?? $attendance->user_id;

            if ($isDryRun) {
                $this->line("  WOULD CLOSE attendance {$attendance->id} (user: {$userName})"
                    . ' — last location: ' . ($heartbeat?->toDateTimeString() ?? 'none')
                    . ' clock-out: ' . ($closeAt?->toDateTimeString() ?? 'none')
                    . " TZ={$attendance->timezone}");
                $closed++;
                continue;
            }

            $companyId = (string) ($attendance->company_id ?? '');
            $initializedHere = false;
            if ($companyId !== '' && (! tenancy()->initialized || (string) tenant('id') !== $companyId)) {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                tenancy()->initialize($companyId);
                $initializedHere = true;
            }

            try {
                $didClose = $staleLocationClockOut->closeIfStale($attendance);
            } catch (\Throwable $e) {
                $skipped++;
                Log::error('Auto close stale location failed', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  fail attendance {$attendance->id}: {$e->getMessage()}");
                continue;
            } finally {
                if ($initializedHere && tenancy()->initialized) {
                    tenancy()->end();
                }
            }

            if ($didClose) {
                $closed++;
                Log::info('Auto close stale location', [
                    'attendance_id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'clock_out_time' => $closeAt?->format('Y-m-d H:i:s'),
                    'last_location_at' => $heartbeat?->format('Y-m-d H:i:s'),
                    'timezone' => $attendance->timezone,
                ]);
                $this->line("  closed attendance {$attendance->id} (user: {$userName})");
            } else {
                $skipped++;
                $this->line("  skip attendance {$attendance->id} — already closed by another process");
            }
        }

        $this->info("Done — closed: {$closed}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
