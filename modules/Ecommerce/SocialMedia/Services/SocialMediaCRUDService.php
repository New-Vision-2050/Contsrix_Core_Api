<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\SocialMedia\DTO\CreateSocialMediaDTO;
use Modules\Ecommerce\SocialMedia\Models\SocialMedia;
use Modules\Ecommerce\SocialMedia\Repositories\SocialMediaRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class SocialMediaCRUDService
{
    use HasExportService;

    public function __construct(
        private SocialMediaRepository $repository,
    ) {
    }

    public function create(CreateSocialMediaDTO $createSocialMediaDTO): SocialMedia
    {
         return $this->repository->createSocialMedia($createSocialMediaDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SocialMedia
    {
        return $this->repository->getSocialMedia(
            id: $id,
        );
    }

    public function toggleStatus(UuidInterface $id): SocialMedia
    {
        return $this->repository->toggleStatus($id);
    }
}
