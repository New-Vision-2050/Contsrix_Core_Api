<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Page\DTO\CreatePageDTO;
use Modules\Ecommerce\Page\Models\Page;
use Modules\Ecommerce\Page\Repositories\PageRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class PageCRUDService
{
    use HasExportService;

    public function __construct(
        private PageRepository $repository,
    ) {
    }

    public function create(CreatePageDTO $createPageDTO): Page
    {
         return $this->repository->createPage($createPageDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Page
    {
        return $this->repository->getPage(
            id: $id,
        );
    }

    public function getByType(string $type): ?Page
    {
        return $this->repository->getByType($type);
    }

    public function upsertByType(string $type, array $pageData): Page
    {
        return $this->repository->upsertByType($type, $pageData);
    }
}
