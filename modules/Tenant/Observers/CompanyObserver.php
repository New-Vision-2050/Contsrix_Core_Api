<?php

declare(strict_types=1);

namespace Modules\Tenant\Observers;

use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Services\TenantService;

class CompanyObserver
{
    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * CompanyObserver constructor.
     *
     * @param TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Handle the Company "created" event.
     *
     * @param Company $company
     * @return void
     */
    public function created(Company $company): void
    {
        // Only create a tenant if the company is active
        if ($company->is_active) {
            // Create a tenant with the company's ID and store company_id in the data column
            $this->tenantService->createTenant($company);
        }
    }

    /**
     * Handle the Company "updated" event.
     *
     * @param Company $company
     * @return void
     */
    public function updated(Company $company): void
    {
        // If company is activated, create a tenant if it doesn't exist
        if ($company->is_active && $company->getOriginal('is_active') === false) {
            $tenant = $this->tenantService->getTenantByCompanyId($company->id);
            
            if (!$tenant) {
                $this->tenantService->createTenant($company);
            }
        }
    }

    /**
     * Handle the Company "deleted" event.
     *
     * @param Company $company
     * @return void
     */
    public function deleted(Company $company): void
    {
        // Find and delete the associated tenant
        $tenant = $this->tenantService->getTenantByCompanyId($company->id);
        
        if ($tenant) {
            $this->tenantService->deleteTenant($tenant);
        }
    }
}