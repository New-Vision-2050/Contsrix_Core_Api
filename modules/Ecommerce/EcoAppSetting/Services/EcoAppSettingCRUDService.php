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
}
