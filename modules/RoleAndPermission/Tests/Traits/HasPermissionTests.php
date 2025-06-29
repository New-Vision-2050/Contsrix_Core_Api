<?php

namespace Modules\RoleAndPermission\Tests\Traits;

use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Models\User;

trait HasPermissionTests
{
    /**
     * Create a user with specific permissions
     */
    protected function createUserWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();
        
        if (!empty($permissions)) {
            $permissionModels = Permission::whereIn('slug', $permissions)->get();
            $user->givePermissionTo($permissionModels);
        }

        return $user;
    }

    /**
     * Create a user with a specific role
     */
    protected function createUserWithRole(string $roleName, array $additionalPermissions = []): User
    {
        $user = User::factory()->create();
        
        $role = Role::where('slug', $roleName)->first();
        if (!$role) {
            $role = Role::create([
                'name' => ucwords(str_replace('-', ' ', $roleName)),
                'slug' => $roleName,
                'description' => "Test role: {$roleName}",
            ]);
        }
        
        $user->assignRole($role);
        
        if (!empty($additionalPermissions)) {
            $permissionModels = Permission::whereIn('slug', $additionalPermissions)->get();
            $user->givePermissionTo($permissionModels);
        }

        return $user;
    }

    /**
     * Create super admin user for testing
     */
    protected function createSuperAdmin(): User
    {
        $user = User::factory()->create();
        
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Super administrator with all permissions',
                'is_system' => true,
            ]
        );
        
        $user->assignRole($superAdminRole);
        
        return $user;
    }

    /**
     * Assert user has permission
     */
    protected function assertUserHasPermission(User $user, string $permission): void
    {
        $this->assertTrue(
            $user->can($permission),
            "User does not have permission: {$permission}"
        );
    }

    /**
     * Assert user does not have permission
     */
    protected function assertUserDoesNotHavePermission(User $user, string $permission): void
    {
        $this->assertFalse(
            $user->can($permission),
            "User has permission they shouldn't have: {$permission}"
        );
    }

    /**
     * Assert endpoint requires permission
     */
    protected function assertEndpointRequiresPermission(string $method, string $uri, string $permission): void
    {
        // Test without permission
        $userWithoutPermission = $this->createUserWithPermissions([]);
        $response = $this->actingAs($userWithoutPermission)->{strtolower($method)}($uri);
        $response->assertStatus(403);

        // Test with permission
        $userWithPermission = $this->createUserWithPermissions([$permission]);
        $response = $this->actingAs($userWithPermission)->{strtolower($method)}($uri);
        $response->assertStatus(200);
    }

    /**
     * Assert endpoint requires any of the permissions (OR logic)
     */
    protected function assertEndpointRequiresAnyPermission(string $method, string $uri, array $permissions): void
    {
        // Test without any permission
        $userWithoutPermission = $this->createUserWithPermissions([]);
        $response = $this->actingAs($userWithoutPermission)->{strtolower($method)}($uri);
        $response->assertStatus(403);

        // Test with each permission individually
        foreach ($permissions as $permission) {
            $userWithPermission = $this->createUserWithPermissions([$permission]);
            $response = $this->actingAs($userWithPermission)->{strtolower($method)}($uri);
            $response->assertStatus(200);
        }
    }

    /**
     * Assert endpoint requires all permissions (AND logic)
     */
    protected function assertEndpointRequiresAllPermissions(string $method, string $uri, array $permissions): void
    {
        // Test without all permissions
        for ($i = 0; $i < count($permissions); $i++) {
            $partialPermissions = array_slice($permissions, 0, $i);
            if (!empty($partialPermissions)) {
                $userWithPartialPermissions = $this->createUserWithPermissions($partialPermissions);
                $response = $this->actingAs($userWithPartialPermissions)->{strtolower($method)}($uri);
                $response->assertStatus(403);
            }
        }

        // Test with all permissions
        $userWithAllPermissions = $this->createUserWithPermissions($permissions);
        $response = $this->actingAs($userWithAllPermissions)->{strtolower($method)}($uri);
        $response->assertStatus(200);
    }

    /**
     * Test permission inheritance through roles
     */
    protected function assertRoleHasPermissions(string $roleName, array $expectedPermissions): void
    {
        $role = Role::where('slug', $roleName)->first();
        $this->assertNotNull($role, "Role {$roleName} does not exist");

        $rolePermissions = $role->permissions->pluck('slug')->toArray();
        
        foreach ($expectedPermissions as $permission) {
            $this->assertContains(
                $permission,
                $rolePermissions,
                "Role {$roleName} does not have permission: {$permission}"
            );
        }
    }

    /**
     * Test wildcard permission matching
     */
    protected function assertWildcardPermissionWorks(string $wildcardPermission, array $expectedMatches): void
    {
        $user = $this->createUserWithPermissions([$wildcardPermission]);
        
        foreach ($expectedMatches as $specificPermission) {
            $this->assertTrue(
                $user->can($specificPermission),
                "Wildcard permission {$wildcardPermission} should match {$specificPermission}"
            );
        }
    }

    /**
     * Generate test data for permission testing
     */
    protected function getTestPermissions(): array
    {
        return [
            'users.user.view',
            'users.user.create',
            'users.user.edit',
            'users.user.delete',
            'companies.company.view',
            'companies.company.create',
            'company-profile.legal-data.view',
            'company-profile.legal-data.create',
        ];
    }

    /**
     * Setup permissions for testing
     */
    protected function setupTestPermissions(): void
    {
        $testPermissions = $this->getTestPermissions();
        
        foreach ($testPermissions as $permissionSlug) {
            Permission::firstOrCreate(
                ['slug' => $permissionSlug],
                [
                    'name' => ucwords(str_replace(['.', '-'], ' ', $permissionSlug)),
                    'description' => "Test permission: {$permissionSlug}",
                ]
            );
        }
    }

    /**
     * Clean up test permissions
     */
    protected function cleanupTestPermissions(): void
    {
        $testPermissions = $this->getTestPermissions();
        Permission::whereIn('slug', $testPermissions)->delete();
    }

    /**
     * Assert API response includes permission context
     */
    protected function assertResponseHasPermissionContext($response, array $expectedActions = []): void
    {
        $response->assertJsonStructure([
            '_permissions' => [
                'available_actions',
                'is_super_admin'
            ]
        ]);

        if (!empty($expectedActions)) {
            $responseData = $response->json();
            $availableActions = array_keys($responseData['_permissions']['available_actions']);
            
            foreach ($expectedActions as $action) {
                $this->assertContains(
                    $action,
                    $availableActions,
                    "Expected action {$action} not found in permission context"
                );
            }
        }
    }

    /**
     * Test pagination with permission filtering
     */
    protected function assertPaginationRespectsPermissions(string $endpoint, string $requiredPermission): void
    {
        // Create test data
        $this->setupTestData();
        
        // Test without permission
        $userWithoutPermission = $this->createUserWithPermissions([]);
        $response = $this->actingAs($userWithoutPermission)->get($endpoint);
        $response->assertStatus(403);

        // Test with permission
        $userWithPermission = $this->createUserWithPermissions([$requiredPermission]);
        $response = $this->actingAs($userWithPermission)->get($endpoint);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'links',
                     'meta'
                 ]);
    }

    /**
     * Setup test data - to be implemented by specific test classes
     */
    protected function setupTestData(): void
    {
        // Override in specific test classes to create relevant test data
    }
}
