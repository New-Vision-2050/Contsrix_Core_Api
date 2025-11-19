<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\WebsiteTermAndCondition\DTO\CreateWebsiteTermAndConditionDTO;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Models\WebsiteTermAndCondition;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Repositories\WebsiteTermAndConditionRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class WebsiteTermAndConditionCRUDService
{
    use HasExportService;

    public function __construct(
        private WebsiteTermAndConditionRepository $repository,
    ) {
    }

    public function create(CreateWebsiteTermAndConditionDTO $createWebsiteTermAndConditionDTO): WebsiteTermAndCondition
    {
         return $this->repository->createWebsiteTermAndCondition($createWebsiteTermAndConditionDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): WebsiteTermAndCondition
    {
        return $this->repository->getWebsiteTermAndCondition(
            id: $id,
        );
    }
}
