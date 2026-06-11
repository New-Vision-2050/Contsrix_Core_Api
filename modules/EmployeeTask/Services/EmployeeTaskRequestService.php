<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskRequestDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Events\EmployeeTaskNotification;
use Modules\EmployeeTask\Events\InboxCountsUpdated;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\Process\Services\ProcessWorkflowService;

class EmployeeTaskRequestService
{
    public function __construct(
        private readonly EmployeeTaskRepository   $repository,
        private readonly ProcedureWorkflowService $workflow,
        private readonly ProcessWorkflowService   $processService,
    ) {}


    public function create(CreateEmployeeTaskRequestDTO $dto): EmployeeTaskRequest
    {
        $procedureType = ProcedureSettingType::EmployeeTaskRequest->value;
        $preview       = $this->workflow->getApprovalResponsibles($procedureType, $dto->userId);

        $data                  = $dto->toArray();
        $data['serial_number'] = $this->repository->generateSerialNumber();
        $data['company_id']    = tenant('id');

        if ($preview['auto_approve']) {
            $data['status']      = EmployeeTaskStatus::Approved->value;
            $data['approved_at'] = now();
            return $this->repository->create($data);
        }

        $data['status'] = EmployeeTaskStatus::Pending->value;
        $task = $this->repository->create($data);

        $this->createProcessesForTask($task);

        return $task;
    }

    private function createProcessesForTask(EmployeeTaskRequest $task): void
    {
        $user     = $task->user->load('userProfessionalData');
        $branchId = $user->userProfessionalData?->branch_id;

        $settings = ProcedureSetting::query()
            ->where('type', ProcedureSettingType::EmployeeTaskRequest->value)
            ->where('company_id', $task->company_id)
            ->whereHas('workFlow', function ($q) use ($branchId) {
                $q->whereHas('managementHierarchies', function ($q) use ($branchId) {
                    $q->where('management_hierarchies.id', $branchId);
                });
            })
            ->orderBy('sort_order')
            ->get();

        $activeProcess = $this->processService->createProcessesFromSettings(
            ProcedureSettingType::EmployeeTaskRequest->value,
            $task->id,
            $settings,
            $task->user_id,
        );

        if (!$activeProcess) {
            $task->update([
                'status'      => EmployeeTaskStatus::Approved->value,
                'approved_at' => now(),
            ]);
            return;
        }

        $currentStep = $this->processService->getCurrentStep($activeProcess);
        if (!$currentStep) return;

        $task->update([
            'approval_responsible_id'     => $currentStep->assigned_user_id,
            'current_procedure_step_id'   => $currentStep->step_id,
        ]);

        $dummyStep = new ProcedureSettingStep([
            'id'         => $currentStep->step_id,
            'name'       => null,
            'step_order' => $currentStep->template_step_order,
        ]);
        $dummyStep->setRelation('actionTakers', collect([
            (object) ['user_id' => $currentStep->assigned_user_id],
        ]));

        $this->broadcastTaskNotification($task, $dummyStep);
        $this->broadcastInboxCounts($dummyStep);
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
// dd($id, $adminId);
        return DB::transaction(function () use ($id, $adminId, $task): EmployeeTaskRequest {
            $process = Process::query()
                ->where('processable_type', ProcedureSettingType::EmployeeTaskRequest->value)
                ->where('processable_id', $task->id)
                ->where('status', ProcessStatus::InProgress)
                ->firstOrFail();
// dd($process);
            $step = ProcessStep::query()
                ->where('process_id', $process->id)
                ->where('assigned_user_id', $adminId)
                ->where('status', ProcessStepStatus::Pending)
                ->firstOrFail();

            $this->processService->approveStep($step->id);

            return $task->fresh();
        });
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

        return DB::transaction(function () use ($id, $adminId, $reason, $task): EmployeeTaskRequest {
            $process = Process::query()
                ->where('processable_type', ProcedureSettingType::EmployeeTaskRequest->value)
                ->where('processable_id', $task->id)
                ->where('status', ProcessStatus::InProgress)
                ->firstOrFail();

            $step = ProcessStep::query()
                ->where('process_id', $process->id)
                ->where('assigned_user_id', $adminId)
                ->where('status', ProcessStepStatus::Pending)
                ->firstOrFail();

            $this->processService->rejectStep($step->id);

            return $task->fresh();
        });
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

    public function inboxAll(string $adminId, array $filters = []): Collection
    {
        return $this->repository->allInboxForAdmin($adminId, $filters);
    }

    public function inboxAllApprovals(string $adminId, array $filters = []): Collection
    {
        return $this->repository->allApprovalInboxForAdmin($adminId, $filters);
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
            'status'       => EmployeeTaskStatus::Cancelled->value,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
        ]);
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
            'status'             => EmployeeTaskStatus::Cancelled->value,
            'cancelled_by'       => $adminId,
            'cancelled_at'       => now(),
            'cancellation_reason'=> $reason,
        ]);
    }


    private function broadcastTaskNotification(EmployeeTaskRequest $task, ProcedureSettingStep $currentStep): void
    {
        $task->load(['user']);
        $currentStep->load(['actionTakers.user']);

        \Log::info('Broadcasting EmployeeTaskNotification', [
            'task_id' => $task->id,
            'step_id' => $currentStep->id,
        ]);

        event(new EmployeeTaskNotification($task, $currentStep));
    }

    public function getInboxCountsForAdmin(string $adminId, array $filters = []): array
    {
        $tasks      = $this->repository->allInboxForAdmin($adminId, $filters)->count();
        $extensions = $this->repository->allExtensionInboxForAdmin($adminId, $filters)->count();
        $approvals  = $this->repository->allApprovalInboxForAdmin($adminId, $filters)->count();

        return [
            'pending_tasks'      => $tasks,
            'pending_extensions' => $extensions,
            'pending_approvals'  => $approvals,
            'total'              => (int) ($tasks + $extensions + $approvals),
        ];
    }

    public function broadcastInboxCounts(ProcedureSettingStep $step, array $filters = []): void
    {
        \Log::info('Broadcasting InboxCountsUpdated', [
            'step_id' => $step->id,
            'action_takers_count' => $step->actionTakers->count(),
        ]);

        foreach ($step->actionTakers as $taker) {
            $counts = $this->getInboxCountsForAdmin($taker->user_id, $filters);
            event(new InboxCountsUpdated(
                userId: $taker->user_id,
                pendingTasks: $counts['pending_tasks'],
                pendingExtensions: $counts['pending_extensions'],
                pendingApprovals: $counts['pending_approvals'],
                total: $counts['total'],
            ));
        }
    }
}
