<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteHomePage\DTO\CreateWebsiteHomePageDTO;
use Modules\WebsiteCMS\WebsiteHomePage\Models\WebsiteHomePage;
use Modules\WebsiteCMS\WebsiteHomePage\Repositories\WebsiteHomePageRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteHomePageCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteHomePageRepository $repository,
    ) {
    }

    public function create(CreateWebsiteHomePageDTO $createWebsiteHomePageDTO): WebsiteHomePage
    {
         return $this->repository->createWebsiteHomePage($createWebsiteHomePageDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteHomePage
    {
        return $this->repository->getWebsiteHomePage(
            id: $id,
        );
    }
}
