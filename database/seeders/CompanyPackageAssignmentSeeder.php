<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Role;
use Modules\Subscription\Package\Models\Package;
use Modules\User\Models\User;

class CompanyPackageAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Model::unguard();

        DB::transaction(function () {
            // Get current company ID or use the first company
            $companyId = tenant("id") ?? Company::query()->first()?->id;

            if (!$companyId) {
                Log::warning('CompanyPackageAssignmentSeeder: No company found to assign package to');
                return;
            }

            // Assign Main Package to company
            $this->assignMainPackageToCompany($companyId);

            // Create roles with permissions from Main Package
            $this->createCompanyRolesWithPackagePermissions($companyId);
        });

        Model::reguard();
    }

    /**
     * Assign Main Package to the company
     *
     * @param string $companyId
     */
    protected function assignMainPackageToCompany(string $companyId): void
    {
        // Find Main Package by name
        $mainPackage = Package::where('name', 'Main Package')->first();

        if (!$mainPackage) {
            Log::error('CompanyPackageAssignmentSeeder: Main Package not found. Please run MainPackageSeeder first.');
            throw new \Exception('Main Package not found. Please run MainPackageSeeder first.');
        }

        // Find the company
        $company = Company::find($companyId);
        if (!$company) {
            Log::error("CompanyPackageAssignmentSeeder: Company with ID {$companyId} not found");
            return;
        }

        // Check if company already has the Main Package assigned
        $existingAssignment = $company->packages()->where('package_id', $mainPackage->id)->exists();

        if (!$existingAssignment) {
            // Assign Main Package to company
            $company->packages()->attach($mainPackage->id, [
                'subscribed_at' => now(),
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("CompanyPackageAssignmentSeeder: Assigned Main Package to company {$companyId}");
        } else {
            Log::info("CompanyPackageAssignmentSeeder: Company {$companyId} already has Main Package assigned");
        }
    }

    /**
     * Create company roles and assign permissions from Main Package
     *
     * @param string $companyId
     */
    protected function createCompanyRolesWithPackagePermissions(string $companyId): void
    {
        // Find Main Package
        $mainPackage = Package::where('name', 'Main Package')->first();

        if (!$mainPackage) {
            Log::error('CompanyPackageAssignmentSeeder: Main Package not found for permissions assignment');
            return;
        }

        // Get all permissions from Main Package
        $packagePermissions = $mainPackage->permissions;

        if ($packagePermissions->isEmpty()) {
            Log::warning('CompanyPackageAssignmentSeeder: Main Package has no permissions assigned');
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

        // Assign Main Package permissions to both roles
        $superAdminRole->syncPermissions($packagePermissions);
        $adminRole->syncPermissions($packagePermissions);

        Log::info("CompanyPackageAssignmentSeeder: Created roles and assigned {$packagePermissions->count()} permissions from Main Package to company {$companyId}");

        // Assign super-admin role to the first user of this company
        $this->assignSuperAdminRoleToFirstUser($companyId);
    }

    /**
     * Assign super-admin role to the first user of the company
     *
     * @param string $companyId
     */
    protected function assignSuperAdminRoleToFirstUser(string $companyId): void
    {
        // Find first user for this company
        $user = User::where('company_id', $companyId)->first() ?? User::first();

        if ($user) {
            // Set permissions team ID for company context
            setPermissionsTeamId($companyId);
            
            // Assign super-admin role if not already assigned
            if (!$user->hasRole('super-admin')) {
                $user->assignRole('super-admin');
                Log::info("CompanyPackageAssignmentSeeder: Assigned super-admin role to user {$user->id}");
            }
        } else {
            Log::warning("CompanyPackageAssignmentSeeder: No user found to assign super-admin role for company {$companyId}");
        }
    }
}
