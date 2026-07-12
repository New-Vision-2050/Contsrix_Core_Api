<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\ContractorSetting;

/**
 * @property ContractorSetting $model
 * @method ContractorSetting findOneOrFail($id)
 * @method ContractorSetting findOneByOrFail(array $data)
 */
class ContractorSettingRepository extends BaseRepository
{
    public function __construct(ContractorSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?ContractorSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): ContractorSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): ContractorSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);

        return $setting->fresh();
    }
}
