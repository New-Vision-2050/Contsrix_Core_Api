<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\NotificationSettings\Services\FirebaseNotificationService;

class SendAttendanceSilentNotificationCommand extends Command
{
    protected $signature = 'attendance:send-silent-notifications {--dry-run : Show users who would receive notifications without sending}';

    protected $description = 'Send silent notifications to all clocked-in users. When now >= end_time + max_over_time (hours) in the attendance timezone, auto clock-out the user and record clock_out_time = end_time (shift end), so overtime stays 0.';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No notifications or DB updates will be applied');
        }

        $this->info('Starting attendance silent notification process...');

        $activeAttendances = Attendance::query()
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->with('user')
            ->get();

        $this->info('Found '.$activeAttendances->count().' active attendances.');

        $notificationsSent = 0;
        $notificationsSkipped = 0;
        $autoClockOuts = 0;

        foreach ($activeAttendances as $attendance) {
            $user = $attendance->user;

            if (! $user) {
                $this->warn("Skipping attendance {$attendance->id} - no user found");
                $notificationsSkipped++;

                continue;
            }

            $timezone = $attendance->timezone ?? config('app.timezone');

            $notificationData = [
                'type' => 'attendance_tracking',
                'attendance_id' => (string) $attendance->id,
                'user_id' => (string) $user->id,
                'clock_in_time' => $attendance->clock_in_time
                    ? (is_string($attendance->clock_in_time)
                        ? Carbon::parse($attendance->clock_in_time)->toISOString()
                        : $attendance->clock_in_time->toISOString())
                    : null,
                'status' => $attendance->status,
                'timestamp' => Carbon::now()->toISOString(),
                'action' => 'sync_attendance_status',
            ];

            if ($user->fcm_token) {
                if ($isDryRun) {
                    $this->info("WOULD SEND silent notification to: {$user->name} ({$user->email})");
                    $this->line("  - Attendance ID: {$attendance->id}");
                    $this->line('  - Clock In: '.($attendance->clock_in_time ?? 'N/A'));
                    $this->line('  - Data: '.json_encode($notificationData, JSON_PRETTY_PRINT));
                    $this->line('');
                    $notificationsSent++;
                } else {
                    $success = FirebaseNotificationService::sendSilent($user->fcm_token, $notificationData);

                    if ($success) {
                        $this->info("✓ Sent silent notification to: {$user->name}");
                        $notificationsSent++;
                        Log::info('Attendance silent notification sent', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'attendance_id' => $attendance->id,
                            'clock_in_time' => $attendance->clock_in_time,
                            'timestamp' => Carbon::now()->toISOString(),
                        ]);
                    } else {
                        $this->error("✗ Failed to send notification to: {$user->name}");
                        $notificationsSkipped++;
                        Log::error('Attendance silent notification failed', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'attendance_id' => $attendance->id,
                            'fcm_token' => $user->fcm_token,
                            'timestamp' => Carbon::now()->toISOString(),
                        ]);
                    }
                }
            } else {
                $this->warn("Skipping silent notification for {$user->name} - no FCM token");
                $notificationsSkipped++;
            }

            if ($attendance->end_time) {
                $endTimeRaw = $attendance->end_time instanceof \DateTimeInterface
                    ? $attendance->end_time->format('Y-m-d H:i:s')
                    : (string) $attendance->end_time;
                // end_time is stored in the branch timezone, so parse it as such.
                $endTime = Carbon::parse($endTimeRaw, $timezone);

                // max_over_time is stored as HOURS (can be decimal, e.g. 4.5).
                // It acts only as the AUTO-CLOSE TRIGGER — how long past end_time we
                // wait before forcibly closing the shift. The stored clock_out_time is
                // always end_time exactly (overtime for the attendance is therefore 0).
                $maxOverTimeHours = (float) ($attendance->max_over_time ?? 0);
                $latestClockOut = $endTime->copy()->addMinutes((int) round($maxOverTimeHours * 60));
                $now = Carbon::now($timezone);

                if ($now->gte($latestClockOut)) {
                    $attendance->refresh();
                    if ($attendance->clock_out_time !== null || $attendance->clock_in_time === null) {
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line("  WOULD AUTO CLOCK-OUT attendance {$attendance->id} (user: {$user->name}) — now >= end_time + max_over_time (trigger: {$latestClockOut->toISOString()}; stored clock_out_time will be end_time: {$endTime->toISOString()})");
                        $autoClockOuts++;

                        continue;
                    }

                    $trackingPoints = $attendance->location_tracking ?? [];
                    $latestPoint = ! empty($trackingPoints) ? end($trackingPoints) : $attendance->clock_in_location;
                    $noteLine = '[Auto] Clock-out: exceeded end_time + max_over_time; stored as end_time ('.$endTime->toISOString().')';

                    $attendance->update([
                        // Store end_time as clock_out_time (not now()) so the recorded shift
                        // ends cleanly at the scheduled boundary regardless of when the
                        // cron actually ran.
                        'clock_out_time' => $endTime->format('Y-m-d H:i:s'),
                        'clock_out_location' => $latestPoint,
                        'status' => Attendance::STATUS_COMPLETED,
                        'day_status' => 'clocked_out',
                        'notes' => trim(($attendance->notes ?? '')."\n".$noteLine),
                    ]);

                    $attendance->refresh();
                    $attendance->calculateWorkHours();
                    $autoClockOuts++;
                    Log::info('Attendance auto clock-out (silent notification command)', [
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'clock_out_time' => $endTime->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        $this->info('');
        $this->info('Process completed:');
        $this->line("  - Total active attendances: {$activeAttendances->count()}");
        $this->line("  - Notifications sent (or would send in dry-run): {$notificationsSent}");
        $this->line("  - Notifications skipped (no user / no FCM / send failed): {$notificationsSkipped}");
        $this->line("  - Auto clock-outs applied (or would apply in dry-run): {$autoClockOuts}");

        if ($isDryRun) {
            $this->info('DRY RUN COMPLETED - No notifications sent and no DB updates applied');
        }

        return Command::SUCCESS;
    }
}
