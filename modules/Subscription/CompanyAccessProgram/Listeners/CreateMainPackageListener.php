<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RoleAndPermission\Services\PermissionLookupService;
use Modules\Subscription\CompanyAccessProgram\Events\CompanyAccessProgramCreated;
use Modules\Subscription\Package\Models\Package;

class CreateMainPackageListener
{
    public function __construct(
        private PermissionLookupService $permissionLookupService
    ) {
    }

    /**
     * Handle the CompanyAccessProgramCreated event
     *
     * @param CompanyAccessProgramCreated $event
     * @return void
     */
    public function handle(CompanyAccessProgramCreated $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                $companyAccessProgram = $event->companyAccessProgram;
                
                // Reload the model to ensure we have the latest data with sub-entities
                $companyAccessProgram->refresh();
                $companyAccessProgram->load(['subEntities', 'programs']);
                
                // Create main package for this company access program
                $package = $this->createMainPackage($companyAccessProgram);
                
                // Get permissions filtered by sub-entities
                $permissions = $this->getFilteredPermissions($companyAccessProgram);
                
                // Assign permissions to the package
                $this->assignPermissionsToPackage($package, $permissions);
                
                Log::info("Auto-created main package for CompanyAccessProgram", [
                    'company_access_program_id' => $companyAccessProgram->id,
                    'package_id' => $package->id,
                    'permissions_count' => $permissions->count(),
                    'sub_entities_count' => $companyAccessProgram->subEntities->count()
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Failed to auto-create main package for CompanyAccessProgram", [
                'company_access_program_id' => $event->companyAccessProgram->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Create main package for the company access program
     *
     * @param \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram $companyAccessProgram
     * @return Package
     */
    private function createMainPackage($companyAccessProgram): Package
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
            'is_main_package' => true, // Mark as main package
        ];

        return Package::create($packageData);
    }

    /**
     * Get permissions filtered by sub-entities from the company access program
     *
     * @param \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram $companyAccessProgram
     * @return \Illuminate\Support\Collection
     */
    private function getFilteredPermissions($companyAccessProgram)
    {
        // Get sub-entity IDs from the company access program
        $subEntityIds = $companyAccessProgram->subEntities->pluck('sub_entity_id')->toArray();
        
        Log::info("Filtering permissions by sub-entities", [
            'company_access_program_id' => $companyAccessProgram->id,
            'sub_entity_ids' => $subEntityIds
        ]);
        
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
            
            Log::info("Assigned permissions to package", [
                'package_id' => $package->id,
                'permissions_assigned' => count($permissionData)
            ]);
        } else {
            Log::warning("No permissions found to assign to package", [
                'package_id' => $package->id
            ]);
        }
    }
}
