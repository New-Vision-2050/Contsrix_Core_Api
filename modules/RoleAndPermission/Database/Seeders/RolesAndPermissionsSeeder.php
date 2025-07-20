<?php

namespace Modules\RoleAndPermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Database\Seeders\UserPermissionsTableSeeder;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ranium\SeedOnce\Traits\SeedOnce;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Get current company ID or use the first company
        $companyId = tenant("id") ?? Company::query()->first()?->id;

        $this->ensureCompanyHasPermissions();


        // Create roles for the current company
        $this->createCompanyRoles($companyId);
    }

    /**
     * Create standard roles for a company and assign permissions
     *
     * @param string|null $companyId The company ID
     */
    protected function createCompanyRoles(?string $companyId): void
    {
        if (!$companyId) {
            return;
        }

        // Create super-admin role
        $superAdminRole = Role::firstOrCreate(
            ["name" => "super-admin", "company_id" => $companyId],
            [
                "name" => "super-admin",
                "company_id" => $companyId,
                "status" => true
            ]
        );

        // Create admin role
        $adminRole = Role::firstOrCreate(
            ["name" => "admin", "company_id" => $companyId],
            [
                "name" => "admin",
                "company_id" => $companyId,
                "status" => true
            ]
        );

        // Get all permissions for this company
        $permissions = Permission::get();

        // Assign permissions to roles
        $superAdminRole->syncPermissions($permissions);
        $adminRole->syncPermissions($permissions);

        // Assign super-admin role to the first userZ

        $user = User::first();
        if ($user) {
            setPermissionsTeamId($companyId);
            $user->assignRole('super-admin');
        }

    }

    /**
     * Seed default permissions to all companies
     */
    protected function seedDefaultPermissionsToAllCompanies(): void
    {
        $companies = Company::all();


        $this->ensureCompanyHasPermissions();
    }

    /**
     * Ensure a company has all required permissions
     *
     * @param string|null $companyId The company ID
     */
    protected function ensureCompanyHasPermissions(): void
    {


        $permissions = config('permissions.permissions');

        $guardName = 'api';

        foreach ($permissions as $key => $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'name' => $name,
                    'key' => $key,
                    'guard_name' => $guardName,
                ]
            );
        }
    }

    /**
     * Get the default permissions for the system
     *
     * @return array
     */
    private function getDefaultPermissions(): array
    {
        return \Modules\RoleAndPermission\Enums\Permission::all();
    }
}
