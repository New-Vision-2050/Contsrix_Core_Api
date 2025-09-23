<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoAppSetting\DTO\CreateEcoAppSettingDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoAppSettingDTO;
use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoAppSettingThemeDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoAppSettingFrontPageDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoProductDisplaySettingDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoFavoritesSettingDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoProductCardSettingDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoFilterDisplaySettingDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoTermsSettingDTO;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoCartSettingDTO;
use Modules\Shared\Media\Services\FileUploadService;

class EcoAppSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoAppSettingRepository $repository,
        private FileUploadService $fileUploadService
    ) {
    }

    public function create(CreateEcoAppSettingDTO $createEcoAppSettingDTO): EcoAppSetting
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

    public function upsertTheme(UpsertEcoAppSettingThemeDTO $upsertDTO): EcoAppSetting
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

    public function upsertFrontPage(UpsertEcoAppSettingFrontPageDTO $upsertDTO): EcoAppSetting
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

    public function upsertProductDisplay(UpsertEcoProductDisplaySettingDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertFavorites(UpsertEcoFavoritesSettingDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertProductCard(UpsertEcoProductCardSettingDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertFilterDisplay(UpsertEcoFilterDisplaySettingDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertTerms(UpsertEcoTermsSettingDTO $upsertDTO): EcoAppSetting
    {
        $ecoAppSetting = $this->repository->upsertByCompanyId(
            $upsertDTO->company_id->toString(),
            $upsertDTO->toArray()
        );

        return $ecoAppSetting;
    }

    public function upsertCart(UpsertEcoCartSettingDTO $upsertDTO): EcoAppSetting
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
