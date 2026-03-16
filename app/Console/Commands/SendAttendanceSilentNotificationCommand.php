<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Attendance\Models\Attendance;
use Modules\NotificationSettings\Services\FirebaseNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAttendanceSilentNotificationCommand extends Command
{
    protected $signature = 'attendance:send-silent-notifications {--dry-run : Show users who would receive notifications without sending}';
    protected $description = 'Send silent notifications to users who are clocked in but not clocked out (runs every 5 minutes)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No notifications will be sent');
        }

        $this->info('Starting attendance silent notification process...');
        
        // Get all active attendances (clocked in but not clocked out)
        $activeAttendances = Attendance::whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->with('user')
            ->get();

        $this->info('Found ' . $activeAttendances->count() . ' active attendances.');
        
        $notificationsSent = 0;
        $notificationsSkipped = 0;

        foreach ($activeAttendances as $attendance) {
            $user = $attendance->user;
            
            if (!$user) {
                $this->warn("Skipping attendance {$attendance->id} - no user found");
                $notificationsSkipped++;
                continue;
            }

            if (!$user->fcm_token) {
                $this->warn("Skipping user {$user->name} - no FCM token");
                $notificationsSkipped++;
                continue;
            }

            // Only send notification when now >= end_time + max_over_time (so app can auto clock-out)
            // Example: end_time=16:00, max_over_time=4h → latestClockOut=20:00 → send when now >= 20:00
            $timezone = $attendance->timezone ?? config('app.timezone');
            if ($attendance->end_time) {
                $endTimeRaw = $attendance->end_time instanceof \DateTimeInterface
                    ? $attendance->end_time->format('Y-m-d H:i:s')
                    : (string) $attendance->end_time;
                $endTime = Carbon::parse($endTimeRaw, $timezone);
                $maxOverHours = (int) ($attendance->max_over_time ?? 0);
                $latestClockOut = $endTime->copy()->addHours($maxOverHours);
                $now = Carbon::now($timezone);
                if ($now->lt($latestClockOut)) {
                    if ($isDryRun) {
                        $this->line("  SKIP (before deadline) user: {$user->name} - now {$now->toISOString()} < latest_clock_out {$latestClockOut->toISOString()}");
                    }
                    $notificationsSkipped++;
                    continue;
                }
            }

            // Auto clock-out when deadline passed (end_time + max_over_time): update DB so user is clocked out
            if ($attendance->end_time) {
                $clockOutAt = Carbon::now($timezone);
                $attendance->update([
                    'clock_out_time' => $clockOutAt,
                    'notes' => trim(($attendance->notes ?? '') . "\n" . 'Auto clock-out (exceeded max over time)'),
                    'status' => 'completed',
                    'day_status' => 'clocked_out',
                ]);
                $attendance->calculateWorkHours();
            }

            // Prepare notification data
            $notificationData = [
                'type' => 'attendance_tracking',
                'attendance_id' => (string) $attendance->id,
                'user_id' => (string) $user->id,
                'clock_in_time' => $attendance->clock_in_time ? 
                    (is_string($attendance->clock_in_time) ? 
                        Carbon::parse($attendance->clock_in_time)->toISOString() : 
                        $attendance->clock_in_time->toISOString()
                    ) : null,
                'status' => $attendance->status,
                'timestamp' => Carbon::now()->toISOString(),
                'action' => 'sync_attendance_status'
            ];

            if ($isDryRun) {
                $this->info("WOULD SEND to user: {$user->name} ({$user->email})");
                $this->line("  - Attendance ID: {$attendance->id}");
                $this->line("  - Clock In: " . ($attendance->clock_in_time ?? 'N/A'));
                $this->line("  - Data: " . json_encode($notificationData, JSON_PRETTY_PRINT));
                $this->line("");
            } else {
                // Send silent notification
                $success = FirebaseNotificationService::sendSilent($user->fcm_token, $notificationData);
                
                if ($success) {
                    $this->info("✓ Sent silent notification to: {$user->name}");
                    $notificationsSent++;
                    
                    // Log successful notification
                    Log::info('Attendance silent notification sent', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'attendance_id' => $attendance->id,
                        'clock_in_time' => $attendance->clock_in_time,
                        'timestamp' => Carbon::now()->toISOString()
                    ]);
                } else {
                    $this->error("✗ Failed to send notification to: {$user->name}");
                    $notificationsSkipped++;
                    
                    // Log failed notification
                    Log::error('Attendance silent notification failed', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'attendance_id' => $attendance->id,
                        'fcm_token' => $user->fcm_token,
                        'timestamp' => Carbon::now()->toISOString()
                    ]);
                }
            }
        }

        $this->info('');
        $this->info('Process completed:');
        $this->line("  - Total active attendances: {$activeAttendances->count()}");
        $this->line("  - Notifications sent: {$notificationsSent}");
        $this->line("  - Notifications skipped: {$notificationsSkipped}");
        
        if ($isDryRun) {
            $this->info('DRY RUN COMPLETED - No actual notifications were sent');
        }

        return Command::SUCCESS;
    }
}
