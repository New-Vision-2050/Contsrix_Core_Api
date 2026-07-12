<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\DTO\UpdateContractorSettingDTO;
use Modules\Project\ProjectType\Models\ContractorSetting;
use Modules\Project\ProjectType\Repositories\ContractorSettingRepository;

class ContractorSettingService
{
    public function __construct(
        private readonly ContractorSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): ContractorSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function getOrCreateByProjectTypeId(int $projectTypeId): ContractorSetting
    {
        $setting = $this->repository->findByProjectTypeId($projectTypeId);

        if (!$setting) {
            $setting = $this->repository->create([
                'project_type_id' => $projectTypeId,
                'is_shown' => true,
            ]);
        }

        return $setting;
    }

    public function update(int $projectTypeId, UpdateContractorSettingDTO $dto): ContractorSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
