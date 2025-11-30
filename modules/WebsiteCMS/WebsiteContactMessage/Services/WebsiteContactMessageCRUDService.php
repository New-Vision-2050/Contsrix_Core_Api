<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteContactMessage\DTO\CreateWebsiteContactMessageDTO;
use Modules\WebsiteCMS\WebsiteContactMessage\Models\WebsiteContactMessage;
use Modules\WebsiteCMS\WebsiteContactMessage\Repositories\WebsiteContactMessageRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteContactMessageCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteContactMessageRepository $repository,
    ) {
    }

    public function create(CreateWebsiteContactMessageDTO $createWebsiteContactMessageDTO): WebsiteContactMessage
    {
         return $this->repository->createWebsiteContactMessage($createWebsiteContactMessageDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteContactMessage
    {
        return $this->repository->getWebsiteContactMessage(
            id: $id,
        );
    }
}
