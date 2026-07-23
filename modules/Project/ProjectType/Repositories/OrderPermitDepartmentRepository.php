<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;

/**
 * @property OrderPermitDepartment $model
 * @method OrderPermitDepartment findOneOrFail($id)
 */
class OrderPermitDepartmentRepository extends BaseRepository
{
    public function __construct(OrderPermitDepartment $model)
    {
        parent::__construct($model);
    }

    public function list(array $conditions = [], string $orderBy = 'id', string $sortBy = 'asc'): Collection
    {
        $query = $this->model->orderBy($orderBy, $sortBy);

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get();
    }
}
