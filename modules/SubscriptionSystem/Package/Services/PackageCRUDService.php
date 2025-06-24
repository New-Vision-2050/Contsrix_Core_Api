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

    public function create(CreatePackageDTO $createPackageDTO): Package
    {
         return $this->repository->createPackage($createPackageDTO->toArray());
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
