<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\SafetyTaskSetting;

class SafetyTaskSettingRepository extends BaseRepository
{
    public function __construct(SafetyTaskSetting $model) { parent::__construct($model); }
    public function findByProjectTypeId(int $projectTypeId): ?SafetyTaskSetting { return $this->findOneBy(['project_type_id' => $projectTypeId]); }
    public function findByProjectTypeIdOrFail(int $projectTypeId): SafetyTaskSetting { return $this->findOneByOrFail(['project_type_id' => $projectTypeId]); }
    public function updateByProjectTypeId(int $projectTypeId, array $data): SafetyTaskSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}