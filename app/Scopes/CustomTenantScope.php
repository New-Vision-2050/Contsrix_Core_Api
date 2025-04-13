<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Company\CompanyCore\Models\Company;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CustomTenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // If tenancy is not initialized, don't apply the scope
        if (! tenancy()->initialized) {
            return;
        }

        // Get the current tenant
        $tenant = tenant();

        // Check if the current tenant's name is "New Vision"
        if ($tenant->is_central_company) {
            // Don't apply any filtering - this tenant can see all data
            return;
        }
        if($model instanceof Company) //if model is Company, apply the tenant filtering on id because no have company_id column
        {
            $builder->where("id", $tenant->getTenantKey());
            return;
        }

        // For all other tenants, apply the normal tenant filtering
        $builder->where($model->qualifyColumn(BelongsToTenant::$tenantIdColumn), $tenant->getTenantKey());
    }
}
