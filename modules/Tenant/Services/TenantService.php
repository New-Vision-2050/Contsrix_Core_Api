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

            // Create a tenant with the company's ID as the tenant ID
            $tenant = Tenant::firstOrCreate(
                ['id' => $company->id],
                [
                    'id' => $company->id,
                    'company_id' => $company->id, // This will be stored in the data column
                    'name' => $company->name,     // This will be stored in the data column
                ]
            );

            // Create a domain for the tenant (using company's user_name as subdomain)
            $domain = $company->user_name . '.' . config('tenancy.central_domains.0', 'localhost');
            $tenant->createDomain(['domain' => $domain]);

            try {
                // For schema-based tenancy in PostgreSQL, ensure the schema exists
                if (!$tenant->database()->manager()->databaseExists($tenant->database()->getName())) {
                    \Log::info('Creating schema for tenant: ' . $tenant->id);
                    $tenant->database()->manager()->createDatabase($tenant->database()->getName());

                    // Run migrations for the tenant
                    \Artisan::call('tenants:migrate', [
                        '--tenants' => [$tenant->id]
                    ]);
                    \Log::info('Ran migrations for tenant: ' . $tenant->id);
                } else {
                    \Log::info('Schema already exists for tenant: ' . $tenant->id);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create tenant schema: ' . $e->getMessage());
                throw $e;
            }

            return $tenant;

    }

    /**
     * Get a tenant by company ID
     *
     * @param string $companyId
     * @return Tenant|null
     */
    public function getTenantByCompanyId(string $companyId): ?Tenant
    {
        // Since company_id is stored in the data column, we need to use whereData
        return Tenant::where('data->company_id', $companyId)->first();
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
            foreach ($tenant->domains as $domain) {
                $domain->delete();
            }

            // Delete the tenant
            return $tenant->delete();
        });
    }
}
