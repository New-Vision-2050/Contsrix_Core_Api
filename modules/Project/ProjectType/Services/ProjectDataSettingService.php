<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\ProjectDataSetting;
use Modules\Project\ProjectType\Repositories\ProjectDataSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateProjectDataSettingDTO;

class ProjectDataSettingService
{
    public function __construct(
        private readonly ProjectDataSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): ProjectDataSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateProjectDataSettingDTO $dto): ProjectDataSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
