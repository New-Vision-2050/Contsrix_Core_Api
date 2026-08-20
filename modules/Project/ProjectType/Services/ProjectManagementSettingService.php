<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\DTO\UpdateProjectManagementSettingDTO;
use Modules\Project\ProjectType\Models\ProjectManagementSetting;
use Modules\Project\ProjectType\Repositories\ProjectManagementSettingRepository;

class ProjectManagementSettingService
{
    public function __construct(private readonly ProjectManagementSettingRepository $repository) {}
    public function getOrCreateByProjectTypeId(int $projectTypeId): ProjectManagementSetting
    {
        return $this->repository->findByProjectTypeId($projectTypeId) ?? $this->repository->create(['project_type_id' => $projectTypeId, 'is_shown' => true]);
    }
    public function update(int $projectTypeId, UpdateProjectManagementSettingDTO $dto): ProjectManagementSetting
    { return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray()); }
}