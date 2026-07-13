<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Project\ProjectType\Models\OrderPermitSetting;

/**
 * @property OrderPermitSetting $model
 * @method OrderPermitSetting findOneOrFail($id)
 * @method OrderPermitSetting findOneByOrFail(array $data)
 */
class OrderPermitSettingRepository extends BaseRepository
{
    public function __construct(OrderPermitSetting $model)
    {
        parent::__construct($model);
    }

    public function findByProjectTypeId(int $projectTypeId): ?OrderPermitSetting
    {
        return $this->findOneBy(['project_type_id' => $projectTypeId]);
    }

    public function findByProjectTypeIdOrFail(int $projectTypeId): OrderPermitSetting
    {
        return $this->findOneByOrFail(['project_type_id' => $projectTypeId]);
    }

    public function updateByProjectTypeId(int $projectTypeId, array $data): OrderPermitSetting
    {
        $setting = $this->findByProjectTypeIdOrFail($projectTypeId);
        $setting->update($data);

        return $setting->fresh();
    }
}
