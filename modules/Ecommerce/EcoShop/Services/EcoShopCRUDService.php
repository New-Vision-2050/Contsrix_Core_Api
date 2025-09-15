<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoShop\DTO\CreateEcoShopDTO;
use Modules\Ecommerce\EcoShop\DTO\UpsertEcoShopDTO;
use Modules\Ecommerce\EcoShop\Models\EcoShop;
use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoShopCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoShopRepository $repository,
    ) {
    }

    public function create(CreateEcoShopDTO $createEcoShopDTO): EcoShop
    {
         return $this->repository->createEcoShop($createEcoShopDTO->toArray());
    }

    public function upsert(CreateEcoShopDTO $createEcoShopDTO): EcoShop
    {
        // Check if shop already exists for this company
        $existingShop = $this->repository->findByCompanyId($createEcoShopDTO->getCompanyId());
        if ($existingShop) {
            // Update existing shop
            $this->repository->updateEcoShop($createEcoShopDTO->getCompanyId(),$createEcoShopDTO->toArray());
            return $this->repository->getEcoShop($createEcoShopDTO->getCompanyId());
        } else {
            // Create new shop
            return $this->repository->createEcoShop($createEcoShopDTO->toArray());
        }
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId): EcoShop
    {
        return $this->repository->getEcoShop(
            companyId: $companyId,
        );
    }
}
