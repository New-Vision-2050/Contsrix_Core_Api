<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Observers;

use Modules\Company\CompanyCore\Models\Company;
use Modules\JobTitle\Models\JobTitle;
use Modules\RoleAndPermission\Services\PermissionCRUDService;
use Ramsey\Uuid\Uuid;
use Modules\Shared\JobType\DTO\CreateJobTypeWithCompanyDTO;
use Modules\Shared\JobType\Services\JobTypeCRUDService;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // Create default job types and titles for the company
//        $this->createDefaultJobTypesAndTitles($company);

        // Copy permissions from the first company to the new company
//        $this->copyPermissionsToNewCompany($company);
    }

    /**
     * Handle the Company "deleting" event.
     * Soft delete all related domains when company is soft deleted.
     */
    public function deleting(Company $company): void
    {
        try {
            // Get all domains for this company
            $domains = $company->domains()->withoutTrashed()->get();
            
            foreach ($domains as $domain) {
                $domain->delete(); // This will soft delete the domain
            }

            \Log::info("Company domains soft deleted", [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'domains_count' => $domains->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to soft delete company domains", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Company "restoring" event.
     * Restore all related domains when company is restored.
     */
    public function restoring(Company $company): void
    {
        try {
            // Get all soft deleted domains for this company
            $domains = $company->domains()->onlyTrashed()->get();
            
            foreach ($domains as $domain) {
                $domain->restore(); // This will restore the domain
            }

            \Log::info("Company domains restored", [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'domains_count' => $domains->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to restore company domains", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create default job types and titles for a new company
     */
    private function createDefaultJobTypesAndTitles(Company $company): void
    {
        $createJobTypeWithCompanyDTO = new CreateJobTypeWithCompanyDTO(
            name: 'مجلس ادارة',
            companyId: Uuid::fromString($company->id),
            status: 1
        );

        $jobType = app(JobTypeCRUDService::class)->createWithCompany($createJobTypeWithCompanyDTO);

        JobTitle::create([
            'type' => 'general_manager',
            'name' => ['ar' => 'مدير عام', 'en' => 'General Manager'],
            'job_type_id' => $jobType->id,
            'description' => 'مدير عام',
            'status' => 1,
            'company_id' => $company->id,
        ]);
    }

    /**
     * Copy permissions from the first company to a new company
     */
    private function copyPermissionsToNewCompany(Company $company): void
    {
        // Skip copying for the first company (it will be our source)
        if (Company::count() <= 1) {
            return;
        }

        try {
            // Get the permissions service and copy permissions to the new company
            $permissionService = app(PermissionCRUDService::class);
            $permissionService->copyPermissionsToCompany(null, $company->id);
        } catch (\Exception $e) {
            \Log::error('Error copying permissions to new company: ' . $e->getMessage(), [
                'company_id' => $company->id,
                'exception' => $e
            ]);
        }
    }
}
