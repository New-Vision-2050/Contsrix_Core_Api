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
                            {--delete-orphaned : Delete permissions not found in config}
                            {--create-missing : Create permissions that exist in config but not in database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update permission names from config by key mappings, create missing permissions, and optionally delete orphaned permissions';

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
        $createMissing = $this->option('create-missing');

        $updates = [];
        $notFound = [];
        $noChanges = [];
        $missing = [];
        $duplicates = [];

        // Check what changes need to be made
        foreach ($permissions as $key => $newName) {
            $permissionsWithKey = Permission::where('key', $key)->get();
            
            if ($permissionsWithKey->isEmpty()) {
                $notFound[] = $key;
                $missing[] = $key;
                continue;
            }

            // Check for duplicates
            if ($permissionsWithKey->count() > 1) {
                $duplicates[$key] = $permissionsWithKey->toArray();
                continue; // Skip updates for duplicates until cleaned
            }

            $permission = $permissionsWithKey->first();

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
        $this->displaySummary($updates, $notFound, $noChanges, $orphanedPermissions, $missing, $duplicates);

        if (empty($updates) && empty($orphanedPermissions) && empty($missing) && empty($duplicates)) {
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

        // Handle missing permissions
        if (!empty($missing)) {
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
        }

        // Handle orphaned permissions
        if (!empty($orphanedPermissions)) {
            $this->handleOrphanedPermissions($orphanedPermissions, $isDryRun, $isForced);
        }

        // Handle duplicate permissions
        if (!empty($duplicates)) {
            $this->handleDuplicatePermissions($duplicates, $isDryRun, $isForced);
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
     * Create missing permissions
     */
    private function createMissingPermissions(array $missing, array $permissions): void
    {
        $this->info("\n🔄 Creating missing permissions...");
        
        $created = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($missing as $key) {
            $success = $this->createSinglePermission($key, $permissions[$key]);
            
            if ($success === true) {
                $created++;
            } elseif ($success === false) {
                $failed++;
            } else { // null means skipped
                $skipped++;
            }
        }

        $this->info("\n📊 Creation Summary:");
        $this->info("✅ Created: {$created}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
        if ($skipped > 0) {
            $this->warn("⏭️  Skipped: {$skipped}");
        }
    }

    /**
     * Create a single permission with retry functionality
     * 
     * @return bool|null true = success, false = failed permanently, null = skipped
     */
    private function createSinglePermission(string $originalKey, string $originalName): ?bool
    {
        $currentKey = $originalKey;
        $currentName = $originalName;
        $attempts = 0;
        $maxAttempts = 5;

        while ($attempts < $maxAttempts) {
            try {
                Permission::create(['key' => $currentKey, 'name' => $currentName]);
                $this->line("✅ Created {$currentKey}: {$currentName}");
                return true;
            } catch (\Exception $e) {
                $attempts++;
                $this->error("❌ Failed to create {$currentKey}: " . $e->getMessage());
                
                // If it's the last attempt, don't offer retry
                if ($attempts >= $maxAttempts) {
                    $this->error("Max attempts reached for {$currentKey}. Skipping.");
                    return false;
                }

                // Ask if user wants to retry with corrections
                if (!$this->confirm("Do you want to correct the key/name and try again?")) {
                    return null; // Skip this permission
                }

                // Get corrected values
                $this->info("Current values:");
                $this->line("  Key: {$currentKey}");
                $this->line("  Name: {$currentName}");
                
                $newKey = $this->ask("Enter corrected key (press enter to keep current)", $currentKey);
                $newName = $this->ask("Enter corrected name (press enter to keep current)", $currentName);
                
                // Update current values for next attempt
                $currentKey = $newKey ?: $currentKey;
                $currentName = $newName ?: $currentName;
                
                $this->info("Retrying with:");
                $this->line("  Key: {$currentKey}");
                $this->line("  Name: {$currentName}");
            }
        }

        return false;
    }

    /**
     * Handle duplicate permissions
     */
    private function handleDuplicatePermissions(array $duplicates, bool $isDryRun, bool $isForced): void
    {
        if (empty($duplicates)) {
            return;
        }

        $this->warn("\n🚨  Found " . count($duplicates) . " duplicate permissions:");
        foreach ($duplicates as $key => $permissions) {
            $this->line("  Key: {$key}");
            $this->table(
                ['ID', 'Name'],
                array_map(fn($perm) => [
                    $perm['id'],
                    $perm['name']
                ], $permissions)
            );
        }

        if (!$isDryRun && !$isForced) {
            if ($this->confirm('Do you want to delete these duplicate permissions from the database?')) {
                $this->deleteDuplicatePermissions($duplicates);
            } else {
                $this->info('Deletion cancelled. Duplicate permissions remain in database.');
            }
        } elseif (!$isDryRun && $isForced) {
            $this->deleteDuplicatePermissions($duplicates);
        } else {
            $this->warn('DRY RUN: Duplicate permissions would be deleted if confirmed.');
        }
    }

    /**
     * Delete duplicate permissions from database and create correct one from config
     */
    private function deleteDuplicatePermissions(array $duplicates): void
    {
        $this->info("\n🗑️  Cleaning up duplicate permissions...");
        
        $permissions = config('permissions.permissions');
        $deleted = 0;
        $created = 0;
        $failed = 0;

        foreach ($duplicates as $key => $permissionsList) {
            try {
                // Delete all duplicates for this key
                foreach ($permissionsList as $permission) {
                    Permission::where('id', $permission['id'])->delete();
                    $this->line("🗑️  Deleted duplicate {$key}: {$permission['name']}");
                    $deleted++;
                }

                // Create one correct permission from config
                if (isset($permissions[$key])) {
                    Permission::create(['key' => $key, 'name' => $permissions[$key]]);
                    $this->line("✅ Created correct {$key}: {$permissions[$key]}");
                    $created++;
                } else {
                    $this->warn("⚠️  No config found for key {$key}, only deleted duplicates");
                }

            } catch (\Exception $e) {
                $this->error("❌ Failed to process {$key}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\n📊 Cleanup Summary:");
        $this->info("🗑️  Deleted: {$deleted}");
        $this->info("✅ Created: {$created}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }
    }

    /**
     * Display summary of changes to be made
     */
    private function displaySummary(array $updates, array $notFound, array $noChanges, array $orphanedPermissions = [], array $missing = [], array $duplicates = []): void
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
            $permissions = config('permissions.permissions');
            $this->table(
                ['Key', 'Name', 'Module', 'Action'],
                array_map(function($key) use ($permissions) {
                    $name = $permissions[$key];
                    $parts = explode('.', $name);
                    $module = $parts[0] ?? 'Unknown';
                    $action = end($parts) ?? 'Unknown';
                    
                    return [
                        $this->formatKey($key),
                        $this->formatName($name),
                        $this->formatModule($module),
                        $this->formatAction($action)
                    ];
                }, $missing)
            );
        }

        if (!empty($orphanedPermissions)) {
            $this->warn("\n🗑️  Orphaned permissions to delete (" . count($orphanedPermissions) . "):");
            $this->table(
                ['Key', 'Name', 'ID', 'Status'],
                array_map(fn($perm) => [
                    $this->formatKey($perm['key']),
                    $this->formatName($perm['name']),
                    substr($perm['id'], 0, 8) . '...',
                    '<fg=red>Orphaned</>'
                ], $orphanedPermissions)
            );
        }

        if (!empty($duplicates)) {
            $this->warn("\n🚨  Duplicate permissions to delete (" . count($duplicates) . "):");
            foreach ($duplicates as $key => $permissions) {
                $this->line("  Key: {$key}");
                $this->table(
                    ['ID', 'Name', 'Status'],
                    array_map(fn($perm) => [
                        $perm['id'],
                        $perm['name'],
                        '<fg=red>Duplicate</>'
                    ], $permissions)
                );
            }
        }

        if (!empty($notFound)) {
            $this->warn("\n⚠️  Keys in config but not found in database (" . count($notFound) . "):");
            $permissions = config('permissions.permissions');
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
            $this->table(
                ['Key', 'Status'],
                array_map(fn($key) => [
                    $this->formatKey($key),
                    '<fg=green>Up to date</>'
                ], array_slice($noChanges, 0, 10)) // Show only first 10 for brevity
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
        // Add color and make it more readable
        return "<fg=cyan>{$key}</>";
    }

    /**
     * Format permission name for better readability
     */
    private function formatName(string $name): string
    {
        // Truncate long names and add color
        $truncated = strlen($name) > 40 ? substr($name, 0, 37) . '...' : $name;
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
