<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RoleAndPermission\Services\PermissionLookupService;
use Modules\Subscription\CompanyAccessProgram\Events\CompanyAccessProgramUpdated;
use Modules\Subscription\Package\Models\Package;

class UpdateMainPackageListener
{
    public function __construct(
        private PermissionLookupService $permissionLookupService
    ) {
    }

    /**
     * Handle the CompanyAccessProgramUpdated event
     *
     * @param CompanyAccessProgramUpdated $event
     * @return void
     */
    public function handle(CompanyAccessProgramUpdated $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                $companyAccessProgram = $event->companyAccessProgram;
                $originalData = $event->originalData;
                
                // Find the main package for this company access program
                $mainPackage = $this->findMainPackage($companyAccessProgram);
                
                if (!$mainPackage) {
                    Log::warning("No main package found for CompanyAccessProgram", [
                        'company_access_program_id' => $companyAccessProgram->id
                    ]);
                    return;
                }
                
                $packageUpdated = false;
                
                // Check if name changed
                if (isset($originalData['name']) && $originalData['name'] !== $companyAccessProgram->name) {
                    $this->updatePackageName($mainPackage, $companyAccessProgram);
                    $packageUpdated = true;
                    
                    Log::info("Updated main package name", [
                        'package_id' => $mainPackage->id,
                        'old_name' => $originalData['name'] . ' - Main Package',
                        'new_name' => $mainPackage->name
                    ]);
                }
                
                // Check if sub-entities changed by comparing current vs original
                if ($this->subEntitiesChanged($companyAccessProgram, $originalData)) {
                    $this->updatePackagePermissions($mainPackage, $companyAccessProgram);
                    $packageUpdated = true;
                    
                    Log::info("Updated main package permissions due to sub-entity changes", [
                        'package_id' => $mainPackage->id,
                        'company_access_program_id' => $companyAccessProgram->id
                    ]);
                }
                
                if ($packageUpdated) {
                    Log::info("Main package auto-updated for CompanyAccessProgram", [
                        'company_access_program_id' => $companyAccessProgram->id,
                        'package_id' => $mainPackage->id
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error("Failed to auto-update main package for CompanyAccessProgram", [
                'company_access_program_id' => $event->companyAccessProgram->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Find the main package for a company access program
     *
     * @param \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram $companyAccessProgram
     * @return Package|null
     */
    private function findMainPackage($companyAccessProgram): ?Package
    {
        return Package::where('company_access_program_id', $companyAccessProgram->id)
            ->where('is_main_package', true)
            ->first();
    }

    /**
     * Update the main package name
     *
     * @param Package $package
     * @param \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram $companyAccessProgram
     * @return void
     */
    private function updatePackageName(Package $package, $companyAccessProgram): void
    {
        $newName = $companyAccessProgram->name . ' - Main Package';
        
        // Use updateQuietly to bypass the observer protection
        $package->updateQuietly(['name' => $newName]);
    }

    /**
     * Update the main package permissions based on sub-entities
     *
     * @param Package $package
     * @param \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram $companyAccessProgram
     * @return void
     */
    private function updatePackagePermissions(Package $package, $companyAccessProgram): void
    {
        // Reload to get latest sub-entities
        $companyAccessProgram->refresh();
        $companyAccessProgram->load(['subEntities']);
        
        // Get sub-entity IDs
        $subEntityIds = $companyAccessProgram->subEntities->pluck('sub_entity_id')->toArray();
        
        Log::info("Updating package permissions", [
            'package_id' => $package->id,
            'sub_entity_ids' => $subEntityIds
        ]);
        
        // Get filtered permissions
        $permissions = $this->permissionLookupService->getPermissionsBySubEntities($subEntityIds);
        
        // Update package permissions
        if ($permissions->isNotEmpty()) {
            $permissionData = $permissions->mapWithKeys(function ($permission) {
                return [$permission->id => ['limit' => null]];
            })->toArray();

            $package->permissions()->sync($permissionData);
            
            Log::info("Updated package permissions", [
                'package_id' => $package->id,
                'permissions_count' => count($permissionData)
            ]);
        } else {
            // Remove all permissions if no sub-entities
            $package->permissions()->sync([]);
            
            Log::info("Removed all permissions from package (no sub-entities)", [
                'package_id' => $package->id
            ]);
        }
    }

    /**
     * Check if sub-entities have changed
     *
     * @param \Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram $companyAccessProgram
     * @param array $originalData
     * @return bool
     */
    private function subEntitiesChanged($companyAccessProgram, array $originalData): bool
    {
        // Get current sub-entity IDs
        $currentSubEntityIds = $companyAccessProgram->subEntities->pluck('sub_entity_id')->sort()->values()->toArray();
        
        // Get original sub-entity IDs if provided
        $originalSubEntityIds = isset($originalData['sub_entity_ids']) 
            ? collect($originalData['sub_entity_ids'])->sort()->values()->toArray()
            : [];
        
        return $currentSubEntityIds !== $originalSubEntityIds;
    }
}
