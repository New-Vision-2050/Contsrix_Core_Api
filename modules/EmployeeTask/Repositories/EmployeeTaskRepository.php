<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Process\Enums\ProcessStatus;

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
            ->with([
                'user',
                'sessions',
                'extensionRequests',
                'media',
                'employeeTaskType',
                'currentProcedureStep.actionTakers.user',
                'approvalRequests.media',
                'projectNotification.media',
                'workResumptions.media',
            ])
            ->find($id);
    }

    public function paginateForEmployee(string $userId, array $filters, int $perPage = 15, ?string $sort = null): LengthAwarePaginator
    {
        $query = EmployeeTaskRequest::filter($filters)
            ->where('user_id', $userId)
            ->with(['sessions']);

        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    private function applySorting($query, ?string $sort): void
    {
        if (!$sort) {
            $query->orderByDesc('created_at');
            return;
        }

        $direction = str_ends_with($sort, '_desc') ? 'desc' : 'asc';
        $column    = str_replace(['_desc', '_asc'], '', $sort);

        $allowed = [
            'created_at',
            'task_date',
            'duration_hours',
            'title',
            'status',
        ];

        if (in_array($column, $allowed, true)) {
            $query->orderBy($column, $direction);
        } else {
            $query->orderByDesc('created_at');
        }
    }

    /**
     * Returns pending tasks where the given admin is either:
     *  - an explicit action-taker on the current step, OR
     *  - the step has no action-takers configured (open step).
     */
    public function paginateInboxForAdmin(string $adminId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeTaskRequest::query()
            ->where('employee_task_requests.status', 'pending')
            ->whereHas('processes', function ($q) use ($adminId) {
                $q->where('status', ProcessStatus::InProgress)
                  ->whereHas('steps', function ($q) use ($adminId) {
                      $q->where('status', 'pending')
                        ->where(function ($q) use ($adminId) {
                            $q->where('assigned_user_id', $adminId)
                              ->orWhereJsonContains('authorized_user_ids', $adminId);
                        });
                  });
            })
            ->with([
                'user',
                'processes' => fn ($q) => $q->where('status', ProcessStatus::InProgress)->with(['steps.procedureSettingStep', 'steps.assignedUser'])
            ])
            ->orderByDesc('created_at');

        if (!empty($filters['task_id'])) {
            $query->where('id', $filters['task_id']);
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

    /**
     * Non-paginated version of paginateInboxForAdmin — used by the combined inbox.
     */
    public function allInboxForAdmin(string $adminId, array $filters): Collection
    {
        $query = EmployeeTaskRequest::query()
            ->filter($filters)
            ->where('employee_task_requests.status', 'pending')
            ->whereHas('processes', function ($q) use ($adminId) {
                $q->where('status', ProcessStatus::InProgress)
                  ->whereHas('steps', function ($q) use ($adminId) {
                      $q->where('status', 'pending')
                        ->where(function ($q) use ($adminId) {
                            $q->where('assigned_user_id', $adminId)
                              ->orWhereJsonContains('authorized_user_ids', $adminId);
                        });
                  });
            })
            ->with([
                'user',
                'projectNotification',
                'project',
                'processes' => fn ($q) => $q->where('status', ProcessStatus::InProgress)->with(['steps.procedureSettingStep', 'steps.assignedUser'])
            ]);

            if (!empty($filters['duration_from'])) {
                    $query->where('duration_hours', '>=', (float) $filters['duration_from']);
                }
                if (!empty($filters['duration_to'])) {
                    $query->where('duration_hours', '<=', (float) $filters['duration_to']);
                }

        return $query->get();
    }

    /**
     * Returns tasks where the current user is the assigned employee (user_id = current user).
     * Used by the admin assigned-inbox view.
     */
    public function allAssignedForAdmin(string $userId, array $filters): Collection
    {
        $query = EmployeeTaskRequest::query()
            ->where('user_id', $userId)
            ->with([
                'user',
                'projectNotification',
                'project',
                'processes' => fn ($q) => $q->where('status', ProcessStatus::InProgress)->with(['steps.procedureSettingStep', 'steps.assignedUser'])
            ])
            ->orderByDesc('created_at');

        if (!empty($filters['task_id'])) {
            $query->where('id', $filters['task_id']);
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

        if (!empty($filters['duration_from'])) {
            $query->where('duration_hours', '>=', (float) $filters['duration_from']);
        }

        if (!empty($filters['duration_to'])) {
            $query->where('duration_hours', '<=', (float) $filters['duration_to']);
        }

        return $query->get();
    }

    /**
     * Non-paginated version of paginateExtensionInboxForAdmin — used by the combined inbox.
     */
    public function allExtensionInboxForAdmin(string $adminId, array $filters): Collection
    {
        $query = EmployeeTaskExtensionRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('current_procedure_step_id')
            ->where(function ($q) use ($adminId) {
                $q->whereDoesntHave('currentProcedureStep.actionTakers')
                  ->orWhereHas('currentProcedureStep.actionTakers', fn ($at) => $at->where('user_id', $adminId));
            })
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user']);

        if (!empty($filters['task_id'])) {
            $query->where('employee_task_request_id', $filters['task_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Returns pending extension requests where the given admin is either:
     *  - an explicit action-taker on the current step, OR
     *  - the step has no action-takers configured (open step).
     *
     * Mirrors paginateInboxForAdmin but for EmployeeTaskExtensionRequest.
     */
    public function paginateExtensionInboxForAdmin(string $adminId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeTaskExtensionRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('current_procedure_step_id')
            ->where(function ($q) use ($adminId) {
                $q->whereDoesntHave('currentProcedureStep.actionTakers')
                  ->orWhereHas('currentProcedureStep.actionTakers', fn ($at) => $at->where('user_id', $adminId));
            })
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])
            ->orderByDesc('created_at');

        if (!empty($filters['task_id'])) {
            $query->where('employee_task_request_id', $filters['task_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Non-paginated approval requests inbox for combined admin inbox.
     */
    public function allApprovalInboxForAdmin(string $adminId, array $filters): Collection
    {
        $query = EmployeeTaskApprovalRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('current_procedure_step_id')
            ->where(function ($q) use ($adminId) {
                $q->whereDoesntHave('currentProcedureStep.actionTakers')
                  ->orWhereHas('currentProcedureStep.actionTakers', fn ($at) => $at->where('user_id', $adminId));
            })
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user', 'media']);

        if (!empty($filters['task_id'])) {
            $query->where('employee_task_request_id', $filters['task_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Non-paginated end requests inbox for combined admin inbox.
     */
    public function allEndRequestInboxForAdmin(string $adminId, array $filters): Collection
    {
        $query = EmployeeTaskEndRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('current_procedure_step_id')
            ->where(function ($q) use ($adminId) {
                $q->whereDoesntHave('currentProcedureStep.actionTakers')
                  ->orWhereHas('currentProcedureStep.actionTakers', fn ($at) => $at->where('user_id', $adminId));
            })
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user']);

        if (!empty($filters['task_id'])) {
            $query->where('employee_task_request_id', $filters['task_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    /**
     * Non-paginated start requests inbox for combined admin inbox.
     */
    public function allStartRequestInboxForAdmin(string $adminId, array $filters): Collection
    {
        $query = EmployeeTaskStartRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('current_procedure_step_id')
            ->where(function ($q) use ($adminId) {
                $q->whereDoesntHave('currentProcedureStep.actionTakers')
                  ->orWhereHas('currentProcedureStep.actionTakers', fn ($at) => $at->where('user_id', $adminId));
            })
            ->with(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user']);

        if (!empty($filters['task_id'])) {
            $query->where('employee_task_request_id', $filters['task_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    public function findActiveTaskForUser(string $userId): ?EmployeeTaskRequest
    {
        return EmployeeTaskRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', EmployeeTaskStatus::activeStatuses())
            ->first();
    }

    public function update(EmployeeTaskRequest $task, array $data): EmployeeTaskRequest
    {
        $task->update($data);
        return $task->fresh();
    }

    public function getFilterMetadata(string $userId, array $filters = []): array
    {
        $taskDate = $filters['task_date'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to']   ?? null;

        $statusQuery = EmployeeTaskRequest::query()
            ->where('user_id', $userId);
        $this->applyDateFilters($statusQuery, $taskDate, $dateFrom, $dateTo);
        $statusCounts = $statusQuery
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $projectQuery = EmployeeTaskRequest::query()
            ->where('user_id', $userId)
            ->whereNotNull('project_id');
        $this->applyDateFilters($projectQuery, $taskDate, $dateFrom, $dateTo);
        $projectRows = $projectQuery
            ->join('projects', 'employee_task_requests.project_id', '=', 'projects.id')
            ->selectRaw('projects.id as project_id, projects.name as project_name, COUNT(*) as count')
            ->groupBy('projects.id', 'projects.name')
            ->get();

        $projectCounts = [];
        foreach ($projectRows as $row) {
            $projectCounts[] = [
                'id'    => $row->project_id,
                'name'  => $row->project_name,
                'count' => (int) $row->count,
            ];
        }

        $durationQuery = EmployeeTaskRequest::query()
            ->where('user_id', $userId);
        $this->applyDateFilters($durationQuery, $taskDate, $dateFrom, $dateTo);
        $durationStats = $durationQuery
            ->selectRaw('MIN(duration_hours) as min_hours, MAX(duration_hours) as max_hours')
            ->first();

        return [
            'status_counts'  => $statusCounts,
            'project_counts' => $projectCounts,
            'duration'       => [
                'min_hours' => $durationStats?->min_hours ? (float) $durationStats->min_hours : null,
                'max_hours' => $durationStats?->max_hours ? (float) $durationStats->max_hours : null,
            ],
        ];
    }

    private function applyDateFilters($query, ?string $taskDate, ?string $dateFrom, ?string $dateTo): void
    {
        if ($taskDate) {
            $query->whereDate('task_date', $taskDate);
            return;
        }
        if ($dateFrom) {
            $query->whereDate('task_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('task_date', '<=', $dateTo);
        }
    }

    public function generateSerialNumber(): string
    {
        $year      = Carbon::now()->format('Y');
        $companyId = tenant('id');
        $prefix    = "TASK-{$year}-";

        $max = DB::table('employee_task_requests')
            ->where('company_id', $companyId)
            ->where('serial_number', 'like', $prefix . '%')
            ->max(DB::raw('CAST(SUBSTRING(serial_number, ' . (strlen($prefix) + 1) . ') AS UNSIGNED)'));

        $sequence = ((int) $max) + 1;

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
