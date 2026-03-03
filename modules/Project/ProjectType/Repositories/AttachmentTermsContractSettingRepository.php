<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\AttachmentTermsContractSetting;

/**
 * @property AttachmentTermsContractSetting $model
 * @method AttachmentTermsContractSetting findOneOrFail($id)
 * @method AttachmentTermsContractSetting findOneByOrFail(array $data)
 */
class AttachmentTermsContractSettingRepository extends BaseRepository
{
    public function __construct(AttachmentTermsContractSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?AttachmentTermsContractSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): AttachmentTermsContractSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): AttachmentTermsContractSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
