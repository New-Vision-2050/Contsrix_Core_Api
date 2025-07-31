<?php

declare(strict_types=1);

namespace Modules\SubEntity\Observers;

use Modules\SubEntity\Models\SubEntity;
use Modules\RoleAndPermission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubEntityObserver
{
    /**
     * Handle the SubEntity "created" event.
     */
    public function created(SubEntity $subEntity): void
    {
        $this->createDefaultPermissions($subEntity);
    }

    /**
     * Handle the SubEntity "updated" event.
     */
    public function updated(SubEntity $subEntity): void
    {
        // Update permission names and keys when SubEntity name or slug changes
        if ($subEntity->isDirty('name') || $subEntity->isDirty('slug')) {
            $oldName = $subEntity->getOriginal('name');
            $oldSlug = $subEntity->getOriginal('slug');
            $this->updatePermissionNamesAndKeys($subEntity, $oldName, $oldSlug);
        }
    }

    /**
     * Handle the SubEntity "deleting" event.
     */
    public function deleting(SubEntity $subEntity): void
    {
        // Delete permissions and remove from packages/roles when SubEntity is deleted
        $this->deletePermissionsAndCleanup($subEntity);
    }

    /**
     * Create default permissions for a new SubEntity
     */
    protected function createDefaultPermissions(SubEntity $subEntity): void
    {
        if (!$subEntity->mainProgram || !$subEntity->slug) {
            return;
        }

        $module = $subEntity->mainProgram->slug;
        $resource = $subEntity->name . '*' . $subEntity->id;
        $createdPermissions = [];

        foreach (SubEntity::PERMISSION_ACTIONS as $action) {
            $permission = Permission::firstOrCreate([
                'name' => "{$module}.{$resource}.{$action}",
                "key" => "DYNAMIC." . $subEntity->slug . "_$action",

            ], [
                'status' => true,
            ]);

            // Track if this is a newly created permission
            if ($permission->wasRecentlyCreated) {
                $createdPermissions[] = $permission;
            }
        }

        // Auto-assign new permissions to main package and super-admin role
        if (!empty($createdPermissions)) {
            $this->assignPermissionsToMainPackageAndSuperAdmin($subEntity, $createdPermissions);
        }
    }

    /**
     * Update permission names and keys when SubEntity name or slug changes
     */
    protected function updatePermissionNamesAndKeys(SubEntity $subEntity, string $oldName, string $oldSlug): void
    {
        if (!$subEntity->mainProgram) {
            return;
        }

        $module = $subEntity->mainProgram->slug;
        $oldResource = $oldName . '*' . $subEntity->id;
        $newResource = $subEntity->name . '*' . $subEntity->id;

        foreach (SubEntity::PERMISSION_ACTIONS as $action) {
            $permission = Permission::where('name', "{$module}.{$oldResource}.{$action}")->first();

            if ($permission) {
                // Update both name and key
                $permission->name = "{$module}.{$newResource}.{$action}";
                $permission->key = "DYNAMIC." . $subEntity->slug . "_$action";
                $permission->save();

                Log::info('Updated SubEntity permission name and key', [
                    'sub_entity_id' => $subEntity->id,
                    'permission_id' => $permission->id,
                    'old_name' => "{$module}.{$oldResource}.{$action}",
                    'new_name' => $permission->name,
                    'old_key' => "DYNAMIC." . $oldSlug . "_$action",
                    'new_key' => $permission->key
                ]);
            }
        }
    }

    /**
     * Delete permissions and cleanup all assignments when SubEntity is deleted
     */
    protected function deletePermissionsAndCleanup(SubEntity $subEntity): void
    {
        if (!$subEntity->mainProgram) {
            return;
        }

        try {
            DB::transaction(function () use ($subEntity) {
                $module = $subEntity->mainProgram->slug;
                $resource = $subEntity->name . '*' . $subEntity->id;
                $permissionsToDelete = [];

                // Collect permissions that need to be deleted
                foreach (SubEntity::PERMISSION_ACTIONS as $action) {
                    $permission = Permission::where('name', "{$module}.{$resource}.{$action}")->first();

                    if ($permission) {
                        $permissionsToDelete[] = $permission;
                    }
                }

                if (empty($permissionsToDelete)) {
                    return;
                }

                $permissionIds = array_map(fn($p) => $p->id, $permissionsToDelete);

                // Remove permissions from ALL packages
                $this->removePermissionsFromAllPackages($subEntity, $permissionIds);

                // Remove permissions from ALL roles
                $this->removePermissionsFromAllRoles($subEntity, $permissionIds);

                // Delete the permissions
                foreach ($permissionsToDelete as $permission) {
                    $permission->delete();
                }

                Log::info('Deleted SubEntity permissions and cleaned up all assignments', [
                    'sub_entity_id' => $subEntity->id,
                    'deleted_permissions' => $permissionIds
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to delete SubEntity permissions and cleanup', [
                'error' => $e->getMessage(),
                'sub_entity' => $subEntity->id
            ]);
        }
    }

    /**
     * Assign permissions to main package and super-admin role
     */
    protected function assignPermissionsToMainPackageAndSuperAdmin(SubEntity $subEntity, array $permissions): void
    {
        try {
            DB::transaction(function () use ($subEntity, $permissions) {
                // Assign to main package
                $this->assignPermissionsToMainPackage($subEntity, $permissions);

                // Assign to super-admin role
                $this->assignPermissionsToSuperAdminRole($subEntity, $permissions);
            });
        } catch (\Exception $e) {
            Log::error('Failed to auto-assign permissions to main package and super-admin role', [
                'error' => $e->getMessage(),
                'permissions' => array_map(fn($p) => $p->id, $permissions),
                'sub_entity' => $subEntity->id
            ]);
        }
    }

    /**
     * Assign permissions to main package
     */
    protected function assignPermissionsToMainPackage(SubEntity $subEntity, array $permissions): void
    {
        $mainPackage = \Modules\Subscription\Package\Models\Package::where('name', 'Main Package')->first();

        if (!$mainPackage) {
            Log::warning('Main Package not found for auto-assignment', [
                'sub_entity' => $subEntity->id
            ]);
            return;
        }

        // Get current permissions
        $currentPermissions = $mainPackage->permissions()->pluck('permissions.id')->toArray();

        // Prepare sync data - keep existing permissions and add new ones
        $syncData = [];

        // Keep existing permissions with their current pivot data
        foreach ($mainPackage->permissions as $existingPermission) {
            $syncData[$existingPermission->id] = [
                'limit' => $existingPermission->pivot->limit
            ];
        }

        // Add new permissions without limit (null)
        foreach ($permissions as $permission) {
            if (!in_array($permission->id, $currentPermissions)) {
                $syncData[$permission->id] = [
                    'limit' => null
                ];
            }
        }

        // Sync permissions
        $mainPackage->permissions()->sync($syncData);

        Log::info('Auto-assigned permissions to Main Package', [
            'package_id' => $mainPackage->id,
            'new_permissions' => array_map(fn($p) => $p->id, $permissions),
            'sub_entity' => $subEntity->id
        ]);
    }

    /**
     * Assign permissions to super-admin role
     */
    protected function assignPermissionsToSuperAdminRole(SubEntity $subEntity, array $permissions): void
    {
        $superAdminRole = \Modules\RoleAndPermission\Models\Role::where('name', 'super-admin')
            ->where('company_id', tenant('company_id'))
            ->first();

        if (!$superAdminRole) {
            Log::warning('Super-admin role not found for auto-assignment', [
                'company_id' => tenant('company_id'),
                'sub_entity' => $subEntity->id
            ]);
            return;
        }

        // Get current role permissions
        $currentPermissions = $superAdminRole->permissions()->pluck('permissions.id')->toArray();

        // Add new permissions to the role
        $permissionsToAdd = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission->id, $currentPermissions)) {
                $permissionsToAdd[] = $permission->id;
            }
        }

        if (!empty($permissionsToAdd)) {
            // Attach new permissions (keeping existing ones)
            $superAdminRole->permissions()->attach($permissionsToAdd);

            Log::info('Auto-assigned permissions to super-admin role', [
                'role_id' => $superAdminRole->id,
                'new_permissions' => $permissionsToAdd,
                'sub_entity' => $subEntity->id
            ]);
        }
    }

    /**
     * Remove specific permissions from ALL packages that have them assigned
     */
    protected function removePermissionsFromAllPackages(SubEntity $subEntity, array $permissionIds): void
    {
        // Find all packages that have any of these permissions
        $packagesWithPermissions = \Modules\Subscription\Package\Models\Package::whereHas('permissions', function($query) use ($permissionIds) {
            $query->whereIn('permissions.id', $permissionIds);
        })->get();

        $removedFromPackages = [];
        foreach ($packagesWithPermissions as $package) {
            // Detach only the specific permissions
            $package->permissions()->detach($permissionIds);
            $removedFromPackages[] = $package->id;
        }

        if (!empty($removedFromPackages)) {
            Log::info('Removed permissions from all packages', [
                'removed_from_packages' => $removedFromPackages,
                'removed_permissions' => $permissionIds,
                'sub_entity' => $subEntity->id
            ]);
        }
    }

    /**
     * Remove specific permissions from ALL roles that have them assigned
     */
    protected function removePermissionsFromAllRoles(SubEntity $subEntity, array $permissionIds): void
    {
        // Find all roles that have any of these permissions
        $rolesWithPermissions = \Modules\RoleAndPermission\Models\Role::whereHas('permissions', function($query) use ($permissionIds) {
            $query->whereIn('permissions.id', $permissionIds);
        })->get();

        $removedFromRoles = [];
        foreach ($rolesWithPermissions as $role) {
            // Detach only the specific permissions
            $role->permissions()->detach($permissionIds);
            $removedFromRoles[] = $role->id;
        }

        if (!empty($removedFromRoles)) {
            Log::info('Removed permissions from all roles', [
                'removed_from_roles' => $removedFromRoles,
                'removed_permissions' => $permissionIds,
                'sub_entity' => $subEntity->id
            ]);
        }
    }
}
