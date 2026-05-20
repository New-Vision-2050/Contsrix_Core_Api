<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\CreateExtensionRequestDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;

final class EmployeeTaskExtensionService
{
    public function __construct(
        private readonly EmployeeTaskRepository $taskRepo,
        private readonly ProcedureWorkflowService $workflow,
    ) {}

    /**
     * Request an extension for an active task.
     *
     * Integrates with workflow system:
     * - If auto-approved, extension created with status = approved
     * - If workflow required, extension created with pending status + workflow step
     */
    public function requestExtension(CreateExtensionRequestDTO $dto): EmployeeTaskExtensionRequest
    {
        $task = $this->taskRepo->findById($dto->taskId);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        $allowedStatuses = [EmployeeTaskStatus::InProgress->value, EmployeeTaskStatus::Paused->value];
        if (!in_array($task->status, $allowedStatuses, true)) {
            throw EmployeeTaskException::extensionNotAllowed();
        }

        if ($task->hasPendingExtension()) {
            throw EmployeeTaskException::pendingExtensionExists();
        }

        $procedureType = ProcedureSettingType::EmployeeTaskExtensionRequest->value;
        $preview = $this->workflow->getApprovalResponsibles($procedureType);

        return DB::transaction(function () use ($task, $dto, $preview, $procedureType): EmployeeTaskExtensionRequest {
            $data = array_merge(
                $dto->toArray(),
                ['company_id' => $task->company_id]
            );

            if ($preview['auto_approve']) {
                $data['status'] = 'approved';
                $data['procedure_setting_id'] = null;
                $data['current_procedure_step_id'] = null;
                $data['reviewed_at'] = now();
            } else {
                $firstStep = $this->workflow->resolveFirstStep($procedureType);
                $data['status'] = 'pending';
                $data['procedure_setting_id'] = $firstStep->procedure_setting_id;
                $data['current_procedure_step_id'] = $firstStep->id;
            }

            $extension = EmployeeTaskExtensionRequest::query()->create($data);
            $task->update(['last_extension_status' => 'extension_pending']);

            return $extension;
        });
    }

    /**
     * List all pending extension requests (admin inbox).
     *
     * Ordered by creation date (newest first).
     */
    public function listPending(int $perPage = 15)
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('status', 'pending')
            ->with([
                'task',
                'requestedByUser',
                'currentProcedureStep.actionTakers.user',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * List all extension requests for a specific task.
     *
     * Useful for viewing history of extensions on a single task.
     */
    public function listForTask(string $taskId): Collection
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('employee_task_request_id', $taskId)
            ->with([
                'requestedByUser',
                'reviewedByUser',
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Retrieve a single extension request with full eager loading.
     *
     * @throws EmployeeTaskException
     */
    public function get(string $extensionId): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()
            ->with([
                'task',
                'requestedByUser',
                'reviewedByUser',
                'currentProcedureStep.actionTakers.user',
            ])
            ->find($extensionId);

        if (!$extension) {
            throw EmployeeTaskException::extensionNotFound();
        }

        return $extension;
    }
}

