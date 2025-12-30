<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteIcon\DTO\CreateWebsiteIconDTO;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use Modules\WebsiteCMS\WebsiteIcon\Repositories\WebsiteIconRepository;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteIconCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteIconRepository $repository,
        private WebsiteHomePageService $homePageService,
    ) {
    }

    public function create(CreateWebsiteIconDTO $createWebsiteIconDTO): WebsiteIcon
    {
         $icon = $this->repository->createWebsiteIcon(
             $createWebsiteIconDTO->toArray(),
             $createWebsiteIconDTO->getIcon()
         );
         
         $this->homePageService->clearCache();
         
         return $icon;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        $orderBy = 'created_at';
        $sortBy = 'desc';

        if (request()->has("sort")) {
            $orderBy = 'name';
            $sortBy = request()->get("sort") === 'desc' ? 'desc' : 'asc';
        }

        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            orderBy: $orderBy,
            sortBy: $sortBy,
        );
    }

    public function get(UuidInterface $id): WebsiteIcon
    {
        return $this->repository->getWebsiteIcon(
            id: $id,
        );
    }
}
