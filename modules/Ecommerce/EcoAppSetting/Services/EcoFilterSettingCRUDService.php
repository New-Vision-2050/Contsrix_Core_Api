<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Services;

use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoFilterSettingDTO;
use Modules\Ecommerce\EcoAppSetting\Models\EcoFilterSetting;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoFilterSettingRepository;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Illuminate\Support\Collection;

class EcoFilterSettingCRUDService
{
    public function __construct(
        private EcoFilterSettingRepository $filterRepository,
        private EcoAppSettingRepository $appSettingRepository
    ) {
    }

    public function upsert(UpsertEcoFilterSettingDTO $upsertDTO): Collection
    {
        $companyId = $upsertDTO->company_id->toString();

        // Update app setting for show_filter_in_app
        $this->appSettingRepository->upsertByCompanyId(
            $companyId,
            ['show_filter_in_app' => $upsertDTO->show_filter_in_app]
        );

        $filters = collect();

        foreach ($upsertDTO->getFilters() as $filterData) {
            $filter = $this->filterRepository->upsertByCompanyAndKey(
                $companyId,
                $filterData['filter_key'],
                [
                    'company_id' => $companyId,
                    'filter_name' => $filterData['filter_name'],
                    'filter_key' => $filterData['filter_key'],
                ]
            );

            $filters->push($filter);
        }
        
        return $filters;
    }

    public function getByCompany(string $companyId): Collection
    {
        return $this->filterRepository->findByCompanyId($companyId);
    }
}
