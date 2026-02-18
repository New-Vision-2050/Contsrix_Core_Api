<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\EmployeeContractSetting;
use Modules\Project\ProjectType\Repositories\EmployeeContractSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateEmployeeContractSettingDTO;

class EmployeeContractSettingService
{
    public function __construct(
        private readonly EmployeeContractSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): EmployeeContractSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateEmployeeContractSettingDTO $dto): EmployeeContractSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
