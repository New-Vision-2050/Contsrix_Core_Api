<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteSetting\Commands\UpdateWebsiteSettingCommand;
use Modules\WebsiteCMS\WebsiteSetting\DTO\CreateWebsiteSettingDTO;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;
use Modules\WebsiteCMS\WebsiteSetting\Repositories\WebsiteSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Modules\WebsiteCMS\WebsiteSetting\Services\WebsiteSettingUploadService;

class WebsiteSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteSettingRepository $repository,
        private WebsiteSettingUploadService $uploadService,
    ) {
    }

    public function create(CreateWebsiteSettingDTO $createWebsiteSettingDTO): WebsiteSetting
    {
        $websiteSetting = $this->repository->createWebsiteSetting($createWebsiteSettingDTO->toArray());
        
        // Handle logo upload if provided
        if ($createWebsiteSettingDTO->getLogo()) {
            $this->uploadService->uploadLogo($websiteSetting, $createWebsiteSettingDTO->getLogo());
        }
        
        return $websiteSetting;
    }

    public function update(UpdateWebsiteSettingCommand $command): WebsiteSetting
    {
        $websiteSetting = $this->repository->updateWebsiteSetting(
            id: $command->getId(),
            data: $command->toArray(),
        );
        
        // Handle logo upload if provided
        if ($command->getLogo()) {
            $this->uploadService->uploadLogo($websiteSetting, $command->getLogo());
        }
        
        return $websiteSetting;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteSetting
    {
        return $this->repository->getWebsiteSetting(
            id: $id,
        );
    }
    
    public function getForCurrentCompany(): ?WebsiteSetting
    {
        return $this->repository->getForCurrentCompany();
    }

    public function updateForCurrentCompany(array $data, $logo = null): WebsiteSetting
    {
        $websiteSetting = $this->repository->updateOrCreateForCurrentCompany($data);
        
        // Handle logo upload if provided
        if ($logo) {
            $this->uploadService->uploadLogo($websiteSetting, $logo);
        }
        
        return $websiteSetting->refresh();
    }
}
