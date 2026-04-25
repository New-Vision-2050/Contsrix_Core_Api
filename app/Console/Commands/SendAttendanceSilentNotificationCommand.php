<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\NotificationSettings\Services\FirebaseNotificationService;

class SendAttendanceSilentNotificationCommand extends Command
{
    protected $signature = 'attendance:send-clock-in-pings {--dry-run : Show users who would receive notifications without sending}';

    protected $description = 'Send silent FCM pings to all currently clocked-in users so mobile clients can sync attendance state.';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No notifications will be sent');
        }

        $activeAttendances = Attendance::query()
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->with('user')
            ->get();

        $this->line("Found {$activeAttendances->count()} active attendances.");

        $sent    = 0;
        $skipped = 0;

        foreach ($activeAttendances as $attendance) {
            $user = $attendance->user;

            if (! $user) {
                $this->warn("  skip attendance {$attendance->id} — no user found");
                $skipped++;
                continue;
            }

            if (! $user->fcm_token) {
                $skipped++;
                continue;
            }

            $notificationData = [
                'type'          => 'attendance_tracking',
                'attendance_id' => (string) $attendance->id,
                'user_id'       => (string) $user->id,
                'clock_in_time' => $attendance->clock_in_time
                    ? (is_string($attendance->clock_in_time)
                        ? Carbon::parse($attendance->clock_in_time)->toISOString()
                        : $attendance->clock_in_time->toISOString())
                    : null,
                'status'        => $attendance->status,
                'timestamp'     => Carbon::now()->toISOString(),
                'action'        => 'sync_attendance_status',
            ];

            if ($isDryRun) {
                $this->line("  WOULD SEND to {$user->name} ({$user->email}) — attendance {$attendance->id}");
                $sent++;
                continue;
            }

            $success = FirebaseNotificationService::sendSilent($user->fcm_token, $notificationData);

            if ($success) {
                $sent++;
                Log::info('Attendance silent notification sent', [
                    'user_id'       => $user->id,
                    'attendance_id' => $attendance->id,
                ]);
            } else {
                $skipped++;
                Log::error('Attendance silent notification failed', [
                    'user_id'       => $user->id,
                    'attendance_id' => $attendance->id,
                    'fcm_token'     => $user->fcm_token,
                ]);
                $this->warn("  failed to send to {$user->name}");
            }
        }

        $this->info("Done — sent: {$sent}, skipped/failed: {$skipped}.");

        return self::SUCCESS;
    }
}
