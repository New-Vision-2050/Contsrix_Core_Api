<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\AttachmentTermsContractSetting;
use Modules\Project\ProjectType\Repositories\AttachmentTermsContractSettingRepository;
use Modules\Project\ProjectType\DTO\UpdateAttachmentTermsContractSettingDTO;

class AttachmentTermsContractSettingService
{
    public function __construct(
        private readonly AttachmentTermsContractSettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): AttachmentTermsContractSetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateAttachmentTermsContractSettingDTO $dto): AttachmentTermsContractSetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
