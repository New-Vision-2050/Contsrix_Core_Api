<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Ecommerce\EcoAppSetting\Models\EcoBannerSetting;

/**
 * @property EcoBannerSetting $model
 */
class EcoBannerSettingRepository extends BaseRepository
{
    public function __construct(EcoBannerSetting $model)
    {
        parent::__construct($model);
    }

    public function findByCompanyId(string $companyId): ?EcoBannerSetting
    {
        return $this->model->where('company_id', $companyId)->first();
    }

    public function findByCompanyAndTypePage(string $companyId, ?string $typePage): ?EcoBannerSetting
    {
        return $this->model->where('company_id', $companyId)
            ->where('type_page', $typePage)
            ->first();
    }

    public function upsertByCompanyId(string $companyId, array $data): EcoBannerSetting
    {
        return $this->model->updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }

    public function upsertByCompanyAndTypePage(string $companyId, ?string $typePage, array $data): EcoBannerSetting
    {
        return $this->model->updateOrCreate(
            [
                'company_id' => $companyId,
                'type_page' => $typePage
            ],
            $data
        );
    }
}
