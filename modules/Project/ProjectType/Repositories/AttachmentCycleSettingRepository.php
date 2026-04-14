<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\AttachmentCycleSetting;

/**
 * @property AttachmentCycleSetting $model
 * @method AttachmentCycleSetting findOneOrFail($id)
 * @method AttachmentCycleSetting findOneByOrFail(array $data)
 */
class AttachmentCycleSettingRepository extends BaseRepository
{
    public function __construct(AttachmentCycleSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?AttachmentCycleSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): AttachmentCycleSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): AttachmentCycleSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
