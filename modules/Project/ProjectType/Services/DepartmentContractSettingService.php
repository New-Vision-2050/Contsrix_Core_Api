<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\DepartmentContractSetting;
use Modules\Project\ProjectType\Repositories\DepartmentContractSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateDepartmentContractSettingDTO;

class DepartmentContractSettingService
{
    public function __construct(
        private readonly DepartmentContractSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): DepartmentContractSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateDepartmentContractSettingDTO $dto): DepartmentContractSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
