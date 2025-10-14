<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Subscription\Package\Models\CompanyPermissionLimit;

class ArchiveLibraryStorageLimitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Sets 1000 MB storage limit for archive library file creation for all companies
     */
    public function run(): void
    {
        try {
            // Get current company from tenant context
            $company = Company::first();

            if (!$company) {
                Log::warning('No company found in tenant database. Skipping storage limit seeder.');
                $this->command->warn('⚠ No company found in tenant database.');
                return;
            }

            // Find the archive library file create permission (from central database)
            $permission = Permission::on('mysql')->where('name', 'archive-library.archive-library*file.create')->first();

            if (!$permission) {
                Log::warning('Archive library file.create permission not found. Skipping storage limit seeder.');
                $this->command->warn('⚠ Archive library file.create permission not found.');
                return;
            }

            // Check if limit already exists for this company and permission
            $existingLimit = CompanyPermissionLimit::on('mysql')
                ->where('company_id', $company->id)
                ->where('permission_id', $permission->id)
                ->first();

            if ($existingLimit) {
                $this->command->info("ℹ Storage limit already exists for company {$company->name}. Current limit: {$existingLimit->limit} MB");
                return;
            }

            // Create the storage limit (1000 MB) in central database
            CompanyPermissionLimit::on('mysql')->create([
                'company_id' => $company->id,
                'permission_id' => $permission->id,
                'limit' => 1000, // 1000 MB total storage
                'actual_limit' => 1000, // 1000 MB available initially
            ]);

            Log::info("Successfully set 1000 MB storage limit for archive library files for company: {$company->id}");
            $this->command->info("✓ Archive library storage limit (1000 MB) set for company: {$company->name}");

        } catch (\Exception $e) {
            Log::error('Failed to set archive library storage limit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->command->error("✗ Failed to set archive library storage limit: {$e->getMessage()}");
        }
    }
}
