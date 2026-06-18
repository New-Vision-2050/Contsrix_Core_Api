<?php

declare(strict_types=1);

namespace Modules\Process\Contracts;

use Modules\Process\Models\ProcessStep;

interface WorkflowNotifier
{
    public function notifyStepActivated(ProcessStep $step, array $userIds, array $context = []): void;

    /**
     * @return array{pending_tasks:int,pending_extensions:int,pending_approvals:int,total:int}
     */
    public function inboxCountsForUser(string $userId): array;
}
