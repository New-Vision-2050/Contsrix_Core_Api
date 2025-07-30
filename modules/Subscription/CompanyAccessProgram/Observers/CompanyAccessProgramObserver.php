<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RoleAndPermission\Services\PermissionLookupService;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Repositories\PackageRepository;

class CompanyAccessProgramObserver
{
    public function __construct(
        private PermissionLookupService $permissionLookupService,
        private PackageRepository $packageRepository
    ) {
    }

    /**
     * Handle the CompanyAccessProgram "created" event.
     *
     * @param  CompanyAccessProgram  $companyAccessProgram
     * @return void
     */
    public function created(CompanyAccessProgram $companyAccessProgram): void
    {
        try {
            DB::transaction(function () use ($companyAccessProgram) {
                // Create main package for this company access program
                $package = $this->createMainPackage($companyAccessProgram);
                
                // Get permissions filtered by sub-entities
                $permissions = $this->getFilteredPermissions($companyAccessProgram);
                
                // Assign permissions to the package
                $this->assignPermissionsToPackage($package, $permissions);
                
                Log::info("Auto-created main package for CompanyAccessProgram", [
                    'company_access_program_id' => $companyAccessProgram->id,
                    'package_id' => $package->id,
                    'permissions_count' => $permissions->count()
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Failed to auto-create main package for CompanyAccessProgram", [
                'company_access_program_id' => $companyAccessProgram->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the CompanyAccessProgram "deleting" event.
     *
     * @param CompanyAccessProgram $companyAccessProgram
     * @return bool|null
     */
    public function deleting(CompanyAccessProgram $companyAccessProgram): ?bool
    {
        // Check if this CompanyAccessProgram has any packages assigned
        $packagesCount = $companyAccessProgram->packages()->count();
        
        if ($packagesCount > 0) {
            Log::warning("Attempted to delete CompanyAccessProgram with packages assigned", [
                'company_access_program_id' => $companyAccessProgram->id,
                'company_access_program_name' => $companyAccessProgram->name,
                'packages_count' => $packagesCount
            ]);
            
            throw new \Exception("Cannot delete Company Access Program '{$companyAccessProgram->name}' because it has {$packagesCount} package(s) assigned. Please delete or reassign the packages first.");
        }
        
        Log::info("CompanyAccessProgram deletion allowed - no packages assigned", [
            'company_access_program_id' => $companyAccessProgram->id,
            'company_access_program_name' => $companyAccessProgram->name
        ]);
        
        return true;
    }

    /**
     * Handle the CompanyAccessProgram "updating" event.
     *
     * @param CompanyAccessProgram $companyAccessProgram
     * @return bool|null
     */
    public function updating(CompanyAccessProgram $companyAccessProgram): ?bool
    {
        // Check if this is a main program
        if ($companyAccessProgram->is_main_program) {
            Log::warning("Attempted to update main CompanyAccessProgram", [
                'company_access_program_id' => $companyAccessProgram->id,
                'company_access_program_name' => $companyAccessProgram->name,
                'changes' => $companyAccessProgram->getDirty()
            ]);
            
            throw new \Exception("Main Company Access Program '{$companyAccessProgram->name}' cannot be updated. Main programs are system-managed and protected from modifications.");
        }
        
        Log::info("CompanyAccessProgram update allowed - not a main program", [
            'company_access_program_id' => $companyAccessProgram->id,
            'company_access_program_name' => $companyAccessProgram->name,
            'changes' => $companyAccessProgram->getDirty()
        ]);
        
        return true;
    }

    /**
     * Create main package for the company access program
     *
     * @param CompanyAccessProgram $companyAccessProgram
     * @return Package
     */
    private function createMainPackage(CompanyAccessProgram $companyAccessProgram): Package
    {
        $packageData = [
            'name' => $companyAccessProgram->name . ' - Main Package',
            'price' => 0.00,
            'currency' => 'USD',
            'company_access_program_id' => $companyAccessProgram->id,
            'subscription_period' => 12,
            'subscription_period_unit' => 'month',
            'trial_period' => 0,
            'trial_period_unit' => 'day',
            'is_active' => true,
        ];

        return Package::create($packageData);
    }

    /**
     * Get permissions filtered by sub-entities from the company access program
     *
     * @param CompanyAccessProgram $companyAccessProgram
     * @return \Illuminate\Support\Collection
     */
    private function getFilteredPermissions(CompanyAccessProgram $companyAccessProgram)
    {
        // Get sub-entity IDs from the company access program
        $subEntityIds = $companyAccessProgram->subEntities()->pluck('sub_entity_id')->toArray();
        
        // Use the existing permission lookup service to get filtered permissions
        return $this->permissionLookupService->getPermissionsBySubEntities($subEntityIds);
    }

    /**
     * Assign permissions to the package
     *
     * @param Package $package
     * @param \Illuminate\Support\Collection $permissions
     * @return void
     */
    private function assignPermissionsToPackage(Package $package, $permissions): void
    {
        if ($permissions->isNotEmpty()) {
            // Prepare permission data for sync (with default limit if needed)
            $permissionData = $permissions->mapWithKeys(function ($permission) {
                return [$permission->id => ['limit' => null]]; // No limit by default
            })->toArray();

            // Sync permissions to the package
            $package->permissions()->sync($permissionData);
        }
    }
}
