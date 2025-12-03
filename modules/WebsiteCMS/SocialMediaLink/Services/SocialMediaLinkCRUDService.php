<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\SocialMediaLink\DTO\CreateSocialMediaLinkDTO;
use Modules\WebsiteCMS\SocialMediaLink\Models\SocialMediaLink;
use Modules\WebsiteCMS\SocialMediaLink\Repositories\SocialMediaLinkRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class SocialMediaLinkCRUDService
{
    use HasExportService;

    public function __construct(
        private SocialMediaLinkRepository $repository,
    ) {
    }

    public function create(CreateSocialMediaLinkDTO $createSocialMediaLinkDTO): SocialMediaLink
    {
        return $this->repository->createSocialMediaLink(
            $createSocialMediaLinkDTO->toArray(),
            $createSocialMediaLinkDTO->icon
        );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SocialMediaLink
    {
        return $this->repository->getSocialMediaLink(
            id: $id,
        );
    }

    public function updateStatus(UuidInterface $id, int $status): bool
    {
        return $this->repository->updateStatus($id, $status);
    }
}
