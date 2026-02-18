<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\AttachmentContractSetting;
use Modules\Project\ProjectType\Repositories\AttachmentContractSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateAttachmentContractSettingDTO;

class AttachmentContractSettingService
{
    public function __construct(
        private readonly AttachmentContractSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): AttachmentContractSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateAttachmentContractSettingDTO $dto): AttachmentContractSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
