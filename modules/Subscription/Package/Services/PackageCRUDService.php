<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Services;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Collection;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\DTO\CreatePackageDTO;
use Modules\Subscription\Package\Repositories\PackageRepository;

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

    public function attachFeatures(string $packageId, array $features): void
    {
        $this->repository->upsertFeatures($packageId, $features);
    }
}
