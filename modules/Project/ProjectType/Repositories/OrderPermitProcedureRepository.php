<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermitProcedure;

/**
 * @property OrderPermitProcedure $model
 * @method OrderPermitProcedure findOneOrFail($id)
 */
class OrderPermitProcedureRepository extends BaseRepository
{
    public function __construct(OrderPermitProcedure $model)
    {
        parent::__construct($model);
    }

    public function listByProjectTypeId(int $projectTypeId): Collection
    {
        return $this->model->where('project_type_id', $projectTypeId)->orderBy('id')->get();
    }
}
