<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;

class EmployeeTaskRepository
{
    public function create(array $data): EmployeeTaskRequest
    {
        return EmployeeTaskRequest::query()->create($data);
    }

    public function findById(string $id): ?EmployeeTaskRequest
    {
        return EmployeeTaskRequest::query()->find($id);
    }

    public function findByIdWithRelations(string $id): ?EmployeeTaskRequest
    {
        return EmployeeTaskRequest::query()
            ->with(['user', 'sessions', 'extensionRequests'])
            ->find($id);
    }

    public function paginateForEmployee(string $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeTaskRequest::query()
            ->where('user_id', $userId)
            ->with(['sessions'])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['task_date'])) {
            $query->whereDate('task_date', $filters['task_date']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('task_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('task_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Returns pending tasks where the given admin is either:
     *  - an explicit action-taker on the current step, OR
     *  - the step has no action-takers configured (open step).
     */
    public function paginateInboxForAdmin(string $adminId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeTaskRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('current_procedure_step_id')
            ->where(function ($q) use ($adminId) {
                $q->whereDoesntHave('currentProcedureStep.actionTakers')
                  ->orWhereHas('currentProcedureStep.actionTakers', fn ($at) => $at->where('user_id', $adminId));
            })
            ->with(['user', 'currentProcedureStep.actionTakers.user'])
            ->orderByDesc('created_at');

        if (!empty($filters['task_date'])) {
            $query->whereDate('task_date', $filters['task_date']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('task_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('task_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeTaskRequest::query()
            ->with(['user', 'sessions'])
            ->orderByDesc('created_at');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['task_date'])) {
            $query->whereDate('task_date', $filters['task_date']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('task_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('task_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function update(EmployeeTaskRequest $task, array $data): EmployeeTaskRequest
    {
        $task->update($data);
        return $task->fresh();
    }

    public function countForYear(int $year): int
    {
        return EmployeeTaskRequest::query()
            ->whereYear('created_at', $year)
            ->count();
    }

    public function generateSerialNumber(): string
    {
        $year     = Carbon::now()->format('Y');
        $sequence = $this->countForYear((int) $year) + 1;

        return 'TASK-' . $year . '-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
