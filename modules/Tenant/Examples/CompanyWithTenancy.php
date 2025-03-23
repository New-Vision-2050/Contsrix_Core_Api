<?php

declare(strict_types=1);

namespace Modules\Tenant\Examples;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Company\CompanyCore\Models\Company as BaseCompany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * Example of how to extend the Company model to support tenancy.
 * This is an alternative approach to creating a separate Tenant model.
 */
class CompanyWithTenancy extends BaseCompany implements TenantWithDatabase
{
    use HasFactory;
    use HasDatabase;
    use HasDomains;

    /**
     * Get the domain identifier for this company.
     * This is used to create the subdomain for the tenant.
     *
     * @return string
     */
    public function getDomainIdentifier(): string
    {
        return $this->user_name;
    }

    /**
     * Create a domain for this company.
     *
     * @param string $domain
     * @return \Stancl\Tenancy\Database\Models\Domain
     */
    public function createDomain(array $data)
    {
        return $this->domains()->create($data);
    }

    /**
     * Get the database name for this tenant.
     * By default, this is tenant_{id}.
     *
     * @return string
     */
    public function getTenantDatabaseName()
    {
        return 'tenant_' . $this->id;
    }

    /**
     * Get the key name for tenant identification.
     *
     * @return string
     */
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the value of the tenant's primary key.
     *
     * @return string
     */
    public function getTenantKey()
    {
        return $this->id;
    }

    /**
     * The connection name to use for tenant database connections.
     *
     * @return string|null
     */
    public function getTenantConnectionName(): ?string
    {
        return 'tenant';
    }

    /**
     * Create a new tenant with the given attributes.
     *
     * @param array $attributes
     * @return static
     */
    public static function createTenant(array $attributes): self
    {
        $company = static::create($attributes);
        
        // Create a domain for the tenant (using company's user_name as subdomain)
        $domain = $company->user_name . '.' . config('tenancy.central_domains.0', 'localhost');
        $company->createDomain(['domain' => $domain]);
        
        return $company;
    }
}