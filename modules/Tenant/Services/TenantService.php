<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Models\Tenant;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;

class TenantService
{
    /**
     * Create a new tenant for a company
     *
     * @param Company $company
     * @return Tenant
     */
    public function createTenant(Company $company): Tenant
    {
        return DB::transaction(function () use ($company) {
            // Create a tenant with the company's ID as the tenant ID
            $tenant = Tenant::create([
                'id' => $company->id,
                'company_id' => $company->id,
                'name' => $company->name,
            ]);

            // Create a domain for the tenant (using company's user_name as subdomain)
            $domain = $company->user_name . '.' . config('app.domain', 'localhost');
            $tenant->domains()->create(['domain' => $domain]);

            return $tenant;
        });
    }

    /**
     * Get a tenant by company ID
     *
     * @param string $companyId
     * @return Tenant|null
     */
    public function getTenantByCompanyId(string $companyId): ?Tenant
    {
        return Tenant::where('company_id', $companyId)->first();
    }

    /**
     * Get the current tenant
     *
     * @return Tenant|null
     * @throws TenantCouldNotBeIdentifiedException
     */
    public function getCurrentTenant(): ?Tenant
    {
        if (tenant()) {
            return tenant();
        }

        throw new TenantCouldNotBeIdentifiedException('No tenant identified');
    }

    /**
     * Get all tenants
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllTenants()
    {
        return Tenant::with('company')->get();
    }

    /**
     * Delete a tenant
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            // Delete the tenant's domains
            $tenant->domains()->delete();
            
            // Delete the tenant
            return $tenant->delete();
        });
    }
}