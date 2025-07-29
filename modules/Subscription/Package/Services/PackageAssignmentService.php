<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Services;

use Illuminate\Support\Facades\DB;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Repositories\PackageRepository;
use Modules\Subscription\Package\Repositories\CompanyPermissionLimitRepository;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\RoleAndPermission\Repositories\RoleRepository;

class PackageAssignmentService
{
    public function __construct(
        private PackageRepository $packageRepository,
        private CompanyPermissionLimitRepository $companyPermissionLimitRepository,
        private CompanyRepository $companyRepository,
        private RoleRepository $roleRepository
    ) {}

    /**
     * Assign multiple packages to a company with permission limits handling.
     */
    public function assignPackagesToCompany(string $companyId, array $packageIds): array
    {
        return DB::transaction(function () use ($companyId, $packageIds) {
            $company = $this->companyRepository->findOneOrFail($companyId);

            // Update company_package relationship first
            $this->companyRepository->syncPackages($company, $packageIds);

            // Then calculate permission limits from all assigned packages
            $permissionLimits = $this->calculatePermissionLimits($companyId);
            $this->syncCompanyPermissionLimits($companyId, $permissionLimits);

            // Sync company role permissions with package permissions
            $this->syncCompanyRolePermissions($companyId, $packageIds);

            return [
                'company_id' => $companyId,
                'assigned_packages' => $packageIds,
                'permission_limits' => $permissionLimits,
                'message' => 'Packages assigned successfully with permission limits calculated'
            ];
        });
    }

    /**
     * Calculate combined permission limits from multiple packages.
     * If a permission has no limit (null) in any package, it becomes unlimited.
     * Otherwise, take the highest limit among packages.
     */
    private function calculatePermissionLimits(string $companyId): array
    {
        // Get all packages assigned to this company with their permissions
        $company = $this->companyRepository->findWithPackages($companyId);
        $packageIds = $company->packages->pluck('id')->toArray();

        if (empty($packageIds)) {
            return [];
        }

        // Get packages with all their permissions (not just limited ones)
        $packages = $this->packageRepository->findByIdsWithPermissions($packageIds);

        $permissionLimits = [];
        $unlimitedPermissions = []; // Track permissions that should be unlimited

        // First pass: identify unlimited permissions
        foreach ($packages as $package) {
            foreach ($package->permissions as $permission) {
                $limit = $permission->pivot->limit;
                $permissionId = $permission->id;

                // If any package grants unlimited access, mark as unlimited
                if ($limit === null) {
                    $unlimitedPermissions[$permissionId] = true;
                }
            }
        }

        // Second pass: calculate limits for non-unlimited permissions
        foreach ($packages as $package) {
            foreach ($package->permissions as $permission) {
                $limit = $permission->pivot->limit;
                $permissionId = $permission->id;

                // Skip if this permission is unlimited
                if (isset($unlimitedPermissions[$permissionId])) {
                    continue;
                }

                // Only process permissions that have limits
                if ($limit !== null) {
                    // If permission already exists, take the higher limit
                    if (isset($permissionLimits[$permissionId])) {
                        $permissionLimits[$permissionId] = max($permissionLimits[$permissionId], $limit);
                    } else {
                        $permissionLimits[$permissionId] = $limit;
                    }
                }
            }
        }

        return $permissionLimits;
    }

