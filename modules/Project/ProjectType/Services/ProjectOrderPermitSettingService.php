<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\DTO\UpdateProjectOrderPermitSettingDTO;
use Modules\Project\ProjectType\Models\ProjectOrderPermitSetting;
use Modules\Project\ProjectType\Repositories\ProjectOrderPermitSettingRepository;

class ProjectOrderPermitSettingService
{
    public function __construct(private readonly ProjectOrderPermitSettingRepository $repository) {}

    public function getOrCreateByProjectTypeId(int $projectTypeId): ProjectOrderPermitSetting
    {
        return $this->repository->findByProjectTypeId($projectTypeId) ?? $this->repository->create([
            'project_type_id' => $projectTypeId,
            'is_shown' => true,
        ]);
    }

    public function update(int $projectTypeId, UpdateProjectOrderPermitSettingDTO $dto): ProjectOrderPermitSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}