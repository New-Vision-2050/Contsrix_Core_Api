<?php

namespace Modules\Tenant\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Tenant\Facades\Tenant;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootBelongsToTenant()
    {
        // Apply tenant scope to all queries
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Only apply the tenant scope if a tenant is set
            if (Tenant::getTenant()) {
                // No need to add any conditions as the schema is already set to the tenant's schema
                // This is just to ensure the trait is properly applied
            }
        });

        // Set the tenant ID on model creation
        static::creating(function ($model) {
            // No need to set a tenant_id as we're using schemas for separation
            // This hook is here in case you want to add additional tenant-specific logic
        });
    }

    /**
     * Get the current tenant.
     *
     * @return \Modules\Company\CompanyCore\Models\Company|null
     */
    public function getTenant()
    {
        return Tenant::getTenant();
    }

    /**
     * Scope a query to only include records for the current tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTenant(Builder $query)
    {
        // No need to add any conditions as the schema is already set to the tenant's schema
        return $query;
    }
}