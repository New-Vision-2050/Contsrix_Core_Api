<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\ProjectSharingSetting;
use Modules\Project\ProjectType\Repositories\ProjectSharingSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateProjectSharingSettingDTO;

class ProjectSharingSettingService
{
    public function __construct(
        private readonly ProjectSharingSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): ProjectSharingSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function getOrCreateByProjectTypeId(int $projectTypeId): ProjectSharingSetting
    {
        $setting = $this->repository->findByProjectTypeId($projectTypeId);
        
        if (!$setting) {
            $setting = $this->repository->create([
                'project_type_id' => $projectTypeId,
                'is_all_data_visible' => 0,
            ]);
        }
        
        return $setting;
    }

    public function update(int $projectTypeId, UpdateProjectSharingSettingDTO $dto): ProjectSharingSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
