<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Services\Dashboard;

use Modules\Ecommerce\EcoShop\DTO\Dashboard\CreateEcoShopDashboardDTO;
use Modules\Ecommerce\EcoShop\Models\EcoShop;
use Modules\Ecommerce\EcoShop\Repositories\EcoShopRepository;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class EcoShopDashboardCRUDService
{
    public function __construct(
        private EcoShopRepository $repository,
        private FileUploadService $fileUploadService,
    ) {
    }

    public function create(CreateEcoShopDashboardDTO $createEcoShopDTO): EcoShop
    {
         return $this->repository->createEcoShop($createEcoShopDTO->toArray());
    }

    public function upsert(CreateEcoShopDashboardDTO $createEcoShopDTO): EcoShop
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
