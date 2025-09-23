<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Services\Dashboard;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoAddress\DTO\CreateEcoAddressDTO;
use Modules\Ecommerce\EcoAddress\Models\EcoAddress;
use Modules\Ecommerce\EcoAddress\Repositories\EcoAddressRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Modules\Ecommerce\EcoAddress\DTO\Dashboard\CreateEcoAddressDashboardDTO;

class EcoAddressDashboardCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoAddressRepository $repository,
    ) {
    }

    public function create(CreateEcoAddressDashboardDTO $createEcoAddressDTO): EcoAddress
    {
        if ($createEcoAddressDTO->isDefault === true) {
        // Unset other default addresses of the same type for this client/company
        EcoAddress::where('company_id', $createEcoAddressDTO->companyId->toString())
                    ->where('eco_client_id', $createEcoAddressDTO->ecoClientId)
                    ->where('is_default',1)
                    ->update(['is_default' => 0]);
        }
           
        return $this->repository->createEcoAddress($createEcoAddressDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoAddress
    {
        return $this->repository->getEcoAddress(
            id: $id,
        );
    }
}
