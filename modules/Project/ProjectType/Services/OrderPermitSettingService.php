<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\DTO\UpdateOrderPermitSettingDTO;
use Modules\Project\ProjectType\Models\OrderPermitSetting;
use Modules\Project\ProjectType\Repositories\OrderPermitSettingRepository;

class OrderPermitSettingService
{
    public function __construct(
        private readonly OrderPermitSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): OrderPermitSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function getOrCreateByProjectTypeId(int $projectTypeId): OrderPermitSetting
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

    public function update(int $projectTypeId, UpdateOrderPermitSettingDTO $dto): OrderPermitSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
