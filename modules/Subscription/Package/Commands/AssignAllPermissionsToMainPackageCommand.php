<?php

namespace Modules\Subscription\Package\Commands;

use Illuminate\Console\Command;
use Modules\Subscription\Package\Models\Package;
use Modules\RoleAndPermission\Models\Permission;
use Illuminate\Support\Facades\DB;

class AssignAllPermissionsToMainPackageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:assign-all-permissions-to-main 
                            {--dry-run : Preview the changes without applying them}
                            {--limit= : Set a default limit for permissions (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all system permissions to the Main Package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $defaultLimit = $this->option('limit');

        $this->info('Starting assignment of all permissions to Main Package...');

        try {
            // Find the Main Package
            $mainPackage = Package::where('name', 'Main Package')->first();
            
            if (!$mainPackage) {
                $this->error('Main Package not found! Please ensure the Main Package exists in the database.');
                return self::FAILURE;
            }

            $this->info("Found Main Package: {$mainPackage->name} (ID: {$mainPackage->id})");

            // Get all permissions
            $allPermissions = Permission::all();
            $totalPermissions = $allPermissions->count();

            if ($totalPermissions === 0) {
                $this->warn('No permissions found in the system.');
                return self::SUCCESS;
            }

            $this->info("Found {$totalPermissions} permissions in the system.");

            // Get currently assigned permissions
            $currentPermissions = $mainPackage->permissions()->pluck('permissions.id')->toArray();
            $currentCount = count($currentPermissions);

            $this->info("Main Package currently has {$currentCount} permissions assigned.");

            // Filter permissions that are not yet assigned
            $permissionsToAssign = $allPermissions->filter(function ($permission) use ($currentPermissions) {
                return !in_array($permission->id, $currentPermissions);
            });

            $newPermissionsCount = $permissionsToAssign->count();

            if ($newPermissionsCount === 0) {
                $this->info('All permissions are already assigned to the Main Package.');
                return self::SUCCESS;
            }

            // Show what will be assigned
            $this->info("Permissions to be assigned: {$newPermissionsCount}");
            
            if ($this->option('verbose')) {
                $this->table(
                    ['ID', 'Permission Key', 'Permission Name'],
                    $permissionsToAssign->map(function ($permission) {
                        return [
                            $permission->id,
                            $permission->key,
                            $permission->name
                        ];
                    })->toArray()
                );
            }

            if ($isDryRun) {
                $this->warn('DRY RUN MODE: No changes will be made.');
                $this->info("Would assign {$newPermissionsCount} new permissions to Main Package.");
                if ($defaultLimit) {
                    $this->info("Would set default limit to: {$defaultLimit}");
                }
                return self::SUCCESS;
            }

            // Confirm before proceeding
            if (!$this->confirm("Are you sure you want to assign {$newPermissionsCount} permissions to the Main Package?")) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            // Prepare sync data
            $syncData = [];
            
            // Keep existing permissions with their current pivot data
            foreach ($mainPackage->permissions as $existingPermission) {
                $syncData[$existingPermission->id] = [
                    'limit' => $existingPermission->pivot->limit
                ];
            }

            // Add new permissions
            foreach ($permissionsToAssign as $permission) {
                $syncData[$permission->id] = [
                    'limit' => $defaultLimit
                ];
            }

            // Perform the assignment within a transaction
            DB::transaction(function () use ($mainPackage, $syncData) {
                $mainPackage->permissions()->sync($syncData);
            });

            $this->info("Successfully assigned {$newPermissionsCount} permissions to the Main Package!");
            $this->info("Total permissions now assigned: " . count($syncData));

            // Show summary
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Permissions in System', $totalPermissions],
                    ['Previously Assigned', $currentCount],
                    ['Newly Assigned', $newPermissionsCount],
                    ['Total Now Assigned', count($syncData)],
                ]
            );

        } catch (\Exception $e) {
            $this->error('Error occurred while assigning permissions: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
