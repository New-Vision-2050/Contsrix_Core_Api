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

        $this->ensureCompanyHasPermissions($companyId);


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
        $permissions = Permission::where('company_id', $companyId)->get();

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

        foreach ($companies as $company) {
            $this->ensureCompanyHasPermissions($company->id);
        }
    }

    /**
     * Ensure a company has all required permissions
     *
     * @param string|null $companyId The company ID
     */
    protected function ensureCompanyHasPermissions(?string $companyId): void
    {
        if (!$companyId) {
            return;
        }

        // Define default permissions by module
        $defaultPermissions = $this->getDefaultPermissions();

        foreach ($defaultPermissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'api',
                    'company_id' => $companyId
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'name' => $permission,
                    'guard_name' => 'api',
                    'company_id' => $companyId,
                    'status' => true
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
        return [
            // User module permissions
            'users.user.view',
            'users.user.list',
            'users.user.create',
            'users.user.edit',
            'users.user.delete',
            'users.user.export',

            'users.client.view',
            'users.client.list',
            'users.client.create',
            'users.client.edit',
            'users.client.delete',
            'users.client.export',


            'users.broker.view',
            'users.broker.list',
            'users.broker.create',
            'users.broker.edit',
            'users.broker.delete',
            'users.broker.export',


            'users.employee.view',
            'users.employee.list',
            'users.employee.create',
            'users.employee.edit',
            'users.employee.delete',
            'users.employee.export',


            // Company module permissions
            'companies.company.view',
            'companies.company.list',
            'companies.company.create',
            'companies.company.edit',
            'companies.company.delete',
            'companies.company.login-as-admin',
            'companies.company.export',

            // Role and Permission module permissions
            'settings.role.view',
            'settings.role.create',
            'settings.role.edit',
            'settings.role.delete',
            'settings.permission.view',
            'settings.permission.assign',

            "settings.identifier.list",
            "settings.login-way.create",
            "settings.login-way.update",
            "settings.login-way.view",
            "settings.login-way.delete",
            "settings.login-way.activate",

            "settings.driver.view",
            "settings.driver.update",

            "organization.branch.view",
            "organization.management.view",
            "organization.users.view",

            "organization.job-title.create",
            "organization.job-title.update",
            "organization.job-title.delete",
            "organization.job-title.list",
            "organization.job-title.activate",
            "organization.job-title.export",


            "organization.job-type.create",
            "organization.job-type.update",
            "organization.job-type.delete",
            "organization.job-type.list",
            "organization.job-type.activate",
            "organization.job-type.export",
            "organization.branch.create",
            "organization.branch.update",
            "organization.branch.delete",
            "organization.management.create",
            "organization.management.update",
            "organization.management.delete",


            "company-profile.official-data.update",
            "company-profile.official-data.request-update",


            "company-profile.legal-data.update",
            "company-profile.legal-data.request-update",


            "company-profile.address.update",
            "company-profile.address.request-update",

            "company-profile.branch.list",
            "company-profile.branch.view",


            "company-profile.official-document.create",
            "company-profile.official-document.update",
            "company-profile.official-document.delete",


            // Add more default permissions for your modules here
        ];
    }
}
