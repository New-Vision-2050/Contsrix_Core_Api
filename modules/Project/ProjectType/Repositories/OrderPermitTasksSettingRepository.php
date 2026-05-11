<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermitTasksSetting;

/**
 * @property OrderPermitTasksSetting $model
 * @method OrderPermitTasksSetting findOneOrFail($id)
 */
class OrderPermitTasksSettingRepository extends BaseRepository
{
    private const WITH = ['orderPermit', 'task'];

    public function __construct(OrderPermitTasksSetting $model)
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

    public function findByIdWithRelations(int $id): OrderPermitTasksSetting
    {
        return $this->model->with(self::WITH)->findOrFail($id);
    }
}
