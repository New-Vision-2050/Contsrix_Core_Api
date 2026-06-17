<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\Process\Services\WorkflowNotifierRegistry;
use Modules\User\Models\User;

/**
 * Central listener that handles all notification channels when a workflow step becomes active:
 * - Real-time broadcast (WebSocket) via EmployeeTaskNotification + InboxCountsUpdated
 * - Email notification (if templateStep->notify_by_email)
 * - SMS notification (if templateStep->notify_by_sms)
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

        // 1. Real-time broadcast (always sent regardless of email/SMS flags)
        $this->broadcastRealTime($event);

        // 2. Resolve notification channels from template flags
        $channels = [];
        if ($templateStep->notify_by_email) {
            $channels[] = 'mail';
        }
        if ($templateStep->notify_by_sms) {
            $channels[] = 'sms';
        }

        if ($channels === []) {
            return;
        }

        // 3. Send Laravel Notifications (email + SMS) to each user
        $users = User::query()->whereIn('id', $userIds)->get();
        $notification = new WorkflowActionRequired(
            $event->processStep,
            $templateStep,
            $channels,
        );

        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (\Throwable $e) {
                Log::error('WorkflowActionRequired notification failed', [
                    'user_id' => $user->id,
                    'process_step_id' => $event->processStep->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
