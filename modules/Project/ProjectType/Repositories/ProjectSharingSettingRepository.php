<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\ProjectSharingSetting;

/**
 * @property ProjectSharingSetting $model
 * @method ProjectSharingSetting findOneOrFail($id)
 * @method ProjectSharingSetting findOneByOrFail(array $data)
 */
class ProjectSharingSettingRepository extends BaseRepository
{
    public function __construct(ProjectSharingSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?ProjectSharingSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): ProjectSharingSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): ProjectSharingSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