    /**
     * Sync company permission limits in the database.
     */
    private function syncCompanyPermissionLimits(string $companyId, array $permissionLimits): void
    {
        // Remove existing limits for this company
        $this->companyPermissionLimitRepository->deleteByCompanyId($companyId);

        // Insert new limits
        $limitsData = [];
        foreach ($permissionLimits as $permissionId => $limit) {
            $limitsData[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'permission_id' => $permissionId,
                'limit' => $limit,
                'actual_limit' => $limit, // Initially set actual_limit to full limit
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->companyPermissionLimitRepository->bulkInsert($limitsData);
    }

    /**
     * Sync company role permissions with package permissions.
     */
    private function syncCompanyRolePermissions(string $companyId, array $packageIds): void
    {
        // Get all roles for this company
        $roles = $this->roleRepository->findByCompanyId($companyId);

        if ($roles->isEmpty()) {
            return;
        }

        // Get all permissions available in the assigned packages
        $packages = $this->packageRepository->findByIdsWithPermissions($packageIds);
        $allowedPermissionIds = [];

        foreach ($packages as $package) {
            foreach ($package->permissions as $permission) {
                $allowedPermissionIds[] = $permission->id;
            }
        }

        // Remove duplicates
        $allowedPermissionIds = array_unique($allowedPermissionIds);

        // For each company role, sync permissions to only include those from the assigned packages
        foreach ($roles as $role) {
            // Get current role permissions
            $currentPermissionIds = $role->permissions()->pluck('id')->toArray();

            // Find permissions to remove (those not in any assigned package)
            $permissionsToRemove = array_diff($currentPermissionIds, $allowedPermissionIds);

            // Find permissions to add (those in packages but not currently assigned to role)
            $permissionsToAdd = array_diff($allowedPermissionIds, $currentPermissionIds);

            // Remove permissions not in packages
            if (!empty($permissionsToRemove)) {
                $role->permissions()->detach($permissionsToRemove);
            }

            // Add permissions from packages to super admin and admin roles
            if ($this->isAdminRole($role) && !empty($permissionsToAdd)) {
                $role->permissions()->attach($permissionsToAdd);
            }
        }
    }

    /**
     * Check if a role is super admin or admin.
     */
    private function isAdminRole($role): bool
    {
        $adminRoleNames = ['super-admin', 'admin', 'Super Admin', 'Admin'];
        return in_array($role->name, $adminRoleNames, true);
    }

    /**
     * Sync company packages relationship.
     */
    private function syncCompanyPackages($company, array $packageIds): void
    {
        $syncData = [];
        foreach ($packageIds as $packageId) {
            $syncData[$packageId] = [
                'subscribed_at' => now(),
                'expires_at' => now()->addYear(), // Default 1 year subscription
                'is_active' => true,
            ];
        }

        $this->companyRepository->syncPackages($company, $syncData);
    }

    /**
     * Update package limits when a package is modified.
     */
    public function updatePackageLimits(string $packageId): void
    {
        DB::transaction(function () use ($packageId) {
            $package = $this->packageRepository->findWithPermissionsAndCompanies($packageId);

            foreach ($package->companies as $company) {
                // Get all packages for this company
                $companyWithPackages = $this->companyRepository->findWithPackages($company->id);
                $companyPackageIds = $companyWithPackages->packages->pluck('id')->toArray();

                // Recalculate limits
                $permissionLimits = $this->calculatePermissionLimits($company->id);

                // Update existing limits for this company
                foreach ($permissionLimits as $permissionId => $newLimit) {
                    $existingLimit = $this->companyPermissionLimitRepository->findByCompanyAndPermission(
                        $company->id,
                        $permissionId
                    );

                    if ($existingLimit) {
                        // Update limit while preserving usage
                        $usedAmount = $existingLimit->limit - $existingLimit->actual_limit;
                        $existingLimit->limit = $newLimit;
                        $existingLimit->actual_limit = max(0, $newLimit - $usedAmount);
                        $existingLimit->save();
                    } else {
                        // Create new limit
                        $this->companyPermissionLimitRepository->updateOrCreate(
                            [
                                'company_id' => $company->id,
                                'permission_id' => $permissionId,
                            ],
                            [
                                'limit' => $newLimit,
                                'actual_limit' => $newLimit,
                            ]
                        );
                    }
                }

                // Sync company role permissions with updated package permissions
                $this->syncCompanyRolePermissions($company->id, $companyPackageIds);
            }
        });
    }

    /**
     * Recalculate permission limits for all companies using this package.
     */
    public function recalculate(Package $package): void
    {
        $this->updatePackageLimits($package->id);
    }

    /**
     * Sync a package to all its assigned companies (used for main package updates).
     */
    public function syncPackageToCompanies(Package $package): void
    {
        $this->updatePackageLimits($package->id);
    }
}
