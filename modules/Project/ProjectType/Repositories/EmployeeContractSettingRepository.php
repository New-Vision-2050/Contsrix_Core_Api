<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\EmployeeContractSetting;

/**
 * @property EmployeeContractSetting $model
 * @method EmployeeContractSetting findOneOrFail($id)
 * @method EmployeeContractSetting findOneByOrFail(array $data)
 */
class EmployeeContractSettingRepository extends BaseRepository
{
    public function __construct(EmployeeContractSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?EmployeeContractSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): EmployeeContractSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): EmployeeContractSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
