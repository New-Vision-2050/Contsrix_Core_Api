<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskRequestDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

class EmployeeTaskRequestService
{
    public function __construct(
        private readonly EmployeeTaskRepository $repository,
    ) {}

    public function create(CreateEmployeeTaskRequestDTO $dto): EmployeeTaskRequest
    {
        $procedureSetting = ProcedureSetting::query()
            ->where('type', ProcedureSettingType::EmployeeTaskProcedure->value)
            ->with(['steps' => fn ($q) => $q->orderBy('step_order')])
            ->first();

        if (!$procedureSetting) {
            throw EmployeeTaskException::procedureSettingNotConfigured();
        }

        $firstStep = $procedureSetting->steps->first();

        if (!$firstStep) {
            throw EmployeeTaskException::noProcedureStepsConfigured();
        }

        $data                              = $dto->toArray();
        $data['serial_number']             = $this->repository->generateSerialNumber();
        $data['procedure_setting_id']      = $procedureSetting->id;
        $data['current_procedure_step_id'] = $firstStep->id;
        $data['company_id']                = tenant('id');

        return $this->repository->create($data);
    }

    public function list(string $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateForEmployee($userId, $filters, $perPage);
    }

    public function adminList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateForAdmin($filters, $perPage);
    }

    public function inbox(string $adminId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateInboxForAdmin($adminId, $filters, $perPage);
    }

    public function get(string $id): EmployeeTaskRequest
    {
        $task = $this->repository->findByIdWithRelations($id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        return $task;
    }

    public function cancelByEmployee(string $id, string $userId): EmployeeTaskRequest
    {
        $task = $this->repository->findById($id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($task->user_id !== $userId) {
            throw EmployeeTaskException::cannotCancel();
        }

        if ($task->status !== EmployeeTaskStatus::Pending->value) {
            throw EmployeeTaskException::notCancellable();
        }

        return $this->repository->update($task, [
            'status'             => EmployeeTaskStatus::Cancelled->value,
            'cancelled_by'       => $userId,
            'cancelled_at'       => now(),
        ]);
    }

    public function approve(string $id, string $adminId): EmployeeTaskRequest
    {
        $task = $this->repository->findById($id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($task->status !== EmployeeTaskStatus::Pending->value) {
            throw EmployeeTaskException::invalidStatus($task->status, EmployeeTaskStatus::Pending->value);
        }

        $currentStep = $this->resolveCurrentStep($task);

        $this->assertIsActionTaker($currentStep, $adminId);

        $nextStep = $this->findNextStep($task, $currentStep);

        if ($nextStep) {
            return $this->repository->update($task, [
                'current_procedure_step_id' => $nextStep->id,
            ]);
        }

        return $this->repository->update($task, [
            'status'                    => EmployeeTaskStatus::Approved->value,
            'approved_by'               => $adminId,
            'approved_at'               => now(),
            'current_procedure_step_id' => null,
        ]);
    }

    public function reject(string $id, string $adminId, string $reason): EmployeeTaskRequest
    {
        $task = $this->repository->findById($id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        if ($task->status !== EmployeeTaskStatus::Pending->value) {
            throw EmployeeTaskException::invalidStatus($task->status, EmployeeTaskStatus::Pending->value);
        }

        $currentStep = $this->resolveCurrentStep($task);

        $this->assertIsActionTaker($currentStep, $adminId);

        return $this->repository->update($task, [
            'status'                    => EmployeeTaskStatus::Rejected->value,
            'rejected_by'               => $adminId,
            'rejected_at'               => now(),
            'rejection_reason'          => $reason,
            'current_procedure_step_id' => null,
        ]);
    }

    private function resolveCurrentStep(EmployeeTaskRequest $task): ?ProcedureSettingStep
    {
        if (!$task->current_procedure_step_id) {
            return null;
        }

        return ProcedureSettingStep::with('actionTakers')
            ->find($task->current_procedure_step_id);
    }

    private function assertIsActionTaker(?ProcedureSettingStep $step, string $userId): void
    {
        if (!$step) {
            return;
        }

        if ($step->actionTakers->isEmpty()) {
            return;
        }

        if (!$step->actionTakers->contains('user_id', $userId)) {
            throw EmployeeTaskException::notAuthorizedForStep();
        }
    }

    private function findNextStep(EmployeeTaskRequest $task, ?ProcedureSettingStep $currentStep): ?ProcedureSettingStep
    {
        if (!$task->procedure_setting_id || !$currentStep) {
            return null;
        }

        return ProcedureSettingStep::query()
            ->where('procedure_setting_id', $task->procedure_setting_id)
            ->where('step_order', '>', $currentStep->step_order)
            ->orderBy('step_order')
            ->first();
    }

    public function cancelByAdmin(string $id, string $adminId, ?string $reason = null): EmployeeTaskRequest
    {
        $task = $this->repository->findById($id);

        if (!$task) {
            throw EmployeeTaskException::notFound();
        }

        $cancellableStatuses = [
            EmployeeTaskStatus::Approved->value,
            EmployeeTaskStatus::InProgress->value,
            EmployeeTaskStatus::Paused->value,
        ];

        if (!in_array($task->status, $cancellableStatuses, true)) {
            throw EmployeeTaskException::notCancellable();
        }

        return $this->repository->update($task, [
            'status'               => EmployeeTaskStatus::Cancelled->value,
            'cancelled_by'         => $adminId,
            'cancelled_at'         => now(),
            'cancellation_reason'  => $reason,
        ]);
    }
}
