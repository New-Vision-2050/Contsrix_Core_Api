<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Services;

use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Collection;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\DTO\CreatePackageDTO;
use Modules\Subscription\Package\DTO\UpdatePackageDTO;
use Modules\Subscription\Package\Repositories\PackageRepository;
use Modules\Subscription\Package\Services\PackageAssignmentService;

class PackageCRUDService
{
    public function __construct(
        private PackageRepository        $repository,
        private PackageAssignmentService $assignmentService,
        private PermissionRepository       $permissionRepository,
    )
    {
    }

    public function create(CreatePackageDTO $createPackageDTO): Package
    {
        return $this->repository->createPackage($createPackageDTO);
    }

    public function update(UpdatePackageDTO $updatePackageDTO): Package
    {
        return $this->repository->updatePackage($updatePackageDTO);
    }

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginated(
            $filters,
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

    public function counts(): array
    {
        return $this->repository->counts();
    }

    public function syncPermissions(Package $package, array $permissionIds, array $limits = []): void
    {
        $this->repository->syncPermissions($package, $permissionIds, $limits);
        $this->assignmentService->recalculate($package);
    }

    public function assignToCompany(array $packageId, string $companyId): void
    {
        $this->assignmentService->assignPackagesToCompany( $companyId,$packageId);
    }

    /**
     * Get filtered packages for export
     *
     * @param array $filters Array of filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
