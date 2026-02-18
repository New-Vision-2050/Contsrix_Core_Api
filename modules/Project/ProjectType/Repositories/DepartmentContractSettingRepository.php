<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\DepartmentContractSetting;

/**
 * @property DepartmentContractSetting $model
 * @method DepartmentContractSetting findOneOrFail($id)
 * @method DepartmentContractSetting findOneByOrFail(array $data)
 */
class DepartmentContractSettingRepository extends BaseRepository
{
    public function __construct(DepartmentContractSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?DepartmentContractSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): DepartmentContractSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): DepartmentContractSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
