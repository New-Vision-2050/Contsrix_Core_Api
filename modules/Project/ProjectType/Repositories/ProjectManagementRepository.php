<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectManagement;

/**
 * @property ProjectManagement $model
 * @method ProjectManagement findOneOrFail($id)
 */
class ProjectManagementRepository extends BaseRepository
{
    public function __construct(ProjectManagement $model)
    {
        parent::__construct($model);
    }

    public function list(array $conditions = [], string $orderBy = 'id', string $sortBy = 'asc'): Collection
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->orderBy($orderBy, $sortBy)->get();
    }
}
