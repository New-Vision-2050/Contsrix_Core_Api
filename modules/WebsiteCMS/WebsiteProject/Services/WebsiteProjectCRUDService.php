<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteProject\DTO\CreateWebsiteProjectDTO;
use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProject;
use Modules\WebsiteCMS\WebsiteProject\Repositories\WebsiteProjectRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteProjectCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteProjectRepository $repository,
    ) {
    }

    public function create(CreateWebsiteProjectDTO $createWebsiteProjectDTO): WebsiteProject
    {
         return $this->repository->createWebsiteProject(
             data: $createWebsiteProjectDTO->toArray(),
             mainImage: $createWebsiteProjectDTO->mainImage,
             secondaryImages: $createWebsiteProjectDTO->secondaryImages,
             projectDetails: $createWebsiteProjectDTO->projectDetails,
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteProject
    {
        return $this->repository->getWebsiteProject(
            id: $id,
        );
    }

    public function deleteMedia(UuidInterface $id, int $mediaId)
    {
         $this->repository->deleteMedia($id, $mediaId);
    }
}
