<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Modules\Project\ProjectType\Models\ArchiveLibrarySetting;
use Modules\Project\ProjectType\Repositories\ArchiveLibrarySettingRepository;
use Modules\Project\ProjectType\DTO\UpdateArchiveLibrarySettingDTO;

class ArchiveLibrarySettingService
{
    public function __construct(
        private readonly ArchiveLibrarySettingRepository $repository
    ) {
    }

    public function getByProjectTypeId(int $projectTypeId): ArchiveLibrarySetting
    {
        return $this->repository->findByProjectTypeIdOrFail($projectTypeId);
    }

    public function update(int $projectTypeId, UpdateArchiveLibrarySettingDTO $dto): ArchiveLibrarySetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
