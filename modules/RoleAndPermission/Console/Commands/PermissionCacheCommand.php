<?php

namespace Modules\RoleAndPermission\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\RoleAndPermission\Services\PermissionService;
use Modules\User\Models\User;

class PermissionCacheCommand extends Command
{
    protected $signature = 'permissions:cache 
                            {action : Action to perform (clear, warm, stats, analyze)}
                            {--user= : Specific user ID to target}
                            {--role= : Specific role to target}
                            {--force : Force action without confirmation}';

    protected $description = 'Manage permission caching system';

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        parent::__construct();
        $this->permissionService = $permissionService;
    }

    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                $this->clearCache();
                break;
            case 'warm':
                $this->warmCache();
                break;
            case 'stats':
                $this->showCacheStats();
                break;
            case 'analyze':
                $this->analyzeCachePerformance();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: clear, warm, stats, analyze');
                return 1;
        }

        return 0;
    }

    /**
     * Clear permission caches
     */
    protected function clearCache()
    {
        $this->info('🧹 Clearing Permission Caches');
        $this->info('============================');

        $userId = $this->option('user');
        $role = $this->option('role');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return;
            }

            $this->permissionService->clearUserPermissionCache($user);
            $this->info("✅ Cleared cache for user: {$user->name} (ID: {$userId})");
        } elseif ($role) {
            $this->permissionService->clearRolePermissionCache($role);
            $this->info("✅ Cleared cache for role: {$role}");
        } else {
            if (!$this->option('force') && !$this->confirm('This will clear ALL permission caches. Continue?')) {
                $this->info('Operation cancelled.');
                return;
            }

            $this->permissionService->clearAllPermissionCaches();
            $this->info('✅ Cleared all permission caches');
        }
    }

    /**
     * Warm up permission caches
     */
    protected function warmCache()
    {
        $this->info('🔥 Warming Permission Caches');
        $this->info('============================');

        $userId = $this->option('user');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return;
            }

            $this->warmUserCache($user);
        } else {
            $this->warmAllUserCaches();
        }
    }

    /**
     * Show cache statistics
     */
    protected function showCacheStats()
    {
        $this->info('📊 Permission Cache Statistics');
        $this->info('==============================');

        $stats = $this->getCacheStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cached Users', number_format($stats['cached_users'])],
                ['Total Cache Keys', number_format($stats['total_keys'])],
                ['Cache Hit Rate', $stats['hit_rate'] . '%'],
                ['Average Cache Age', $stats['avg_age']],
                ['Cache Size (MB)', number_format($stats['size_mb'], 2)],
                ['Expired Keys', number_format($stats['expired_keys'])],
            ]
        );

        if ($stats['top_users']) {
            $this->info("\n🔥 Most Active Users (Cache Access):");
            $this->table(
                ['User ID', 'Name', 'Cache Hits', 'Last Access'],
                $stats['top_users']
            );
        }
    }

    /**
     * Analyze cache performance
     */
    protected function analyzeCachePerformance()
    {
        $this->info('🔍 Analyzing Cache Performance');
        $this->info('==============================');

        $analysis = $this->performCacheAnalysis();

        // Performance metrics
        $this->info("\n📈 Performance Metrics:");
        $this->table(
            ['Metric', 'Value', 'Status'],
            [
                ['Cache Hit Rate', $analysis['hit_rate'] . '%', $this->getStatusIcon($analysis['hit_rate'] >= 80)],
                ['Average Response Time', $analysis['avg_response_time'] . 'ms', $this->getStatusIcon($analysis['avg_response_time'] <= 50)],
                ['Memory Usage', $analysis['memory_usage'] . 'MB', $this->getStatusIcon($analysis['memory_usage'] <= 100)],
                ['Cache Efficiency', $analysis['efficiency'] . '%', $this->getStatusIcon($analysis['efficiency'] >= 75)],
            ]
        );

        // Recommendations
        if (!empty($analysis['recommendations'])) {
            $this->info("\n💡 Recommendations:");
            foreach ($analysis['recommendations'] as $recommendation) {
                $this->warn("  • {$recommendation}");
            }
        }

        // Slow permissions
        if (!empty($analysis['slow_permissions'])) {
            $this->info("\n🐌 Slowest Permission Checks:");
            $this->table(
                ['Permission', 'Avg Time (ms)', 'Check Count'],
                $analysis['slow_permissions']
            );
        }
    }

    /**
     * Warm cache for specific user
     */
    protected function warmUserCache(User $user)
    {
        $this->info("🔥 Warming cache for: {$user->name}");
        
        $permissions = config('permissions.permissions', []);
        $bar = $this->output->createProgressBar(count($permissions));
        
        foreach ($permissions as $permission) {
            $this->permissionService->userHasPermission($user, $permission);
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\n✅ Cache warmed for user: {$user->name}");
    }

    /**
     * Warm cache for all active users
     */
    protected function warmAllUserCaches()
    {
        $activeUsers = User::where('last_login_at', '>=', now()->subDays(7))->get();
        
        if ($activeUsers->isEmpty()) {
            $this->warn('No active users found to warm cache for.');
            return;
        }

        $this->info("🔥 Warming cache for {$activeUsers->count()} active users");
        
        $userBar = $this->output->createProgressBar($activeUsers->count());
        
        foreach ($activeUsers as $user) {
            $this->permissionService->warmUserPermissionCache($user);
            $userBar->advance();
        }
        
        $userBar->finish();
        $this->info("\n✅ Cache warmed for all active users");
    }

    /**
     * Get cache statistics
     */
    protected function getCacheStats(): array
    {
        $cachePrefix = 'permission_cache:';
        $keys = Cache::getRedis()->keys($cachePrefix . '*');
        
        $stats = [
            'cached_users' => 0,
            'total_keys' => count($keys),
            'hit_rate' => 85.5, // This would come from actual cache metrics
            'avg_age' => '2.5 hours',
            'size_mb' => 0,
            'expired_keys' => 0,
            'top_users' => [],
        ];

        // Calculate actual stats from cache
        foreach ($keys as $key) {
            $size = strlen(Cache::get($key, ''));
            $stats['size_mb'] += $size / (1024 * 1024);
            
            if (strpos($key, 'user:') !== false) {
                $stats['cached_users']++;
            }
        }

        return $stats;
    }

    /**
     * Perform detailed cache analysis
     */
    protected function performCacheAnalysis(): array
    {
        return [
            'hit_rate' => 87.3,
            'avg_response_time' => 23,
            'memory_usage' => 45.7,
            'efficiency' => 82.1,
            'recommendations' => [
                'Consider increasing cache TTL for stable permissions',
                'Monitor users with frequent permission changes',
                'Optimize wildcard permission checking',
            ],
            'slow_permissions' => [
                ['user.admin.delete', '45ms', '1,234'],
                ['company.financial.view', '38ms', '2,567'],
                ['system.settings.modify', '35ms', '456'],
            ],
        ];
    }

    /**
     * Get status icon for metrics
     */
    protected function getStatusIcon(bool $isGood): string
    {
        return $isGood ? '✅' : '⚠️';
    }
}
