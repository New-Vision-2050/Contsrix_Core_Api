<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Ecommerce\EcoAppSetting\Models\EcoFilterSetting;
use Illuminate\Support\Collection;

/**
 * @property EcoFilterSetting $model
 */
class EcoFilterSettingRepository extends BaseRepository
{
    public function __construct(EcoFilterSetting $model)
    {
        parent::__construct($model);
    }

    public function findByCompanyId(string $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)
            ->active()
            ->ordered()
            ->get();
    }

    public function findByCompanyAndKey(string $companyId, string $filterKey): ?EcoFilterSetting
    {
        return $this->model->where('company_id', $companyId)
            ->where('filter_key', $filterKey)
            ->first();
    }

    public function upsertByCompanyAndKey(string $companyId, string $filterKey, array $data): EcoFilterSetting
    {
        return $this->model->updateOrCreate(
            [
                'company_id' => $companyId,
                'filter_key' => $filterKey
            ],
            $data
        );
    }

    public function deleteByCompanyId(string $companyId): bool
    {
        return $this->model->where('company_id', $companyId)->delete();
    }
}
