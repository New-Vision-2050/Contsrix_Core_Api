<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\AttachmentContractSetting;

/**
 * @property AttachmentContractSetting $model
 * @method AttachmentContractSetting findOneOrFail($id)
 * @method AttachmentContractSetting findOneByOrFail(array $data)
 */
class AttachmentContractSettingRepository extends BaseRepository
{
    public function __construct(AttachmentContractSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?AttachmentContractSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): AttachmentContractSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): AttachmentContractSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
