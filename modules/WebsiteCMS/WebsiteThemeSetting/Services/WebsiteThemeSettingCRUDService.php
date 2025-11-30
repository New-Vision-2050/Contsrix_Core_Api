<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteThemeSetting\DTO\CreateWebsiteThemeSettingDTO;
use Modules\WebsiteCMS\WebsiteThemeSetting\DTO\UpdateWebsiteThemeSettingDTO;
use Modules\WebsiteCMS\WebsiteThemeSetting\DTO\AssignThemeToCompanyDTO;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSetting;
use Modules\WebsiteCMS\WebsiteThemeSetting\Repositories\WebsiteThemeSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteThemeSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteThemeSettingRepository $repository,
    ) {
    }

    public function create(CreateWebsiteThemeSettingDTO $dto): WebsiteThemeSetting
    {
        return $this->repository->createWebsiteThemeSetting(
            data: $dto->toArray(),
            departments: $dto->departments,
            mainImage: $dto->main_image
        );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteThemeSetting
    {
        return $this->repository->getWebsiteThemeSetting(
            id: $id,
        );
    }

    public function update(UuidInterface $id, UpdateWebsiteThemeSettingDTO $dto): WebsiteThemeSetting
    {
        return $this->repository->updateWebsiteThemeSetting(
            id: $id,
            data: $dto->toArray(),
            departments: $dto->departments,
            mainImage: $dto->main_image
        );
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteWebsiteThemeSetting($id);
    }

    /**
     * Assign theme setting to a company
     */
    public function assignThemeToCompany(AssignThemeToCompanyDTO $dto): void
    {
        $this->repository->assignThemeToCompany(
            companyId: $dto->company_id,
            themeSettingId: $dto->website_theme_setting_id
        );
    }

    /**
     * Get theme setting for a specific company
     */
    public function getCompanyThemeSetting(UuidInterface $companyId): ?WebsiteThemeSetting
    {
        return $this->repository->getCompanyThemeSetting($companyId);
    }

    /**
     * Get default theme setting
     */
    public function getDefaultThemeSetting(): ?WebsiteThemeSetting
    {
        return $this->repository->getDefaultThemeSetting();
    }
}
