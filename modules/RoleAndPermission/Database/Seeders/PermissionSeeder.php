<?php

namespace Modules\RoleAndPermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Models\User;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🔐 Seeding Permissions and Roles...');

        DB::transaction(function () {
            $this->seedPermissions();
            $this->seedRoles();
            $this->assignDefaultPermissions();
            $this->createSuperAdmin();
        });

        $this->command->info('✅ Permissions and Roles seeded successfully!');
    }

    /**
     * Seed permissions from config
     */
    protected function seedPermissions()
    {
        $this->command->info('📝 Creating permissions...');
        
        $configPermissions = config('permissions.permissions', []);
        $created = 0;
        $updated = 0;

        foreach ($configPermissions as $key => $slug) {
            $permission = Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $this->generatePermissionName($key, $slug),
                    'description' => $this->generatePermissionDescription($key, $slug),
                    'module' => $this->extractModule($slug),
                    'resource' => $this->extractResource($slug),
                    'action' => $this->extractAction($slug),
                ]
            );

            if ($permission->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->info("   ✨ Created: {$created} permissions");
        $this->command->info("   🔄 Updated: {$updated} permissions");
    }

    /**
     * Seed default roles
     */
    protected function seedRoles()
    {
        $this->command->info('👥 Creating roles...');

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full system access with all permissions',
                'color' => '#DC2626',
                'is_system' => true,
            ],
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Administrative access to most system features',
                'color' => '#EF4444',
                'is_system' => true,
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Managerial access to specific modules',
                'color' => '#F59E0B',
                'is_system' => true,
            ],
            [
                'name' => 'Supervisor',
                'slug' => 'supervisor',
                'description' => 'Supervisory access with limited permissions',
                'color' => '#10B981',
                'is_system' => true,
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Basic employee access',
                'color' => '#3B82F6',
                'is_system' => true,
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Basic user access',
                'color' => '#6B7280',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        $this->command->info('   ✨ Created/Updated ' . count($roles) . ' roles');
    }

    /**
     * Assign default permissions to roles based on hierarchy
     */
    protected function assignDefaultPermissions()
    {
        $this->command->info('🔗 Assigning permissions to roles...');

        $hierarchies = config('permissions.permission_hierarchies', []);
        
        foreach ($hierarchies as $roleName => $permissionPatterns) {
            $role = Role::where('slug', strtolower(str_replace('_', '-', $roleName)))->first();
            
            if (!$role) {
                continue;
            }

            $permissions = $this->expandPermissionPatterns($permissionPatterns);
            $role->syncPermissions($permissions);
            
            $this->command->info("   🎯 Assigned " . count($permissions) . " permissions to {$role->name}");
        }
    }

    /**
     * Create default super admin user
     */
    protected function createSuperAdmin()
    {
        if (config('app.env') === 'production') {
            $this->command->warn('⚠️  Skipping super admin creation in production environment');
            return;
        }

        $this->command->info('👤 Creating super admin user...');

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@constrix.com'],
            [
                'name' => 'Super Administrator',
                'email' => 'admin@constrix.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole);
            $this->command->info('   ✅ Super admin user created: admin@constrix.com / password');
        }
    }

    /**
     * Expand wildcard permission patterns to actual permissions
     */
    protected function expandPermissionPatterns(array $patterns): array
    {
        $allPermissions = Permission::all();
        $expandedPermissions = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                $expandedPermissions = array_merge($expandedPermissions, $allPermissions->pluck('slug')->toArray());
            } elseif (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                $matchingPermissions = $allPermissions->filter(function ($permission) use ($prefix) {
                    return str_starts_with($permission->slug, $prefix);
                })->pluck('slug')->toArray();
                
                $expandedPermissions = array_merge($expandedPermissions, $matchingPermissions);
            } else {
                $expandedPermissions[] = $pattern;
            }
        }

        return array_unique($expandedPermissions);
    }

    /**
     * Generate human-readable permission name
     */
    protected function generatePermissionName(string $key, string $slug): string
    {
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }

    /**
     * Generate permission description
     */
    protected function generatePermissionDescription(string $key, string $slug): string
    {
        $parts = explode('.', $slug);
        $action = end($parts);
        $resource = count($parts) > 2 ? $parts[count($parts) - 2] : 'resource';
        $module = reset($parts);

        return "Allows user to {$action} {$resource} in {$module} module";
    }

    /**
     * Extract module from permission slug
     */
    protected function extractModule(string $slug): string
    {
        $parts = explode('.', $slug);
        return $parts[0] ?? 'unknown';
    }

    /**
     * Extract resource from permission slug
     */
    protected function extractResource(string $slug): string
    {
        $parts = explode('.', $slug);
        return count($parts) > 2 ? $parts[count($parts) - 2] : ($parts[1] ?? 'unknown');
    }

    /**
     * Extract action from permission slug
     */
    protected function extractAction(string $slug): string
    {
        $parts = explode('.', $slug);
        return end($parts);
    }
}
