<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Contracts\WorkflowNotifier;
use Modules\Process\Models\ProcessStep;

final class EmployeeTaskWorkflowNotifier implements WorkflowNotifier
{
    public function __construct(
        private readonly EmployeeTaskRequestService $requestService,
    ) {}

    public function notifyStepActivated(ProcessStep $step, array $userIds, array $context = []): void
    {
        $process = $step->process;
        $task = $process?->processable;

        if (! $task instanceof EmployeeTaskRequest) {
            return;
        }

        $templateStep = $step->procedureSettingStep;
        if ($templateStep === null) {
            return;
        }

        $task->load(['user']);

        event(new EmployeeTaskNotification($task, $templateStep, $userIds));
    }

    public function inboxCountsForUser(string $userId): array
    {
        return $this->requestService->getInboxCountsForAdmin($userId);
    }
}
