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

        // Check what changes need to be made
        foreach ($permissions as $key => $newName) {
            $permission = Permission::where('key', $key)->first();
            
            if (!$permission) {
                $notFound[] = $key;
                $missing[] = $key;
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
        $this->displaySummary($updates, $notFound, $noChanges, $orphanedPermissions, $missing);

        if (empty($updates) && empty($orphanedPermissions) && empty($missing)) {
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
     * Display summary of changes to be made
     */
    private function displaySummary(array $updates, array $notFound, array $noChanges, array $orphanedPermissions = [], array $missing = []): void
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

        if (!empty($missing)) {
            $this->warn("\n⚠️  Missing permissions found (" . count($missing) . "):");
            $this->line("   These permissions exist in config but not in database.");
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
