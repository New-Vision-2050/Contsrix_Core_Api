<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteAddress\DTO\CreateWebsiteAddressDTO;
use Modules\WebsiteCMS\WebsiteAddress\Models\WebsiteAddress;
use Modules\WebsiteCMS\WebsiteAddress\Repositories\WebsiteAddressRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteAddressCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteAddressRepository $repository,
    ) {
    }

    public function create(CreateWebsiteAddressDTO $createWebsiteAddressDTO): WebsiteAddress
    {
         return $this->repository->createWebsiteAddress($createWebsiteAddressDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteAddress
    {
        return $this->repository->getWebsiteAddress(
            id: $id,
        );
    }
}
