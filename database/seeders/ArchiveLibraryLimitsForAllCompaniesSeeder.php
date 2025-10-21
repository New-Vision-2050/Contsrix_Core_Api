<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\Subscription\Package\Models\CompanyPermissionLimit;

class ArchiveLibraryLimitsForAllCompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Sets archive library limits for ALL existing companies
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Find the archive library permissions
            $filePermission = Permission::where('name', 'archive-library.archive-library*file.create')->first();
            $folderPermission = Permission::where('name', 'archive-library.archive-library*folder.create')->first();

            if (!$filePermission) {
                $this->command->warn('⚠ Archive library file.create permission not found.');
            }

            if (!$folderPermission) {
                $this->command->warn('⚠ Archive library folder.create permission not found.');
            }

            if (!$filePermission && !$folderPermission) {
                $this->command->error('✗ No archive library permissions found. Exiting.');
                DB::rollBack();
                return;
            }

            // Get all companies
            $companies = Company::all();
            
            if ($companies->isEmpty()) {
                $this->command->warn('⚠ No companies found in the database.');
                DB::rollBack();
                return;
            }

            $this->command->info("Processing {$companies->count()} companies...");
            $fileCount = 0;
            $folderCount = 0;
            $skippedCount = 0;

            foreach ($companies as $company) {
                // Set file storage limit (1000 MB)
                if ($filePermission) {
                    $existingFileLimit = CompanyPermissionLimit::where('company_id', $company->id)
                        ->where('permission_id', $filePermission->id)
                        ->first();

                    if (!$existingFileLimit) {
                        CompanyPermissionLimit::create([
                            'company_id' => $company->id,
                            'permission_id' => $filePermission->id,
                            'limit' => 1000, // 1000 MB
                            'actual_limit' => 1000,
                        ]);
                        $fileCount++;
                    }
                }

                // Set folder limit (1000 folders)
                if ($folderPermission) {
                    $existingFolderLimit = CompanyPermissionLimit::where('company_id', $company->id)
                        ->where('permission_id', $folderPermission->id)
                        ->first();

                    if (!$existingFolderLimit) {
                        CompanyPermissionLimit::create([
                            'company_id' => $company->id,
                            'permission_id' => $folderPermission->id,
                            'limit' => 1000, // 1000 folders
                            'actual_limit' => 1000,
                        ]);
                        $folderCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            }

            DB::commit();

            $this->command->info("✓ Successfully set archive library limits:");
            $this->command->info("  - File storage limits (1000 MB): {$fileCount} companies");
            $this->command->info("  - Folder limits (1000 folders): {$folderCount} companies");
            if ($skippedCount > 0) {
                $this->command->info("  - Skipped (already exists): {$skippedCount} records");
            }

            Log::info('Archive library limits set for all companies', [
                'file_limits_created' => $fileCount,
                'folder_limits_created' => $folderCount,
                'skipped' => $skippedCount,
                'total_companies' => $companies->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to set archive library limits for all companies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->command->error("✗ Failed: {$e->getMessage()}");
        }
    }
}
