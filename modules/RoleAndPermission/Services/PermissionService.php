<?php

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Modules\RoleAndPermission\Models\Role;
use Modules\RoleAndPermission\Models\Permission;
use Modules\User\Models\User;

class PermissionService
{
    /**
     * Get grouped permissions for UI display
     */
    public function getGroupedPermissions(): array
    {
        $permissions = $this->getAllPermissions();
        $groups = config('permissions.permission_groups', []);
        $grouped = [];

        foreach ($groups as $groupKey => $groupConfig) {
            $grouped[$groupKey] = [
                'config' => $groupConfig,
                'permissions' => $permissions->filter(function ($permission) use ($groupKey) {
                    return str_starts_with($permission->slug, $groupKey . '.');
                })->groupBy(function ($permission) {
                    $parts = explode('.', $permission->slug);
                    return isset($parts[1]) ? $parts[1] : 'general';
                }),
            ];
        }

        return $grouped;
    }

    /**
     * Get all permissions with caching
     */
    public function getAllPermissions(): Collection
    {
        return Cache::remember('all_permissions', 3600, function () {
            return Permission::all();
        });
    }

    /**
     * Check if user has permission with advanced logic
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        // Super admin check
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Get user permissions with caching
        $userPermissions = $this->getUserPermissions($user);

        // Direct permission check
        if ($userPermissions->contains('slug', $permission)) {
            return true;
        }

        // Wildcard permission check
        return $this->checkWildcardPermissions($permission, $userPermissions);
    }

    /**
     * Check multiple permissions with OR/AND logic
     */
    public function userHasPermissions(User $user, array $permissions, string $logic = 'AND'): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $results = array_map(fn($permission) => $this->userHasPermission($user, $permission), $permissions);

        return $logic === 'OR' ? in_array(true, $results) : !in_array(false, $results);
    }

    /**
     * Get user permissions with caching
     */
    public function getUserPermissions(User $user): Collection
    {
        return Cache::remember("user_permissions_{$user->id}", 900, function () use ($user) {
            return $user->getAllPermissions();
        });
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(User $user): bool
    {
        return Cache::remember("user_is_super_admin_{$user->id}", 900, function () use ($user) {
            return $user->hasRole('Super Admin') || 
                   $user->getAllPermissions()->contains('slug', '*') ||
                   $user->getAllPermissions()->contains('slug', 'admin.*');
        });
    }

    /**
     * Check wildcard permissions
     */
    protected function checkWildcardPermissions(string $requiredPermission, Collection $userPermissions): bool
    {
        foreach ($userPermissions as $userPermission) {
            if (str_ends_with($userPermission->slug, '*')) {
                $prefix = rtrim($userPermission->slug, '*');
                if (str_starts_with($requiredPermission, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate permission matrix for role management
     */
    public function getPermissionMatrix(): array
    {
        $permissions = $this->getGroupedPermissions();
        $actions = config('permissions.actions', []);
        $matrix = [];

        foreach ($permissions as $moduleKey => $moduleData) {
            $matrix[$moduleKey] = [
                'config' => $moduleData['config'],
                'resources' => [],
            ];

            foreach ($moduleData['permissions'] as $resourceKey => $resourcePermissions) {
                $matrix[$moduleKey]['resources'][$resourceKey] = [
                    'name' => ucwords(str_replace('-', ' ', $resourceKey)),
                    'actions' => [],
                ];

                foreach ($actions as $actionKey => $actionConfig) {
                    $hasAction = $resourcePermissions->first(function ($permission) use ($actionKey) {
                        return str_ends_with($permission->slug, '.' . $actionKey);
                    });

                    if ($hasAction) {
                        $matrix[$moduleKey]['resources'][$resourceKey]['actions'][$actionKey] = [
                            'config' => $actionConfig,
                            'permission' => $hasAction,
                        ];
                    }
                }
            }
        }

        return $matrix;
    }

    /**
     * Sync permissions from config to database
     */
    public function syncPermissions(): array
    {
        $configPermissions = config('permissions.permissions', []);
        $stats = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        DB::transaction(function () use ($configPermissions, &$stats) {
            // Get existing permissions
            $existingPermissions = Permission::all()->keyBy('slug');

            // Process config permissions
            foreach ($configPermissions as $key => $slug) {
                $permission = $existingPermissions->get($slug);

                if ($permission) {
                    // Update existing permission
                    $permission->update([
                        'name' => $this->generatePermissionName($key, $slug),
                        'description' => $this->generatePermissionDescription($key, $slug),
                    ]);
                    $stats['updated']++;
                } else {
                    // Create new permission
                    Permission::create([
                        'name' => $this->generatePermissionName($key, $slug),
                        'slug' => $slug,
                        'description' => $this->generatePermissionDescription($key, $slug),
                    ]);
                    $stats['created']++;
                }

                $existingPermissions->forget($slug);
            }

            // Delete permissions not in config
            if ($existingPermissions->isNotEmpty()) {
                Permission::whereIn('slug', $existingPermissions->keys())->delete();
                $stats['deleted'] = $existingPermissions->count();
            }
        });

        // Clear permission cache
        Cache::forget('all_permissions');
        
        return $stats;
    }

    /**
     * Generate human-readable permission name
     */
    protected function generatePermissionName(string $key, string $slug): string
    {
        // Convert permission key to readable format
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }

    /**
     * Generate permission description
     */
    protected function generatePermissionDescription(string $key, string $slug): string
    {
        $parts = explode('.', $slug);
        $action = end($parts);
        $resource = prev($parts);
        $module = reset($parts);

        return "Allows user to {$action} {$resource} in {$module} module";
    }

    /**
     * Get permission hierarchy for role templates
     */
    public function getPermissionHierarchy(): array
    {
        return config('permissions.permission_hierarchies', []);
    }

    /**
     * Apply role template with predefined permissions
     */
    public function applyRoleTemplate(Role $role, string $template): bool
    {
        $hierarchies = $this->getPermissionHierarchy();
        
        if (!isset($hierarchies[$template])) {
            return false;
        }

        $permissions = $this->expandWildcardPermissions($hierarchies[$template]);
        $role->syncPermissions($permissions);

        return true;
    }

    /**
     * Expand wildcard permissions to actual permission slugs
     */
    protected function expandWildcardPermissions(array $wildcardPermissions): array
    {
        $allPermissions = $this->getAllPermissions();
        $expandedPermissions = [];

        foreach ($wildcardPermissions as $wildcardPermission) {
            if ($wildcardPermission === '*') {
                // All permissions
                $expandedPermissions = array_merge($expandedPermissions, $allPermissions->pluck('slug')->toArray());
            } elseif (str_ends_with($wildcardPermission, '*')) {
                // Wildcard match
                $prefix = rtrim($wildcardPermission, '*');
                $matchingPermissions = $allPermissions->filter(function ($permission) use ($prefix) {
                    return str_starts_with($permission->slug, $prefix);
                })->pluck('slug')->toArray();
                
                $expandedPermissions = array_merge($expandedPermissions, $matchingPermissions);
            } else {
                // Direct permission
                $expandedPermissions[] = $wildcardPermission;
            }
        }

        return array_unique($expandedPermissions);
    }

    /**
     * Clear user permission cache
     */
    public function clearUserPermissionCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
        Cache::forget("user_is_super_admin_{$user->id}");
    }

    /**
     * Clear all permission caches
     */
    public function clearAllPermissionCaches(): void
    {
        Cache::forget('all_permissions');
        
        // Clear user-specific caches (this could be optimized with cache tags)
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
            Cache::forget("user_is_super_admin_{$userId}");
        }
    }
}
