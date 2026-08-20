<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\DTO\UpdateSafetyTaskSettingDTO;
use Modules\Project\ProjectType\Models\SafetyTaskSetting;
use Modules\Project\ProjectType\Repositories\SafetyTaskSettingRepository;

class SafetyTaskSettingService
{
    public function __construct(private readonly SafetyTaskSettingRepository $repository) {}
    public function getOrCreateByProjectTypeId(int $projectTypeId): SafetyTaskSetting
    {
        return $this->repository->findByProjectTypeId($projectTypeId) ?? $this->repository->create(['project_type_id' => $projectTypeId, 'is_shown' => true]);
    }
    public function update(int $projectTypeId, UpdateSafetyTaskSettingDTO $dto): SafetyTaskSetting
    { return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray()); }
}