<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\AttachmentCycleSetting;
use Modules\Project\ProjectType\Repositories\AttachmentCycleSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateAttachmentCycleSettingDTO;

class AttachmentCycleSettingService
{
    public function __construct(
        private readonly AttachmentCycleSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): AttachmentCycleSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function getOrCreateByProjectTypeId(int $projectTypeId): AttachmentCycleSetting
    {
        $setting = $this->repository->findByProjectTypeId($projectTypeId);
        
        if (!$setting) {
            $setting = $this->repository->create([
                'project_type_id' => $projectTypeId,
                'is_all_data_visible' => 0,
            ]);
        }
        
        return $setting;
    }

    public function update(int $projectTypeId, UpdateAttachmentCycleSettingDTO $dto): AttachmentCycleSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
