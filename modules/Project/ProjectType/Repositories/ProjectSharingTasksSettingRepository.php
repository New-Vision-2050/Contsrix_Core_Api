<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectSharingTasksSetting;

/**
 * @property ProjectSharingTasksSetting $model
 * @method ProjectSharingTasksSetting findOneOrFail($id)
 */
class ProjectSharingTasksSettingRepository extends BaseRepository
{
    private const WITH = ['workOrder', 'task'];

    public function __construct(ProjectSharingTasksSetting $model)
    {
        parent::__construct($model);
    }

    public function listByProjectTypeId(int $projectTypeId): Collection
    {
        return $this->model
            ->with(self::WITH)
            ->where('project_type_id', $projectTypeId)
            ->orderBy('id')
            ->get();
    }

    public function findByIdWithRelations(int $id): ProjectSharingTasksSetting
    {
        return $this->model->with(self::WITH)->findOrFail($id);
    }
}
