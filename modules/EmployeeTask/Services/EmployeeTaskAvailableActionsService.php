<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\InternalProcedureAvailableActionsService;

/**
 * Thin wrapper around InternalProcedureAvailableActionsService for EmployeeTask.
 * Resolves the task's company/branch context and delegates all filtering logic
 * to the generic central service.
 */
final class EmployeeTaskAvailableActionsService
{
    public function __construct(
        private readonly EmployeeTaskRepository $repository,
        private readonly InternalProcedureAvailableActionsService $actionsService,
    ) {}

    /**
     * @return list<array>
     */
    public function forTask(string $taskId): array
    {
        $task = $this->repository->findById($taskId);

        if (! $task) {
            throw EmployeeTaskException::notFound();
        }

        $task->loadMissing('user.userProfessionalData');
        $branchId = $task->user?->userProfessionalData?->branch_id !== null
            ? (string) $task->user->userProfessionalData->branch_id
            : null;

        return $this->actionsService->forProcessable(
            'employee_task',
            $task->id,
            ProcedureSettingType::EmployeeTask->value,
            $task->company_id,
            $branchId,
            $task->procedure_setting_id,
        );
    }
}
