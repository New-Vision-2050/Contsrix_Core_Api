<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteProjectSetting\DTO\CreateWebsiteProjectSettingDTO;
use Modules\WebsiteCMS\WebsiteProjectSetting\Models\WebsiteProjectSetting;
use Modules\WebsiteCMS\WebsiteProjectSetting\Repositories\WebsiteProjectSettingRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteProjectSettingCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteProjectSettingRepository $repository,
    ) {
    }

    public function create(CreateWebsiteProjectSettingDTO $createWebsiteProjectSettingDTO): WebsiteProjectSetting
    {
         return $this->repository->createWebsiteProjectSetting($createWebsiteProjectSettingDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteProjectSetting
    {
        return $this->repository->getWebsiteProjectSetting(
            id: $id,
        );
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }
}
