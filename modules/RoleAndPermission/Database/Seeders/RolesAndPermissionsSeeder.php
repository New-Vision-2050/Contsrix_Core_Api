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

        // Assign super-admin role to the first user if not in tenant environment
        if (!tenant()) {
            $user = User::first();
            if ($user) {
                setPermissionsTeamId($companyId);
                $user->assignRole('super-admin');
            }
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
            'user.view',
            'user.list',
            'user.create',
            'user.edit',
            'user.delete',
            'user.export',

            'client.view',
            'client.list',
            'client.create',
            'client.edit',
            'client.delete',
            'client.export',


            'broker.view',
            'broker.list',
            'broker.create',
            'broker.edit',
            'broker.delete',
            'broker.export',


            'employee.view',
            'employee.list',
            'employee.create',
            'employee.edit',
            'employee.delete',
            'employee.export',


            // Company module permissions
            'company.view',
            'company.list',
            'company.create',
            'company.edit',
            'company.delete',
            'company.login-as-admin',
            'company.export',

            // Role and Permission module permissions
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'permission.view',
            'permission.assign',

            "identifier.list",
            "login-way.create",
            "login-way.update",
            "login-way.view",
            "login-way.delete",
            "login-way.activate",

            "driver.view",
            "driver.update",

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
