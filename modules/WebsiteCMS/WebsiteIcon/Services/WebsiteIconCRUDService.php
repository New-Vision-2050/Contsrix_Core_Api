<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteIcon\DTO\CreateWebsiteIconDTO;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use Modules\WebsiteCMS\WebsiteIcon\Repositories\WebsiteIconRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteIconCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteIconRepository $repository,
    ) {
    }

    public function create(CreateWebsiteIconDTO $createWebsiteIconDTO): WebsiteIcon
    {
         return $this->repository->createWebsiteIcon(
             $createWebsiteIconDTO->toArray(),
             $createWebsiteIconDTO->getIcon()
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteIcon
    {
        return $this->repository->getWebsiteIcon(
            id: $id,
        );
    }
}
