<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Services;

use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoBannerSettingDTO;
use Modules\Ecommerce\EcoAppSetting\Models\EcoBannerSetting;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoBannerSettingRepository;
use Modules\Shared\Media\Services\FileUploadService;

class EcoBannerSettingCRUDService
{
    public function __construct(
        private EcoBannerSettingRepository $repository,
        private FileUploadService $fileUploadService
    ) {
    }

    public function upsert(UpsertEcoBannerSettingDTO $upsertDTO): EcoBannerSetting
    {
        // Use company_id and type_page as composite key for upsert
        $ecoBannerSetting = $this->repository->upsertByCompanyAndTypePage(
            $upsertDTO->company_id->toString(),
            $upsertDTO->type_page,
            $upsertDTO->toArray()
        );

        $banners = $upsertDTO->getBanners();

        if ($banners && is_array($banners) && count($banners) > 0) {
            $path = $ecoBannerSetting->company->name . '/ecommerce/settings/banners';

            $this->fileUploadService->uploadFile(
                $ecoBannerSetting,
                $banners,
                $path,
                'eco_banners',
                "public"
            );
        }

        return $ecoBannerSetting;
    }

    public function getByCompany(string $companyId): ?EcoBannerSetting
    {
        return $this->repository->findByCompanyId($companyId);
    }

    public function getByCompanyAndTypePage(string $companyId, ?string $typePage): ?EcoBannerSetting
    {
        return $this->repository->findByCompanyAndTypePage($companyId, $typePage);
    }
}
