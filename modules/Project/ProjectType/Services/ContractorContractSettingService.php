<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\ContractorContractSetting;
use Modules\Project\ProjectType\Repositories\ContractorContractSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateContractorContractSettingDTO;

class ContractorContractSettingService
{
    public function __construct(
        private readonly ContractorContractSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): ContractorContractSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateContractorContractSettingDTO $dto): ContractorContractSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
