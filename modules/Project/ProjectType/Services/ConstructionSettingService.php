<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\DTO\UpdateConstructionSettingDTO;
use Modules\Project\ProjectType\Models\ConstructionSetting;
use Modules\Project\ProjectType\Repositories\ConstructionSettingRepository;

class ConstructionSettingService
{
    public function __construct(private readonly ConstructionSettingRepository $repository) {}
    public function getOrCreateByProjectTypeId(int $projectTypeId): ConstructionSetting
    {
        return $this->repository->findByProjectTypeId($projectTypeId) ?? $this->repository->create(['project_type_id' => $projectTypeId, 'is_shown' => true]);
    }
    public function update(int $projectTypeId, UpdateConstructionSettingDTO $dto): ConstructionSetting
    { return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray()); }
}