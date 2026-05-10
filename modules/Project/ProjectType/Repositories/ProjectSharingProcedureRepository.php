<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingProcedure;

/**
 * @property ProjectSharingProcedure $model
 * @method ProjectSharingProcedure findOneOrFail($id)
 */
class ProjectSharingProcedureRepository extends BaseRepository
{
    public function __construct(ProjectSharingProcedure $model)
    {
        parent::__construct($model);
    }

    public function listByProjectTypeId(int $projectTypeId): Collection
    {
        return $this->model->where('project_type_id', $projectTypeId)->orderBy('id')->get();
    }
}
