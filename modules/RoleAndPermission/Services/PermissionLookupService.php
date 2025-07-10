<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Ramsey\Uuid\Uuid;

class PermissionLookupService
{
    public function __construct(
        private CompanyRepository $companyRepository,
    ) {
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
}
