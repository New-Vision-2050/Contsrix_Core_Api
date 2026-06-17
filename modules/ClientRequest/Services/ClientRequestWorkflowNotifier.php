<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Modules\Process\Contracts\WorkflowNotifier;
use Modules\Process\Models\ProcessStep;

final class ClientRequestWorkflowNotifier implements WorkflowNotifier
{
    public function notifyStepActivated(ProcessStep $step, array $userIds, array $context = []): void
    {
        // ClientRequest currently has no dedicated step-activation broadcast event.
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
