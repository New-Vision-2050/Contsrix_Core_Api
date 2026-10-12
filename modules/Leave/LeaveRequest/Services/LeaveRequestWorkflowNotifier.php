<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Services;

use Modules\Process\Contracts\WorkflowNotifier;
use Modules\Process\Models\ProcessStep;

/**
 * Real-time notifier for leave-request workflow steps.
 *
 * Email/SMS/WhatsApp/push are already handled centrally by WorkflowEngine::
 * dispatchNotifications(); this hook is for module-specific broadcasts and inbox
 * counters. Leave requests currently have no dedicated broadcast channel, so this
 * is intentionally a no-op placeholder (same as ClientRequestWorkflowNotifier).
 */
final class LeaveRequestWorkflowNotifier implements WorkflowNotifier
{
    public function notifyStepActivated(ProcessStep $step, array $userIds, array $context = []): void
    {
        // No module-specific real-time broadcast for leave requests yet.
    }

    public function inboxCountsForUser(string $userId): array
    {
        return [
            'pending_tasks' => 0,
            'pending_extensions' => 0,
            'pending_approvals' => 0,
            'total' => 0,
        ];
    }
}
