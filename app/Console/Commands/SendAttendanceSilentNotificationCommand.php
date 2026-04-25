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

    protected $description = 'Send silent notifications to all clocked-in users; auto clock-out when now >= end_time + max_over_time (hours) in attendance timezone';

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
                $endTime = Carbon::parse($endTimeRaw, $timezone);
                $this->line("  End time: {$endTime}");
                $this->line("  Max over time: {$attendance->max_over_time}");
                $this->line("  attendance end_time: {$attendance->end_time}");
                $maxOverHours = (int) ($attendance->max_over_time ?? 0);
                $latestClockOut = $endTime->copy()->addHours($maxOverHours);
                $now = Carbon::now($timezone);

                if ($now->gte($latestClockOut)) {
                    $attendance->refresh();
                    if ($attendance->clock_out_time !== null || $attendance->clock_in_time === null) {
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line("  WOULD AUTO CLOCK-OUT attendance {$attendance->id} (user: {$user->name}) — now >= end_time + max_over_time (latest: {$latestClockOut->toISOString()})");
                        $autoClockOuts++;

                        continue;
                    }

                    $clockOutAt = Carbon::now($timezone);
                    $trackingPoints = $attendance->location_tracking ?? [];
                    $latestPoint = ! empty($trackingPoints) ? end($trackingPoints) : $attendance->clock_in_location;
                    $noteLine = '[Auto] Clock-out: exceeded end_time + max_over_time at '.$clockOutAt->toISOString();

                    $attendance->update([
                        'clock_out_time' => $clockOutAt->format('Y-m-d H:i:s'),
                        'clock_out_location' => $latestPoint,
                        'status' => Attendance::STATUS_COMPLETED,
                        'day_status' => 'clocked_out',
                        'notes' => trim(($attendance->notes ?? '')."\n".$noteLine),
                    ]);

                    $attendance->refresh();
                    $attendance->updateTotalBreakHours();
                    $attendance->calculateWorkHours();
                    $autoClockOuts++;
                    Log::info('Attendance auto clock-out (silent notification command)', [
                        'attendance_id' => $attendance->id,
                        'user_id' => $user->id,
                        'clock_out_time' => $clockOutAt->format('Y-m-d H:i:s'),
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
