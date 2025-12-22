<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\CategoryWebsiteCMS\DTO\CreateCategoryWebsiteCMSDTO;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Enum\CategoryWebsiteCMSType;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Repositories\CategoryWebsiteCMSRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class CategoryWebsiteCMSCRUDService
{
    use HasExportService;

    public function __construct(
        private CategoryWebsiteCMSRepository $repository,
    )
    {
    }

    public function create(CreateCategoryWebsiteCMSDTO $createCategoryWebsiteCMSDTO): CategoryWebsiteCMS
    {
        return $this->repository->createCategoryWebsiteCMS($createCategoryWebsiteCMSDTO->toArray());
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

    public function get(UuidInterface $id): CategoryWebsiteCMS
    {
        return $this->repository->getCategoryWebsiteCMS(
            id: $id,
        );
    }

    public function getTypes(): array
    {
        return collect(CategoryWebsiteCMSType::array())->map(function ($item) {
            return [
                'id' => $item["value"],
                'name' => CategoryWebsiteCMSType::lang($item["value"]),
            ];
        })->toArray();
    }

    public function getAll(): Collection
    {


        return $this->repository->getAll();
    }
}
