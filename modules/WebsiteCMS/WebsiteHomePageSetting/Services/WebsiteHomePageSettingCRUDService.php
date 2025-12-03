<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteHomePageSetting\DTO\CreateWebsiteHomePageSettingDTO;
use Modules\WebsiteCMS\WebsiteHomePageSetting\DTO\UpdateWebsiteHomePageSettingDTO;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Models\WebsiteHomePageSetting;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Repositories\WebsiteHomePageSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteHomePageSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteHomePageSettingRepository $repository,
    ) {
    }

    public function create(CreateWebsiteHomePageSettingDTO $createWebsiteHomePageSettingDTO): WebsiteHomePageSetting
    {
         return $this->repository->createWebsiteHomePageSetting(
            $createWebsiteHomePageSettingDTO->toArray(),
            $createWebsiteHomePageSettingDTO->webVideoFile,
            $createWebsiteHomePageSettingDTO->mobileVideoFile,
            $createWebsiteHomePageSettingDTO->videoProfileFile
         );
    }

    public function getCurrentCompanySetting(): ?WebsiteHomePageSetting
    {
        return $this->repository->getCurrentCompanySetting();
    }

    public function updateCurrentCompanySetting(UpdateWebsiteHomePageSettingDTO $updateDTO): WebsiteHomePageSetting
    {
        $setting = $this->repository->getCurrentCompanySetting();
        
        if (!$setting) {
            // Create new setting if doesn't exist
            return $this->repository->createWebsiteHomePageSetting(
                $updateDTO->toArray(),
                $updateDTO->webVideoFile,
                $updateDTO->mobileVideoFile,
                $updateDTO->videoProfileFile
            );
        }

        return $this->repository->updateWebsiteHomePageSetting(
            \Ramsey\Uuid\Uuid::fromString($setting->id),
            $updateDTO->toArray(),
            $updateDTO->webVideoFile,
            $updateDTO->mobileVideoFile,
            $updateDTO->videoProfileFile
        );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteHomePageSetting
    {
        return $this->repository->getWebsiteHomePageSetting(
            id: $id,
        );
    }
}
