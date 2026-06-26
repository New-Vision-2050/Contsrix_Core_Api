<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class ProjectNotificationRepository
{
    public function create(array $data): ProjectNotification
    {
        return ProjectNotification::query()->create($data);
    }

    public function findById(string $id): ?ProjectNotification
    {
        return ProjectNotification::query()
            ->with(['project', 'assignedUser', 'creator', 'employeeTask', 'media'])
            ->find($id);
    }

    public function paginated(array $filters, int $perPage = 15, ?string $sort = null): LengthAwarePaginator
    {
        $query = ProjectNotification::filter($filters)
            ->with(['assignedUser', 'project']);

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
