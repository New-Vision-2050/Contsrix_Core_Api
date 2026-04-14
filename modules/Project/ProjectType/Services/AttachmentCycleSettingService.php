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

    public function update(int $projectTypeId, UpdateAttachmentCycleSettingDTO $dto): AttachmentCycleSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
