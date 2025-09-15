<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoShopAddress\DTO\CreateEcoShopAddressDTO;
use Modules\Ecommerce\EcoShopAddress\Models\EcoShopAddress;
use Modules\Ecommerce\EcoShopAddress\Repositories\EcoShopAddressRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoShopAddressCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoShopAddressRepository $repository,
    ) {
    }

    public function create(CreateEcoShopAddressDTO $createEcoShopAddressDTO): EcoShopAddress
    {
         return $this->repository->createEcoShopAddress($createEcoShopAddressDTO->toArray());
    }

    public function upsert(CreateEcoShopAddressDTO $createEcoShopAddressDTO): EcoShopAddress
    {
        // Check if address already exists for this company
        $existingAddress = $this->repository->findByCompanyId($createEcoShopAddressDTO->getCompanyId());
        
        if ($existingAddress) {
            // Update existing address
            $this->repository->updateEcoShopAddress($createEcoShopAddressDTO->getCompanyId(), $createEcoShopAddressDTO->toArray());
            return $this->repository->getEcoShopAddress($createEcoShopAddressDTO->getCompanyId());
        } else {
            // Create new address
            return $this->repository->createEcoShopAddress($createEcoShopAddressDTO->toArray());
        }
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $companyId): EcoShopAddress
    {
        return $this->repository->getEcoShopAddress(
            companyId: $companyId,
        );
    }
}
