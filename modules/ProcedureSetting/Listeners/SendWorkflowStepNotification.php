<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Services\WorkflowNotifierRegistry;

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

        // 2. Email / SMS / WhatsApp / Push — delegated to WorkflowEngine (central dispatch)
        app(WorkflowEngine::class)->dispatchNotifications(
            $templateStep,
            $userIds,
            $event->processStep?->id,
        );
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
