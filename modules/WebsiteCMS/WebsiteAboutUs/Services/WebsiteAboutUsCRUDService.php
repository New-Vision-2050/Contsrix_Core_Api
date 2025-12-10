<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteAboutUs\DTO\CreateWebsiteAboutUsDTO;
use Modules\WebsiteCMS\WebsiteAboutUs\DTO\UpdateWebsiteAboutUsDTO;
use Modules\WebsiteCMS\WebsiteAboutUs\Models\WebsiteAboutUs;
use Modules\WebsiteCMS\WebsiteAboutUs\Repositories\WebsiteAboutUsRepository;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteAboutUsCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteAboutUsRepository $repository,
        private WebsiteHomePageService $homePageService,
    ) {
    }

    public function create(CreateWebsiteAboutUsDTO $createWebsiteAboutUsDTO): WebsiteAboutUs
    {
        $aboutUs = $this->repository->createWebsiteAboutUs(
            data: $createWebsiteAboutUsDTO->toArray(),
            mainImage: $createWebsiteAboutUsDTO->main_image,
            projectTypes: $createWebsiteAboutUsDTO->project_types,
            attachments: $createWebsiteAboutUsDTO->attachments,
        );
        
        $this->homePageService->clearCache();
        
        return $aboutUs;
    }

    public function update(UpdateWebsiteAboutUsDTO $updateWebsiteAboutUsDTO): WebsiteAboutUs
    {
        $aboutUs = $this->repository->updateWebsiteAboutUs(
            id: Uuid::fromString($updateWebsiteAboutUsDTO->id),
            data: $updateWebsiteAboutUsDTO->toArray(),
            mainImage: $updateWebsiteAboutUsDTO->main_image,
            projectTypes: $updateWebsiteAboutUsDTO->project_types,
            attachments: $updateWebsiteAboutUsDTO->attachments,
        );
        
        $this->homePageService->clearCache();
        
        return $aboutUs;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteAboutUs
    {
        return $this->repository->getWebsiteAboutUs(
            id: $id,
        );
    }

    public function delete(UuidInterface $id): bool
    {
        $result = $this->repository->deleteWebsiteAboutUs($id);
        
        $this->homePageService->clearCache();
        
        return $result;
    }

    public function getCurrentCompanyAboutUs(): ?WebsiteAboutUs
    {
        return $this->repository->getCurrentCompanyAboutUs();
    }

    public function updateCurrentCompanyAboutUs(UpdateWebsiteAboutUsDTO $updateWebsiteAboutUsDTO): WebsiteAboutUs
    {
        $aboutUs = $this->repository->updateCurrentCompanyAboutUs(
            data: $updateWebsiteAboutUsDTO->toArray(),
            mainImage: $updateWebsiteAboutUsDTO->main_image,
            projectTypes: $updateWebsiteAboutUsDTO->project_types,
            attachments: $updateWebsiteAboutUsDTO->attachments,
        );
        
        $this->homePageService->clearCache();
        
        return $aboutUs;
    }
}
