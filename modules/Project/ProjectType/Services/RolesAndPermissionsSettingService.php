<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\RolesAndPermissionsSetting;
use Modules\Project\ProjectType\Repositories\RolesAndPermissionsSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateRolesAndPermissionsSettingDTO;

class RolesAndPermissionsSettingService
{
    public function __construct(
        private readonly RolesAndPermissionsSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): RolesAndPermissionsSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function getOrCreateByProjectTypeId(int $projectTypeId): RolesAndPermissionsSetting
    {
        $setting = $this->repository->findByProjectTypeId($projectTypeId);
        
        if (!$setting) {
            $setting = $this->repository->create([
                'project_type_id' => $projectTypeId,
                'is_enabled' => 0,
            ]);
        }
        
        return $setting;
    }

    public function update(int $projectTypeId, UpdateRolesAndPermissionsSettingDTO $dto): RolesAndPermissionsSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
