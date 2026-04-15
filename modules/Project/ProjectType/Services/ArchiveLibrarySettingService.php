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

    public function getOrCreateByProjectTypeId(int $projectTypeId): ArchiveLibrarySetting
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

    public function update(int $projectTypeId, UpdateArchiveLibrarySettingDTO $dto): ArchiveLibrarySetting
    {
        return $this->repository->updateByProjectTypeId($projectTypeId, $dto->toArray());
    }
}
