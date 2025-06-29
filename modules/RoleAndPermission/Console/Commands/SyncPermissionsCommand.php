<?php

namespace Modules\RoleAndPermission\Console\Commands;

use Illuminate\Console\Command;
use Modules\RoleAndPermission\Services\PermissionService;
use Symfony\Component\Console\Helper\Table;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync 
                            {--force : Force sync without confirmation}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync permissions from config to database';

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
    }

    public function handle()
    {
        $this->info('🔐 Permission Sync Utility');
        $this->info('========================');

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->performDryRun();
            return;
        }

        if (!$this->option('force') && !$this->confirm('This will sync permissions from config to database. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->info('📝 Syncing permissions...');
        
        $stats = $this->permissionService->syncPermissions();

        $this->displayStats($stats);
        
        if ($stats['created'] > 0 || $stats['updated'] > 0) {
            $this->info('🧹 Clearing permission caches...');
            $this->permissionService->clearAllPermissionCaches();
        }

        $this->info('✅ Permission sync completed successfully!');
    }

    protected function performDryRun()
    {
        $configPermissions = config('permissions.permissions', []);
        $this->info("📊 Found " . count($configPermissions) . " permissions in config");

        // Group permissions by module for better display
        $groupedPermissions = [];
        foreach ($configPermissions as $key => $slug) {
            $parts = explode('.', $slug);
            $module = $parts[0] ?? 'unknown';
            $groupedPermissions[$module][] = ['key' => $key, 'slug' => $slug];
        }

        foreach ($groupedPermissions as $module => $permissions) {
            $this->info("\n📁 Module: " . ucwords($module));
            $table = new Table($this->output);
            $table->setHeaders(['Permission Key', 'Slug']);
            
            foreach ($permissions as $permission) {
                $table->addRow([$permission['key'], $permission['slug']]);
            }
            
            $table->render();
        }
    }

    protected function displayStats(array $stats)
    {
        $this->info("\n📈 Sync Results:");
        $this->info("================");
        
        if ($stats['created'] > 0) {
            $this->info("✨ Created: {$stats['created']} permissions");
        }
        
        if ($stats['updated'] > 0) {
            $this->info("🔄 Updated: {$stats['updated']} permissions");
        }
        
        if ($stats['deleted'] > 0) {
            $this->warn("🗑️  Deleted: {$stats['deleted']} permissions");
        }
        
        if ($stats['created'] == 0 && $stats['updated'] == 0 && $stats['deleted'] == 0) {
            $this->info("✅ No changes needed - everything is in sync!");
        }
    }
}
