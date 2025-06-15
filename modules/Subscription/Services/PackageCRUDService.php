<?php

declare(strict_types=1);

namespace Modules\Subscription\Services;

use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Models\Package;
use Modules\Subscription\DTO\CreatePackageDTO;
use Modules\Subscription\DTO\CreateSubscriptionDTO;
use Modules\Subscription\Repositories\PackageRepository;

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
