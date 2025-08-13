<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\RoleAndPermission\Models\Role;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Company\CompanyCore\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sync all permissions in the system to super-admin and admin roles
     * across all companies.
     */
    public function up(): void
    {
        try {
            // Wrap in transaction for data integrity
            DB::transaction(function () {
                $this->syncPermissionsToAdminRoles();
            });

            echo "✅ Successfully synced all permissions to super-admin and admin roles\n";
        } catch (\Exception $e) {
            echo "❌ Error syncing permissions: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Sync all system permissions to super-admin and admin roles
     */
    private function syncPermissionsToAdminRoles(): void
    {
        // Check if company_id column exists in roles table
        if (!Schema::hasColumn('roles', 'company_id')) {
            echo "⚠️ Warning: company_id column does not exist in roles table. Skipping company-specific role sync.\n";
            $this->syncPermissionsToGlobalRoles();
            return;
        }

        // Get all permissions in the system
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            echo "ℹ️ No permissions found in the system\n";
            return;
        }

        echo "📋 Found {$allPermissions->count()} permissions to sync\n";

        // Get all companies to ensure we cover multi-tenant scenarios
        $companies = Company::all();

        if ($companies->isEmpty()) {
            echo "ℹ️ No companies found in the system\n";
            return;
        }

        $totalRolesUpdated = 0;

        foreach ($companies as $company) {
            // Set tenant context for this company
            $this->setTenantContext($company->id);

            // Find super-admin and admin roles for this company
            $adminRoles = Role::where('company_id', $company->id)
                             ->whereIn('name', ['super-admin', 'admin'])
                             ->get();

            foreach ($adminRoles as $role) {
                try {
                    // Sync all permissions to this role
                    $role->syncPermissions($allPermissions);
                    $totalRolesUpdated++;

                    echo "✅ Synced {$allPermissions->count()} permissions to role '{$role->name}' for company '{$company->name}'\n";
                } catch (\Exception $e) {
                    echo "❌ Failed to sync permissions to role '{$role->name}' for company '{$company->name}': " . $e->getMessage() . "\n";
                }
            }

            // If no admin roles exist for this company, create them
            if ($adminRoles->isEmpty()) {
                $this->createAdminRolesForCompany($company, $allPermissions);
                $totalRolesUpdated += 2; // super-admin + admin
            }
        }

        echo "🎯 Total roles updated: {$totalRolesUpdated}\n";
    }

    /**
     * Sync permissions to global roles (when company_id column doesn't exist)
     */
    private function syncPermissionsToGlobalRoles(): void
    {
        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            echo "ℹ️ No permissions found in the system\n";
            return;
        }

        // Find global super-admin and admin roles
        $adminRoles = Role::whereIn('name', ['super-admin', 'admin'])->get();

        foreach ($adminRoles as $role) {
            try {
                $role->syncPermissions($allPermissions);
                echo "✅ Synced {$allPermissions->count()} permissions to global role '{$role->name}'\n";
            } catch (\Exception $e) {
                echo "❌ Failed to sync permissions to global role '{$role->name}': " . $e->getMessage() . "\n";
            }
        }

        // Create global admin roles if they don't exist
        if ($adminRoles->isEmpty()) {
            $this->createGlobalAdminRoles($allPermissions);
        }
    }

    /**
     * Create global admin roles
     */
    private function createGlobalAdminRoles($allPermissions): void
    {
        $rolesToCreate = [
            ['name' => 'super-admin', 'status' => true, 'guard_name' => 'web'],
            ['name' => 'admin', 'status' => true, 'guard_name' => 'web']
        ];

        foreach ($rolesToCreate as $roleData) {
            try {
                $role = Role::firstOrCreate(
                    ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                    $roleData
                );

                $role->syncPermissions($allPermissions);
                echo "✅ Created and synced permissions to global '{$roleData['name']}' role\n";
            } catch (\Exception $e) {
                echo "❌ Failed to create global '{$roleData['name']}' role: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Create super-admin and admin roles for a company if they don't exist
     */
    private function createAdminRolesForCompany(Company $company, $allPermissions): void
    {
        $rolesToCreate = [
            [
                'name' => 'super-admin',
                'company_id' => $company->id,
                'status' => true,
                'guard_name' => 'web'
            ],
            [
                'name' => 'admin',
                'company_id' => $company->id,
                'status' => true,
                'guard_name' => 'web'
            ]
        ];

        foreach ($rolesToCreate as $roleData) {
            try {
                $role = Role::firstOrCreate(
                    ['name' => $roleData['name'], 'company_id' => $company->id],
                    $roleData
                );

                // Sync all permissions to the newly created/found role
                $role->syncPermissions($allPermissions);

                echo "✅ Created and synced permissions to '{$roleData['name']}' role for company '{$company->name}'\n";
            } catch (\Exception $e) {
                echo "❌ Failed to create '{$roleData['name']}' role for company '{$company->name}': " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Set tenant context for multi-tenant operations
     */
    private function setTenantContext(string $companyId): void
    {
        try {
            // Set permissions team ID for Spatie permissions
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($companyId);
            }
        } catch (\Exception $e) {
            echo "⚠️ Could not set tenant context for company {$companyId}: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration doesn't create tables or schema changes,
     * so the down method is intentionally empty.
     */
    public function down(): void
    {
        echo "ℹ️ This migration only syncs permissions and doesn't create schema changes.\n";
        echo "ℹ️ To reverse, you would need to manually remove permissions from roles if desired.\n";
    }
};
