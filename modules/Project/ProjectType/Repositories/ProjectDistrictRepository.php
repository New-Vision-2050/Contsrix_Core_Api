<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectDistrict;

/**
 * @property ProjectDistrict $model
 * @method ProjectDistrict findOneOrFail($id)
 */
class ProjectDistrictRepository extends BaseRepository
{
    public function __construct(ProjectDistrict $model)
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
