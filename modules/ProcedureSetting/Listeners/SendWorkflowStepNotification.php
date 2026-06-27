<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\ProcedureSetting\Services\WorkflowPushNotificationService;
use Modules\Process\Services\WorkflowNotifierRegistry;
use Modules\User\Models\User;

/**
 * Central listener that handles all notification channels when a workflow step becomes active:
 * - Real-time broadcast (WebSocket) via EmployeeTaskNotification + InboxCountsUpdated
 * - Push notification (FCM) (if templateStep->notify_by_push)
 * - Email notification (if templateStep->notify_by_email)
 * - SMS notification (if templateStep->notify_by_sms)
 * - WhatsApp notification (if templateStep->notify_by_whatsapp)
 */
class SendWorkflowStepNotification
{
    public function handle(WorkflowStepActivated $event): void
    {
        $templateStep = $event->templateStep;
        $userIds = $event->userIds;

        if ($userIds === []) {
            Log::warning('WorkflowStepActivated: no user IDs to notify', [
                'process_step_id' => $event->processStep->id,
                'template_step_id' => $templateStep->id,
            ]);

            return;
        }

        // 1. Real-time broadcast (always sent regardless of email/SMS/push flags)
        $this->broadcastRealTime($event);

        // 2. Push notification (FCM) when the step is configured for it
        WorkflowPushNotificationService::sendForStep($templateStep, $userIds);

        // 3. Resolve notification channels from template flags
        $channels = [];
        if ($templateStep->notify_by_email) {
            $channels[] = 'mail';
        }
        if ($templateStep->notify_by_sms) {
            $channels[] = 'sms';
        }
        if ($templateStep->notify_by_whatsapp) {
            $channels[] = 'whatsapp';
        }

        if ($channels === []) {
            return;
        }

        // 4. Send Laravel Notifications (email + SMS + WhatsApp) to each user
        $users = User::query()->whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $userChannels = $this->channelsAvailableForUser($channels, $user, $event);

            if ($userChannels === []) {
                continue;
            }

            try {
                $user->notify(new WorkflowActionRequired(
                    $event->processStep,
                    $templateStep,
                    $userChannels,
                ));
            } catch (\Throwable $e) {
                Log::error('WorkflowActionRequired notification failed', [
                    'user_id' => $user->id,
                    'process_step_id' => $event->processStep->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function channelsAvailableForUser(array $channels, User $user, WorkflowStepActivated $event): array
    {
        $available = $channels;

        if (in_array('mail', $available, true) && trim((string) $user->email) === '') {
            Log::warning('WorkflowActionRequired mail skipped: user has no email', [
                'user_id' => $user->id,
                'process_step_id' => $event->processStep->id,
            ]);
            $available = array_values(array_diff($available, ['mail']));
        }

        if (in_array('sms', $available, true) && trim((string) $user->phone) === '') {
            Log::warning('WorkflowActionRequired sms skipped: user has no phone', [
                'user_id' => $user->id,
                'process_step_id' => $event->processStep->id,
            ]);
            $available = array_values(array_diff($available, ['sms']));
        }

        if (in_array('whatsapp', $available, true) && trim((string) $user->phone) === '') {
            Log::warning('WorkflowActionRequired whatsapp skipped: user has no phone', [
                'user_id' => $user->id,
                'process_step_id' => $event->processStep->id,
            ]);
            $available = array_values(array_diff($available, ['whatsapp']));
        }

        return $available;
    }

    private function broadcastRealTime(WorkflowStepActivated $event): void
    {
        $process = $event->processStep->process;
        if ($process === null) {
            return;
        }

        $notifier = app(WorkflowNotifierRegistry::class)->for($process->processable_type);
        if ($notifier === null) {
            return;
        }

        $notifier->notifyStepActivated($event->processStep, $event->userIds, $event->context);

        foreach ($event->userIds as $userId) {
            $counts = $notifier->inboxCountsForUser((string) $userId);
            event(new InboxCountsUpdated(
                userId: (string) $userId,
                pendingTasks: $counts['pending_tasks'],
                pendingExtensions: $counts['pending_extensions'],
                pendingApprovals: $counts['pending_approvals'],
                total: $counts['total'],
            ));
        }
    }

}
