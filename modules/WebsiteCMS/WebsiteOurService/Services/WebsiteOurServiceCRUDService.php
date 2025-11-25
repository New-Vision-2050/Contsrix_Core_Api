<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteOurService\DTO\CreateWebsiteOurServiceDTO;
use Modules\WebsiteCMS\WebsiteOurService\Models\WebsiteOurService;
use Modules\WebsiteCMS\WebsiteOurService\Repositories\WebsiteOurServiceRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteOurServiceCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteOurServiceRepository $repository,
    ) {
    }

    public function create(CreateWebsiteOurServiceDTO $createWebsiteOurServiceDTO): WebsiteOurService
    {
         return $this->repository->createWebsiteOurService(
             $createWebsiteOurServiceDTO->toArray(),
             $createWebsiteOurServiceDTO->departments
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteOurService
    {
        return $this->repository->getWebsiteOurService(
            id: $id,
        );
    }

    public function getCurrentCompany(): ?WebsiteOurService
    {
        return $this->repository->getCurrentCompanyWebsiteOurService();
    }

    public function updateCurrentCompany(CreateWebsiteOurServiceDTO $dto): WebsiteOurService
    {
        return $this->repository->updateCurrentCompanyWebsiteOurService(
            $dto->toArray(),
            $dto->departments
        );
    }
}
