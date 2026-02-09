<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteNews\DTO\CreateWebsiteNewsDTO;
use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;
use Modules\WebsiteCMS\WebsiteNews\Repositories\WebsiteNewsRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteNewsCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteNewsRepository $repository,
    ) {
    }

    public function create(CreateWebsiteNewsDTO $createWebsiteNewsDTO): WebsiteNews
    {
         return $this->repository->createWebsiteNews(
             $createWebsiteNewsDTO->toArray(),
             $createWebsiteNewsDTO->getMainImage(),
             $createWebsiteNewsDTO->getThumbnail()
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }
    
    public function listConditions(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            conditions:[
                'status' => 1,
            ],
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteNews
    {
        return $this->repository->getWebsiteNews(
            id: $id,
        );
    }

    public function toggleStatus(UuidInterface $id): WebsiteNews
    {
        return $this->repository->toggleStatus($id);
    }
}
