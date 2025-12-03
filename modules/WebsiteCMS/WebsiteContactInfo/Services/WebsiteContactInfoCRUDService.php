<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteContactInfo\DTO\CreateWebsiteContactInfoDTO;
use Modules\WebsiteCMS\WebsiteContactInfo\DTO\UpdateWebsiteContactInfoDTO;
use Modules\WebsiteCMS\WebsiteContactInfo\Models\WebsiteContactInfo;
use Modules\WebsiteCMS\WebsiteContactInfo\Repositories\WebsiteContactInfoRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteContactInfoCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteContactInfoRepository $repository,
    ) {
    }

    public function create(CreateWebsiteContactInfoDTO $createWebsiteContactInfoDTO): WebsiteContactInfo
    {
         return $this->repository->createWebsiteContactInfo($createWebsiteContactInfoDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteContactInfo
    {
        return $this->repository->getWebsiteContactInfo(
            id: $id,
        );
    }

    public function getCurrentCompanyContactInfo(): ?WebsiteContactInfo
    {
        return $this->repository->getCurrentCompanyContactInfo();
    }

    public function updateCurrentCompanyContactInfo(UpdateWebsiteContactInfoDTO $dto): WebsiteContactInfo
    {
        return $this->repository->updateCurrentCompanyContactInfo($dto->toArray());
    }
}
