<?php

namespace Modules\RoleAndPermission\Commands;

use Illuminate\Console\Command;
use Modules\RoleAndPermission\Models\Permission;

class UpdatePermissionNamesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:update-names 
                            {--dry-run : Preview changes without applying them}
                            {--key=* : Update specific keys only}
                            {--force : Force update without confirmation}
                            {--delete-orphaned : Delete permissions not found in config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update permission names from config by key mappings and optionally delete orphaned permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Loading permissions from config...');
        
        // Load permissions from config
        $permissions = config('permissions.permissions');
        
        if (empty($permissions)) {
            $this->error('No permissions found in config. Please check config/permissions.php');
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

        $updates = [];
        $notFound = [];
        $noChanges = [];

        // Check what changes need to be made
        foreach ($permissions as $key => $newName) {
            $permission = Permission::where('key', $key)->first();
            
            if (!$permission) {
                $notFound[] = $key;
                continue;
            }

            if ($permission->name === $newName) {
                $noChanges[] = $key;
                continue;
            }

            $updates[] = [
                'key' => $key,
                'old_name' => $permission->name,
                'new_name' => $newName,
                'permission' => $permission
            ];
        }

        // Check for orphaned permissions (exist in DB but not in config)
        $orphanedPermissions = [];
        if ($deleteOrphaned || !$isForced) {
            $orphanedPermissions = $this->findOrphanedPermissions($permissions);
        }

        // Display summary
        $this->displaySummary($updates, $notFound, $noChanges, $orphanedPermissions);

        if (empty($updates) && empty($orphanedPermissions)) {
            $this->info('No updates needed.');
            return self::SUCCESS;
        }

        // Handle updates
        if (!empty($updates)) {
            // Confirm before proceeding (unless dry-run or forced)
            if (!$isDryRun && !$isForced) {
                if (!$this->confirm('Do you want to proceed with these updates?')) {
                    $this->info('Update operation cancelled.');
                    return self::SUCCESS;
                }
            }

            // Apply updates
            if (!$isDryRun) {
                $this->applyUpdates($updates);
            } else {
                $this->warn('DRY RUN: No updates were applied.');
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
        $configKeys = array_keys($configPermissions);
        
        return Permission::whereNotNull('key')
            ->whereNotIn('key', $configKeys)
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
            ['Key', 'Name', 'ID'],
            array_map(fn($perm) => [
                $perm['key'],
                $perm['name'],
                $perm['id']
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
                Permission::where('id', $permission['id'])->delete();
                $this->line("✅ Deleted {$permission['key']}: {$permission['name']}");
                $deleted++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to delete {$permission['key']}: " . $e->getMessage());
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
     * Display summary of changes to be made
     */
    private function displaySummary(array $updates, array $notFound, array $noChanges, array $orphanedPermissions = []): void
    {
        if (!empty($updates)) {
            $this->info("\n📝 Permissions to update (" . count($updates) . "):");
            $this->table(
                ['Key', 'Current Name', 'New Name'],
                array_map(fn($update) => [
                    $update['key'],
                    $update['old_name'],
                    $update['new_name']
                ], $updates)
            );
        }

        if (!empty($notFound)) {
            $this->warn("\n⚠️  Keys not found in database (" . count($notFound) . "):");
            foreach ($notFound as $key) {
                $this->line("  - {$key}");
            }
        }

        if (!empty($noChanges)) {
            $this->info("\n✅ Keys with no changes needed (" . count($noChanges) . "):");
            foreach ($noChanges as $key) {
                $this->line("  - {$key}");
            }
        }

        if (!empty($orphanedPermissions)) {
            $this->warn("\n🗑️  Orphaned permissions found (" . count($orphanedPermissions) . "):");
            $this->line("   These permissions exist in database but not in config.");
        }
    }

    /**
     * Apply the updates to the database
     */
    private function applyUpdates(array $updates): void
    {
        $this->info("\n🔄 Applying updates...");
        
        $updated = 0;
        $failed = 0;

        foreach ($updates as $update) {
            try {
                $update['permission']->update(['name' => $update['new_name']]);
                $this->line("✅ Updated {$update['key']}: {$update['old_name']} → {$update['new_name']}");
                $updated++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to update {$update['key']}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\n📊 Update Summary:");
        $this->info("✅ Updated: {$updated}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
    }
}
