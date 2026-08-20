<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\ProjectManagementSetting;

class ProjectManagementSettingRepository extends BaseRepository
{
    public function __construct(ProjectManagementSetting $model) { parent::__construct($model); }
    public function findByProjectTypeId(int $projectTypeId): ?ProjectManagementSetting { return $this->findOneBy(['project_type_id' => $projectTypeId]); }
    public function findByProjectTypeIdOrFail(int $projectTypeId): ProjectManagementSetting { return $this->findOneByOrFail(['project_type_id' => $projectTypeId]); }
    public function updateByProjectTypeId(int $projectTypeId, array $data): ProjectManagementSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}