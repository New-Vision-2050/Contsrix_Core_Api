<?php

namespace Modules\RoleAndPermission\Commands;

use Illuminate\Console\Command;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Services\PermissionCRUDService;
use Ramsey\Uuid\Uuid;

class SyncCompanyPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync-companies 
                            {--source= : Source company ID to copy from (defaults to first company)}
                            {--target= : Target company ID to copy to (all companies if not specified)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize permissions across companies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceCompanyId = $this->option('source');
        $targetCompanyId = $this->option('target');
        
        $permissionService = app(PermissionCRUDService::class);
        
        if ($targetCompanyId) {
            // Sync permissions to a specific company
            $this->info("Syncing permissions to company ID: {$targetCompanyId}");
            $company = Company::find($targetCompanyId);
            
            if (!$company) {
                $this->error("Target company not found!");
                return 1;
            }
            
            $createdPermissions = $permissionService->copyPermissionsToCompany($sourceCompanyId, $targetCompanyId);
            $this->info("Added/Updated {$createdPermissions->count()} permissions for {$company->name}");
        } else {
            // Sync permissions to all companies
            $companies = Company::all();
            $this->info("Syncing permissions to {$companies->count()} companies...");
            
            $totalPermissions = 0;
            $successCount = 0;
            
            foreach ($companies as $company) {
                try {
                    $createdPermissions = $permissionService->copyPermissionsToCompany($sourceCompanyId, $company->id);
                    $totalPermissions += $createdPermissions->count();
                    $this->info("Processed company: {$company->name} - Added/Updated {$createdPermissions->count()} permissions");
                    $successCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to sync permissions for company {$company->name}: {$e->getMessage()}");
                }
            }
            
            $this->info("Completed! Added/Updated {$totalPermissions} permissions across {$successCount} companies");
        }
        
        return 0;
    }
}
