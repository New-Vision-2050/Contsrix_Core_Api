<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteTheme\DTO\CreateWebsiteThemeDTO;
use Modules\WebsiteCMS\WebsiteTheme\DTO\UpdateWebsiteThemeDTO;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use Modules\WebsiteCMS\WebsiteTheme\Repositories\WebsiteThemeRepository;
use Modules\WebsiteCMS\WebsiteContactInfo\Repositories\WebsiteContactInfoRepository;
use Modules\WebsiteCMS\SocialMediaLink\Models\SocialMediaLink;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteThemeCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteThemeRepository $repository,
        private WebsiteContactInfoRepository $contactInfoRepository,
    ) {
    }

    public function create(CreateWebsiteThemeDTO $createWebsiteThemeDTO): WebsiteTheme
    {
         return $this->repository->createWebsiteTheme($createWebsiteThemeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteTheme
    {
        return $this->repository->getWebsiteTheme(
            id: $id,
        );
    }

    /**
     * Get the website theme for the current company
     */
    public function getCurrentCompanyTheme(): ?WebsiteTheme
    {
        return $this->repository->getCurrentCompanyTheme();
    }

    /**
     * Get the current company contact info
     */
    public function getCurrentCompanyContactInfo()
    {
        return $this->contactInfoRepository->getCurrentCompanyContactInfo();
    }

    /**
     * Get the current company social media links
     */
    public function getCurrentCompanySocialMediaLinks()
    {
        return SocialMediaLink::where('company_id', tenant('id'))
            ->where('status', 1)
            ->get();
    }

    /**
     * Update the website theme for the current company
     */
    public function updateCurrentCompanyTheme(UpdateWebsiteThemeDTO $dto): WebsiteTheme
    {
        return $this->repository->updateCurrentCompanyTheme(
            data: $dto->toArray(),
            colorPalettes: $dto->color_palettes,
            icon: $dto->icon
        );
    }
}
