<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\NotificationSettings\Services\FirebaseNotificationService;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\User\Models\User;

class SendSiteStatusUpdateRemindersCommand extends Command
{
    protected $signature = 'project-notifications:send-site-status-reminders {--dry-run : Show users who would receive notifications without sending}';

    protected $description = 'Send FCM push reminders to assigned users when the last site status update is older than 30 days until the notification is completed.';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No notifications will be sent');
        }

        $reminderThresholdDays = (int) config('app.site_status_reminder_threshold_days', 30);
        $reminderIntervalDays = (int) config('app.site_status_reminder_interval_days', 7);

        $thresholdDate = Carbon::now()->subDays($reminderThresholdDays)->toDateString();
        $intervalDate = Carbon::now()->subDays($reminderIntervalDays);

        $notifications = ProjectNotification::query()
            ->where('status', '!=', 'completed')
            ->where(function ($query) use ($intervalDate) {
                $query->whereNull('last_site_status_reminder_sent_at')
                    ->orWhere('last_site_status_reminder_sent_at', '<=', $intervalDate);
            })
            ->whereExists(function ($query) use ($thresholdDate) {
                $query->selectRaw('1')
                    ->from('project_notification_site_status_updates')
                    ->whereColumn('project_notification_site_status_updates.project_notification_id', 'project_notifications.id')
                    ->havingRaw('MAX(update_date) <= ?', [$thresholdDate]);
            })
            ->get();

        $this->line("Found {$notifications->count()} project notifications requiring a site status reminder.");

        $sent = 0;
        $skipped = 0;

        foreach ($notifications as $notification) {
            $assignedUserIds = $notification->assigned_user_ids ?? [];

            if ($assignedUserIds === []) {
                $this->warn("  skip notification {$notification->id} — no assigned users");
                $skipped++;
                continue;
            }

            $users = User::withoutGlobalScopes()
                ->whereIn('id', $assignedUserIds)
                ->whereNotNull('fcm_token')
                ->get();

            if ($users->isEmpty()) {
                $this->warn("  skip notification {$notification->id} — no assigned users with FCM tokens");
                $skipped++;
                continue;
            }

            $title = 'تذكير بتحديث حالة الموقع';
            $body = "لم يتم تحديث حالة الموقع للإخطار رقم {$notification->notification_number} منذ {$reminderThresholdDays} يومًا";

            $data = [
                'type' => 'project_notification_site_status_reminder',
                'project_notification_id' => (string) $notification->id,
                'notification_number' => (string) $notification->notification_number,
                'action' => 'open_project_notification',
            ];

            foreach ($users as $user) {
                if ($isDryRun) {
                    $this->line("  WOULD SEND to {$user->name} ({$user->email}) — notification {$notification->id}");
                    $sent++;
                    continue;
                }

                $success = FirebaseNotificationService::send($user->fcm_token, $title, $body, $data);

                if ($success) {
                    $sent++;
                    Log::info('Project notification site status reminder sent', [
                        'user_id' => $user->id,
                        'project_notification_id' => $notification->id,
                    ]);
                } else {
                    $skipped++;
                    Log::error('Project notification site status reminder failed', [
                        'user_id' => $user->id,
                        'project_notification_id' => $notification->id,
                        'fcm_token' => $user->fcm_token,
                    ]);
                    $this->warn("  failed to send to {$user->name}");
                }
            }

            if (! $isDryRun) {
                $notification->update(['last_site_status_reminder_sent_at' => Carbon::now()]);
            }
        }

        $this->info("Done — sent: {$sent}, skipped/failed: {$skipped}.");

        return self::SUCCESS;
    }
}
