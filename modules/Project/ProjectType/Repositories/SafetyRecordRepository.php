<?php

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyRecordRepository extends BaseRepository
{
    public function __construct(SafetyRecord $model)
    {
        parent::__construct($model);
    }

    public function paginateForProject(
        string $projectId,
        array $filters = [],
        int $perPage = 15,
        ?string $sort = null,
    ): LengthAwarePaginator {
        $query = SafetyRecord::query()
            ->where('project_id', $projectId)
            ->with(['violations', 'morphable', 'assignedUser', 'contractor', 'media'])
            ->filter($filters);

        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    public function paginateForInbox(
        string $userId,
        array $filters = [],
        int $perPage = 15,
        ?string $sort = null,
    ): LengthAwarePaginator {
        unset($filters['assigned_user_id']);

        $query = SafetyRecord::query()
            ->where('assigned_user_id', $userId)
            ->with(['violations', 'morphable', 'assignedUser', 'contractor', 'project', 'media'])
            ->filter($filters);

        $this->applySorting($query, $sort);

        return $query->paginate($perPage);
    }

    private function applySorting(Builder $query, ?string $sort): void
    {
        if (! $sort) {
            $query->orderByDesc('created_at');

            return;
        }

        $direction = str_ends_with($sort, '_desc') ? 'desc' : 'asc';
        $column = str_replace(['_desc', '_asc'], '', $sort);

        $allowed = [
            'created_at',
            'date',
            'status',
            'percentage',
            'earned_score',
            'required_score',
            'consultant',
            'consultant_engineer',
        ];

        if (in_array($column, $allowed, true)) {
            $query->orderBy($column, $direction);
        } else {
            $query->orderByDesc('created_at');
        }
    }
}
