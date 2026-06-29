<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class ProjectNotificationRepository
{
    public function __construct(
        private readonly WorkflowEngine $engine,
    ) {}
    public function create(array $data): ProjectNotification
    {
        return ProjectNotification::query()->create($data);
    }

    public function findById(string $id): ?ProjectNotification
    {
        return ProjectNotification::query()
            ->with([
                'project',
                'contractor',
                'assignedUser',
                'creator',
                'media',
                'employeeTask.user',
                'employeeTask.employeeTaskType',
                'employeeTask.media',
                'employeeTask.sessions',
                'employeeTask.extensionRequests',
                'employeeTask.currentProcedureStep.actionTakers.user',
                'employeeTask.createProjectNotificationTaskProcedureSetting',
            ])
            ->find($id);
    }

    public function paginated(array $filters, int $perPage = 15, ?string $sort = null): LengthAwarePaginator
    {
        $query = ProjectNotification::filter($filters)
            ->with(['assignedUser', 'project', 'contractor', 'employeeTask.user', 'employeeTask.createProjectNotificationTaskProcedureSetting']);

        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    /**
     * Mobile "my-tasks" query. The assigned_user_id filter is applied directly
     * because the EloquentFilter relation handling can drop it when the
     * assignedUser relation is whitelisted in $relations.
     */
    public function paginatedForMyTasks(array $filters, string $userId, int $perPage = 15, ?string $sort = null): LengthAwarePaginator
    {
        $query = ProjectNotification::query()
            ->where('assigned_user_id', $userId)
            ->filter($filters)
            ->with(['assignedUser', 'project', 'contractor', 'employeeTask.user', 'employeeTask.createProjectNotificationTaskProcedureSetting']);

        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    /**
     * Mobile inbox query. Bypasses the EloquentFilter status filter so
     * notifications with any top-level status appear as long as they have
     * a pending workflow process assigned to the user.
     */
    public function paginatedForInbox(array $filters, string $userId, int $perPage = 15, ?string $sort = null): LengthAwarePaginator
    {
        $query = ProjectNotification::query();

        // Apply non-status filters manually (project, search, dates, etc.).
        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (! empty($filters['notification_type'])) {
            $query->where('notification_type', $filters['notification_type']);
        }
        if (! empty($filters['work_type'])) {
            $query->where('work_type', $filters['work_type']);
        }
        if (! empty($filters['contractor_name'])) {
            $query->where('contractor_name', 'like', '%' . $filters['contractor_name'] . '%');
        }
        if (! empty($filters['contractor_id'])) {
            $query->where('contractor_id', $filters['contractor_id']);
        }
        if (! empty($filters['task_date'])) {
            $query->whereDate('task_date', $filters['task_date']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('task_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('task_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('notification_number', 'like', '%' . $term . '%')
                  ->orWhere('contractor_name', 'like', '%' . $term . '%')
                  ->orWhere('work_description', 'like', '%' . $term . '%')
                  ->orWhere('repair_point', 'like', '%' . $term . '%');
            });
        }

        // Core inbox filter: must have an in-progress process with a pending
        // step assigned to (or authorized for) the current user.
        $query->whereHas(
            'employeeTask.processes',
            $this->engine->pendingProcessScopeForUser(
                ProcedureSettingType::ProjectNotificationTask->value,
                $userId,
            ),
        );

        $query->with([
            'assignedUser',
            'project',
            'contractor',
            'employeeTask.user',
            'employeeTask.createProjectNotificationTaskProcedureSetting',
            'employeeTask.processes.procedureSetting',
            'employeeTask.processes.steps',
        ]);

        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    public function update(string $id, array $data): bool
    {
        return ProjectNotification::query()->where('id', $id)->update($data);
    }

    public function delete(string $id): bool
    {
        return ProjectNotification::query()->where('id', $id)->delete();
    }

    public function generateNotificationNumber(string $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            $year = now()->format('Y');
            $counter = DB::table('project_notification_counters')
                ->where('company_id', $companyId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($counter) {
                DB::table('project_notification_counters')
                    ->where('id', $counter->id)
                    ->increment('sequence');
                $sequence = $counter->sequence + 1;
            } else {
                $id = (string) Str::uuid();
                DB::table('project_notification_counters')->insert([
                    'id' => $id,
                    'company_id' => $companyId,
                    'year' => $year,
                    'sequence' => 1,
                ]);
                $sequence = 1;
            }

            return "NTF-{$year}-" . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }

    private function applySorting($query, ?string $sort): void
    {
        if (!$sort) {
            $query->orderByDesc('created_at');
            return;
        }

        $direction = str_ends_with($sort, '_desc') ? 'desc' : 'asc';
        $column = str_replace(['_desc', '_asc'], '', $sort);

        $allowed = ['created_at', 'task_date', 'notification_number', 'status', 'severity'];

        if (in_array($column, $allowed, true)) {
            $query->orderBy($column, $direction);
        } else {
            $query->orderByDesc('created_at');
        }
    }
}
