<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\ContractorContractSetting;

/**
 * @property ContractorContractSetting $model
 * @method ContractorContractSetting findOneOrFail($id)
 * @method ContractorContractSetting findOneByOrFail(array $data)
 */
class ContractorContractSettingRepository extends BaseRepository
{
    public function __construct(ContractorContractSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?ContractorContractSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): ContractorContractSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): ContractorContractSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);
        return $setting->fresh();
    }
}
