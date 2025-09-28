<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Services\Dashboard;


use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\CreateEcoAppSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoAppSettingFrontPageDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoAppSettingThemeDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoCartSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoFavoritesSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoFilterDisplaySettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoProductCardSettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoProductDisplaySettingDashboardDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\Dashboard\UpsertEcoTermsSettingDashboardDTO;
use Modules\Shared\Media\Services\FileUploadService;

class EcoAppSettingCRUDDashboardService
{
    use HasExportService;

    public function __construct(
        private EcoAppSettingRepository $repository,
        private FileUploadService $fileUploadService
    ) {
    }

    public function create(CreateEcoAppSettingDashboardDTO $createEcoAppSettingDTO): EcoAppSetting
    {
         return $this->repository->createEcoAppSetting($createEcoAppSettingDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoAppSetting
    {
        return $this->repository->getEcoAppSetting(
            id: $id,
        );
    }

    public function upsertTheme(UpsertEcoAppSettingThemeDashboardDTO $upsertDTO): EcoAppSetting
    {
        return $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );
    }

    public function getByCompany(string $companyId): ?EcoAppSetting
    {
        return $this->repository->findByCompanyId($companyId);
    }

    public function upsertFrontPage(UpsertEcoAppSettingFrontPageDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        $logo = $upsertDTO->getLogo();


        if ($logo) {
            $path = $ecoAppSetting->company->name . '/ecommerce/settings/logo'   ;

            $this->fileUploadService->uploadFile(
                $ecoAppSetting,
                $logo,
                $path,
                'eco_logo',
                "public"
            );
        }
        return $ecoAppSetting;
    }

    public function upsertProductDisplay(UpsertEcoProductDisplaySettingDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertFavorites(UpsertEcoFavoritesSettingDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertProductCard(UpsertEcoProductCardSettingDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertFilterDisplay(UpsertEcoFilterDisplaySettingDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertTerms(UpsertEcoTermsSettingDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertCart(UpsertEcoCartSettingDashboardDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        // Handle empty cart image upload
        $emptyCartImage = $upsertDTO->getEmptyCartImage();
        if ($emptyCartImage) {
            // Clear existing empty cart images before uploading new one
            $ecoAppSetting->clearMediaCollection('empty_cart_image');

            $path = $ecoAppSetting->company->name . '/ecommerce/settings/cart';

            $this->fileUploadService->uploadFile(
                $ecoAppSetting,
                [$emptyCartImage],
                $path,
                'empty_cart_image',
                "public"
            );
        }

        return $ecoAppSetting;
    }
}
