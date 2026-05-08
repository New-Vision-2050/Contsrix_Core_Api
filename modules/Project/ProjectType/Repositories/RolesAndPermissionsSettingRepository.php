<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\RolesAndPermissionsSetting;

/**
 * @property RolesAndPermissionsSetting $model
 * @method RolesAndPermissionsSetting findOneOrFail($id)
 * @method RolesAndPermissionsSetting findOneByOrFail(array $data)
 */
class RolesAndPermissionsSettingRepository extends BaseRepository
{
    public function __construct(RolesAndPermissionsSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?RolesAndPermissionsSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): RolesAndPermissionsSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): RolesAndPermissionsSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
