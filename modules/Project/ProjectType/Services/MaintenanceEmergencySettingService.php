<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\MaintenanceEmergencySetting;
use Modules\Project\ProjectType\Repositories\MaintenanceEmergencySettingRepository;
use Modules\Project\ProjectType\DTO\UpdateMaintenanceEmergencySettingDTO;

class MaintenanceEmergencySettingService
{
    public function __construct(
        private readonly MaintenanceEmergencySettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): MaintenanceEmergencySetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function getOrCreateByProjectTypeId(int $projectTypeId): MaintenanceEmergencySetting
    {
        $setting = $this->repository->findByProjectTypeId($projectTypeId);

        if (!$setting) {
            $setting = $this->repository->create([
                'project_type_id' => $projectTypeId,
                'is_shown' => 1,
            ]);
        }

        return $setting;
    }

    public function update(int $projectTypeId, UpdateMaintenanceEmergencySettingDTO $dto): MaintenanceEmergencySetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
