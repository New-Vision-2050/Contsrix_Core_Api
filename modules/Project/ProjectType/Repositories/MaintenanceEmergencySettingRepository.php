<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\MaintenanceEmergencySetting;

/**
 * @property MaintenanceEmergencySetting $model
 * @method MaintenanceEmergencySetting findOneOrFail($id)
 * @method MaintenanceEmergencySetting findOneByOrFail(array $data)
 */
class MaintenanceEmergencySettingRepository extends BaseRepository
{
    public function __construct(MaintenanceEmergencySetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?MaintenanceEmergencySetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): MaintenanceEmergencySetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): MaintenanceEmergencySetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
