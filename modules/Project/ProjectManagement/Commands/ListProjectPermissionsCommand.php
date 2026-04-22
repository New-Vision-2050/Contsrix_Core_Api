<?php

namespace Modules\Project\ProjectManagement\Commands;

use Illuminate\Console\Command;
use Modules\Project\ProjectManagement\Models\ProjectPermission;

class ListProjectPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project-permissions:list 
                            {--submodule= : Filter by submodule}
                            {--action= : Filter by action}
                            {--active : Show only active permissions}
                            {--config : Show config keys instead of names}
                            {--count : Show only count}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all project permissions with optional filters';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📋 Project Permissions List');
        $this->line('');

        // Build query
        $query = ProjectPermission::query();

        // Apply filters
        if ($submodule = $this->option('submodule')) {
            $query->where('submodule', $submodule);
            $this->line("🔍 Filtering by submodule: <fg=cyan>{$submodule}</>");
        }

        if ($action = $this->option('action')) {
            $query->where('action', $action);
            $this->line("🔍 Filtering by action: <fg=cyan>{$action}</>");
        }

        if ($this->option('active')) {
            $query->where('is_active', true);
            $this->line("🔍 Showing only active permissions");
        }

        $permissions = $query->orderBy('submodule')->orderBy('action')->get();

        if ($permissions->isEmpty()) {
            $this->warn('No permissions found matching the criteria.');
            return self::SUCCESS;
        }

        // Show count only
        if ($this->option('count')) {
            $this->info("\n📊 Total permissions: " . $permissions->count());
            return self::SUCCESS;
        }

        // Load config for key mapping
        $configPermissions = config('project-management.permissions', []);
        $nameToKeyMap = array_flip($configPermissions);

        // Prepare table data
        $showConfigKeys = $this->option('config');
        $headers = $showConfigKeys 
            ? ['Config Key', 'Submodule', 'Action', 'Title (AR)', 'Title (EN)', 'Active']
            : ['Permission Name', 'Submodule', 'Action', 'Title (AR)', 'Title (EN)', 'Active'];

        $rows = [];
        foreach ($permissions as $permission) {
            $identifier = $showConfigKeys 
                ? ($nameToKeyMap[$permission->name] ?? 'N/A')
                : $permission->name;

            $rows[] = [
                $this->formatIdentifier($identifier, $showConfigKeys),
                $this->formatSubmodule($permission->submodule),
                $this->formatAction($permission->action),
                $this->truncate($permission->getTranslation('title', 'ar'), 30),
                $this->truncate($permission->getTranslation('title', 'en'), 30),
                $permission->is_active ? '<fg=green>✓</>' : '<fg=red>✗</>',
            ];
        }

        $this->line('');
        $this->table($headers, $rows);

        // Summary
        $this->line('');
        $this->info('📊 Summary:');
        $this->line("Total permissions: <fg=cyan>{$permissions->count()}</>");
        $this->line("Active: <fg=green>{$permissions->where('is_active', true)->count()}</>");
        $this->line("Inactive: <fg=red>{$permissions->where('is_active', false)->count()}</>");

        // Group by submodule
        $bySubmodule = $permissions->groupBy('submodule');
        $this->line('');
        $this->info('📁 By Submodule:');
        foreach ($bySubmodule as $submodule => $perms) {
            $this->line("  <fg=magenta>{$submodule}</>: {$perms->count()} permissions");
        }

        return self::SUCCESS;
    }

    /**
     * Format identifier (config key or permission name)
     */
    private function formatIdentifier(string $identifier, bool $isConfigKey): string
    {
        if ($isConfigKey) {
            return "<fg=cyan>{$identifier}</>";
        }
        
        $truncated = $this->truncate($identifier, 50);
        return "<fg=yellow>{$truncated}</>";
    }

    /**
     * Format submodule name
     */
    private function formatSubmodule(string $submodule): string
    {
        return "<fg=magenta>" . ucfirst($submodule) . "</>";
    }

    /**
     * Format action name
     */
    private function formatAction(string $action): string
    {
        return "<fg=blue>" . ucfirst($action) . "</>";
    }

    /**
     * Truncate string with ellipsis
     */
    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }
}
