<?php

namespace Modules\Tenant\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Company\CompanyCore\Models\Company;

/**
 * @method static \Modules\Company\CompanyCore\Models\Company|null getTenant()
 * @method static void setTenant(\Modules\Company\CompanyCore\Models\Company $tenant)
 * @method static void setTenantById(string $tenantId)
 * @method static void resetTenant()
 * @method static string getTenantSchemaName()
 * @method static bool schemaExists(string $schemaName)
 * @method static bool createTenantSchema(\Modules\Company\CompanyCore\Models\Company $tenant)
 * @method static void migrateTenant(\Modules\Company\CompanyCore\Models\Company $tenant)
 * 
 * @see \Modules\Tenant\Services\TenantManager
 */
class Tenant extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tenant.manager';
    }
}