<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Services;

use Illuminate\Support\Collection;
use Modules\SubscriptionSystem\Package\DTO\CreatePackageDTO;
use Modules\SubscriptionSystem\Package\Models\Package;
use Modules\SubscriptionSystem\Package\Repositories\PackageRepository;
use Ramsey\Uuid\UuidInterface;

class PackageCRUDService
{
    public function __construct(
        private PackageRepository $repository,
    ) {
    }

    public function create(CreatePackageDTO $createPackageDTO)//: Package
    {
        $package = $this->repository->createPackage($createPackageDTO->toArray());

        // 2. Sync all many-to-many relationships
        if (!empty($createPackageDTO->businessTypeIds)) {
            $package->businessTypes()->sync($createPackageDTO->businessTypeIds);
        }
        if (!empty($createPackageDTO->countryIds)) {
            $package->countries()->sync($createPackageDTO->countryIds);
        }
        if (!empty($createPackageDTO->programSystemIds)) {
            $package->programSystems()->sync($createPackageDTO->programSystemIds);
        }
        
        // Eager load relationships for the response
        return $package->load(['businessTypes', 'countries', 'programSystems']);    
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Package
    {
        return $this->repository->getPackage(
            id: $id,
        );
    }
}
