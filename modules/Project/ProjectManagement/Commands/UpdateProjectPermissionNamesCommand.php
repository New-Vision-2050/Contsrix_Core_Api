<?php

namespace Modules\Project\ProjectManagement\Commands;

use Illuminate\Console\Command;
use Modules\Project\ProjectManagement\Models\ProjectPermission;

class UpdateProjectPermissionNamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project-permissions:update-names 
                            {--dry-run : Preview changes without applying them}
                            {--key=* : Update specific keys only}
                            {--force : Force update without confirmation}
                            {--delete-orphaned : Delete permissions not found in config}
                            {--create-missing : Create permissions that exist in config but not in database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update project permission names from config by key mappings, create missing permissions, and optionally delete orphaned permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Loading project permissions from config...');
        
        // Load permissions from config
        $permissions = config('project-management.permissions');
        
        if (empty($permissions)) {
            $this->error('No permissions found in config. Please check modules/Project/ProjectManagement/Config/permissions.php');
            return self::FAILURE;
        }

        $this->info('Found ' . count($permissions) . ' permissions in config.');

        // Filter by specific keys if provided
        $specificKeys = $this->option('key');
        if (!empty($specificKeys)) {
            $permissions = array_intersect_key($permissions, array_flip($specificKeys));
            $this->info('Filtering to ' . count($permissions) . ' specific keys.');
        }

        if (empty($permissions)) {
            $this->error('No permissions to update after filtering.');
            return self::FAILURE;
        }

        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        $deleteOrphaned = $this->option('delete-orphaned');
        $createMissing = $this->option('create-missing');

        $updates = [];
        $notFound = [];
        $noChanges = [];
        $missing = [];

        // Check what changes need to be made
        foreach ($permissions as $key => $newName) {
            $permission = ProjectPermission::where('name', $newName)->first();
            
            if (!$permission) {
                $notFound[] = $key;
                $missing[] = $key;
                continue;
            }

            // No changes needed (permission already has correct name)
            $noChanges[] = $key;
        }

        // Check for orphaned permissions (exist in DB but not in config)
        $orphanedPermissions = [];
        if ($deleteOrphaned || !$isForced) {
            $orphanedPermissions = $this->findOrphanedPermissions($permissions);
        }

        // Display summary
        $this->displaySummary($updates, $notFound, $noChanges, $orphanedPermissions, $missing);

        if (empty($updates) && empty($orphanedPermissions) && empty($missing)) {
            $this->info('No updates needed.');
            return self::SUCCESS;
        }

        // Handle missing permissions
        if (!empty($missing)) {
            if ($createMissing) {
                if (!$isDryRun && !$isForced) {
                    if ($this->confirm('Do you want to create these missing permissions?')) {
                        $this->createMissingPermissions($missing, $permissions);
                    } else {
                        $this->info('Creation operation cancelled.');
                    }
                } elseif (!$isDryRun && $isForced) {
                    $this->createMissingPermissions($missing, $permissions);
                } else {
                    $this->warn('DRY RUN: Missing permissions would be created if confirmed.');
                }
            } else {
                $this->warn('Use --create-missing flag to create missing permissions.');
            }
        }

        // Handle orphaned permissions
        if (!empty($orphanedPermissions)) {
            $this->handleOrphanedPermissions($orphanedPermissions, $isDryRun, $isForced);
        }

        return self::SUCCESS;
    }

    /**
     * Find permissions that exist in database but not in config
     */
    private function findOrphanedPermissions(array $configPermissions): array
    {
        $configNames = array_values($configPermissions);
        
        return ProjectPermission::whereNotIn('name', $configNames)
            ->get()
            ->toArray();
    }

    /**
     * Handle orphaned permissions (ask for deletion)
     */
    private function handleOrphanedPermissions(array $orphanedPermissions, bool $isDryRun, bool $isForced): void
    {
        if (empty($orphanedPermissions)) {
            return;
        }

        $this->warn("\n🗑️  Found " . count($orphanedPermissions) . " orphaned permissions (exist in DB but not in config):");
        $this->table(
            ['Name', 'Submodule', 'Action', 'ID'],
            array_map(fn($perm) => [
                $perm['name'],
                $perm['submodule'],
                $perm['action'],
                substr($perm['id'], 0, 8) . '...'
            ], $orphanedPermissions)
        );

        if (!$isDryRun && !$isForced) {
            if ($this->confirm('Do you want to delete these orphaned permissions from the database?')) {
                $this->deleteOrphanedPermissions($orphanedPermissions);
            } else {
                $this->info('Deletion cancelled. Orphaned permissions remain in database.');
            }
        } elseif (!$isDryRun && $isForced) {
            $this->deleteOrphanedPermissions($orphanedPermissions);
        } else {
            $this->warn('DRY RUN: Orphaned permissions would be deleted if confirmed.');
        }
    }

    /**
     * Delete orphaned permissions from database
     */
    private function deleteOrphanedPermissions(array $orphanedPermissions): void
    {
        $this->info("\n🗑️  Deleting orphaned permissions...");
        
        $deleted = 0;
        $failed = 0;

        foreach ($orphanedPermissions as $permission) {
            try {
                ProjectPermission::where('id', $permission['id'])->delete();
                $this->line("✅ Deleted {$permission['name']}");
                $deleted++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to delete {$permission['name']}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\n📊 Deletion Summary:");
        $this->info("✅ Deleted: {$deleted}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
    }

    /**
     * Create missing permissions
     */
    private function createMissingPermissions(array $missing, array $permissions): void
    {
        $this->info("\n🔄 Creating missing permissions...");
        $this->warn("⚠️  Note: You need to run the seeder to create permissions with proper translations.");
        $this->warn("⚠️  Command: php artisan db:seed --class=Modules\\\\Project\\\\ProjectManagement\\\\Database\\\\Seeders\\\\ProjectPermissionsSeeder");
        
        $this->info("\nMissing permissions:");
        foreach ($missing as $key) {
            $this->line("  - {$key}: {$permissions[$key]}");
        }
    }

    /**
     * Display summary of changes to be made
     */
    private function displaySummary(array $updates, array $notFound, array $noChanges, array $orphanedPermissions = [], array $missing = []): void
    {
        if (!empty($updates)) {
            $this->info("\n📝 Permissions to update (" . count($updates) . "):");
            $this->table(
                ['Key', 'Current Name', 'New Name'],
                array_map(fn($update) => [
                    $this->formatKey($update['key']),
                    $this->formatName($update['old_name']),
                    $this->formatName($update['new_name'])
                ], $updates)
            );
        }

        if (!empty($missing)) {
            $this->warn("\n➕ Missing permissions to create (" . count($missing) . "):");
            $permissions = config('project-management.permissions');
            $this->table(
                ['Key', 'Name', 'Submodule', 'Action'],
                array_map(function($key) use ($permissions) {
                    $name = $permissions[$key];
                    $parts = explode('.', $name);
                    
                    // Extract submodule and action
                    $submodule = 'Unknown';
                    $action = 'Unknown';
                    
                    if (count($parts) >= 3) {
                        // Format: project-management.project-management*submodule.action
                        $middle = $parts[1] ?? '';
                        if (str_contains($middle, '*')) {
                            $submodule = explode('*', $middle)[1] ?? 'Unknown';
                        }
                        $action = $parts[2] ?? 'Unknown';
                    }
                    
                    return [
                        $this->formatKey($key),
                        $this->formatName($name),
                        $this->formatModule($submodule),
                        $this->formatAction($action)
                    ];
                }, $missing)
            );
        }

        if (!empty($orphanedPermissions)) {
            $this->warn("\n🗑️  Orphaned permissions to delete (" . count($orphanedPermissions) . "):");
            $this->table(
                ['Name', 'Submodule', 'Action', 'Status'],
                array_map(fn($perm) => [
                    $this->formatName($perm['name']),
                    $perm['submodule'],
                    $perm['action'],
                    '<fg=red>Orphaned</>'
                ], $orphanedPermissions)
            );
        }

        if (!empty($notFound)) {
            $this->warn("\n⚠️  Keys in config but not found in database (" . count($notFound) . "):");
            $permissions = config('project-management.permissions');
            $this->table(
                ['Key', 'Config Name', 'Status'],
                array_map(fn($key) => [
                    $this->formatKey($key),
                    $this->formatName($permissions[$key] ?? 'Unknown'),
                    '<fg=yellow>Missing</>'
                ], $notFound)
            );
        }

        if (!empty($noChanges)) {
            $this->info("\n✅ Keys with no changes needed (" . count($noChanges) . "):");
            $count = min(10, count($noChanges));
            $this->table(
                ['Key', 'Status'],
                array_map(fn($key) => [
                    $this->formatKey($key),
                    '<fg=green>Up to date</>'
                ], array_slice($noChanges, 0, $count))
            );
            
            if (count($noChanges) > 10) {
                $this->line("... and " . (count($noChanges) - 10) . " more");
            }
        }
    }

    /**
     * Format permission key for better readability
     */
    private function formatKey(string $key): string
    {
        return "<fg=cyan>{$key}</>";
    }

    /**
     * Format permission name for better readability
     */
    private function formatName(string $name): string
    {
        $truncated = strlen($name) > 50 ? substr($name, 0, 47) . '...' : $name;
        return "<fg=yellow>{$truncated}</>";
    }

    /**
     * Format module name for better readability
     */
    private function formatModule(string $module): string
    {
        return "<fg=magenta>" . ucfirst($module) . "</>";
    }

    /**
     * Format action name for better readability
     */
    private function formatAction(string $action): string
    {
        return "<fg=blue>" . ucfirst($action) . "</>";
    }
}
