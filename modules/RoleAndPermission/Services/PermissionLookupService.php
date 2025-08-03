<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Repositories\PackageRepository;
use Ramsey\Uuid\Uuid;

class PermissionLookupService
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private PackageRepository $packageRepository,
        private CompanyAccessProgramRepository $companyAccessProgramRepository,
        private PermissionRepository $permissionRepository
    )
    {
    }

    public function getPermissionsForCompany(): Collection
    {
        $company = $this->companyRepository->getCompany(UUid::fromString(tenant("id")));

        if (!$company) {
            return collect();
        }

        $company->load(['packages' => function ($query) {
            $query->where('company_package.is_active', true)->with('permissions');
        }]);

        // Get all permissions from all packages, flatten the collection, and get unique permissions.
        return $company->packages
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function getPermissionsForPackage($packageId): Collection
    {
        $package = $this->packageRepository->getPackage(Uuid::fromString($packageId));
        $companyAccessPrograms = $this->companyAccessProgramRepository->getCompanyAccessProgram(Uuid::fromString($package->company_access_program_id));
        $subEntities = $companyAccessPrograms->subEntities()->pluck("sub_entity_id")->toArray();

        return $this->permissionRepository->getPermissionsBySubEntities($subEntities);
    }

    /**
     * Get permissions filtered by sub-entity IDs
     *
     * @param array $subEntityIds
     * @return Collection
     */
    public function getPermissionsBySubEntities(array $subEntityIds): Collection
    {
        return $this->permissionRepository->getPermissionsBySubEntities($subEntityIds);
    }
}
