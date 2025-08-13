<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Commands;

use Illuminate\Console\Command;
use Modules\RoleAndPermission\Services\PermissionConfigService;

class ManageModulePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'permissions:manage 
                            {action : Action to perform (list, clear-cache, show-modules, validate)}
                            {--module= : Specific module name for module-specific actions}';

    /**
     * The console command description.
     */
    protected $description = 'Manage modular permissions system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        
        match ($action) {
            'list' => $this->listPermissions(),
            'clear-cache' => $this->clearCache(),
            'show-modules' => $this->showModules(),
            'validate' => $this->validatePermissions(),
            default => $this->error("Unknown action: {$action}")
        };

        return 0;
    }

    /**
     * List all permissions
     */
    private function listPermissions(): void
    {
        $moduleName = $this->option('module');

        if ($moduleName) {
            $this->listModulePermissions($moduleName);
        } else {
            $this->listAllPermissions();
        }
    }

    /**
     * List permissions for a specific module
     */
    private function listModulePermissions(string $moduleName): void
    {
        $permissions = PermissionConfigService::getModulePermissions($moduleName);

        if (empty($permissions)) {
            $this->error("No permissions found for module: {$moduleName}");
            return;
        }

        $this->info("Permissions for module: {$moduleName}");
        $this->table(['Key', 'Value'], collect($permissions)->map(fn($value, $key) => [$key, $value]));
    }

    /**
     * List all merged permissions
     */
    private function listAllPermissions(): void
    {
        $permissions = PermissionConfigService::getMergedPermissions();

        if (empty($permissions)) {
            $this->error('No permissions found in any module');
            return;
        }

        $this->info('All merged permissions:');
        $this->table(['Key', 'Value'], collect($permissions)->map(fn($value, $key) => [$key, $value]));
    }

    /**
     * Clear permissions cache
     */
    private function clearCache(): void
    {
        PermissionConfigService::clearCache();
        $this->info('Permissions cache cleared successfully');
    }

    /**
     * Show modules with permissions
     */
    private function showModules(): void
    {
        $modules = PermissionConfigService::getModulesWithPermissions();

        if (empty($modules)) {
            $this->error('No modules with permissions found');
            return;
        }

        $this->info('Modules with permission configurations:');
        foreach ($modules as $module) {
            $permissionCount = count(PermissionConfigService::getModulePermissions($module));
            $this->line("• {$module} ({$permissionCount} permissions)");
        }
    }

    /**
     * Validate permissions for duplicates and conflicts
     */
    private function validatePermissions(): void
    {
        $this->info('Validating permission configurations...');
        
        $modules = PermissionConfigService::getModulesWithPermissions();
        $allPermissions = [];
        $duplicates = [];

        foreach ($modules as $module) {
            $modulePermissions = PermissionConfigService::getModulePermissions($module);
            
            foreach ($modulePermissions as $key => $value) {
                if (isset($allPermissions[$key])) {
                    $duplicates[] = [
                        'key' => $key,
                        'value' => $value,
                        'first_module' => $allPermissions[$key]['module'],
                        'duplicate_module' => $module
                    ];
                } else {
                    $allPermissions[$key] = [
                        'value' => $value,
                        'module' => $module
                    ];
                }
            }
        }

        if (empty($duplicates)) {
            $this->info('✅ No permission conflicts found');
        } else {
            $this->error('❌ Found permission conflicts:');
            $this->table(
                ['Key', 'Value', 'First Module', 'Duplicate Module'],
                $duplicates
            );
        }

        $this->info("Total permissions: " . count($allPermissions));
        $this->info("Total modules: " . count($modules));
    }
}
