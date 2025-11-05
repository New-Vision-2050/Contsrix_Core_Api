<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * ForcedTenantScope - A global tenant scope that can be used across the entire project
 * 
 * This scope ALWAYS filters by tenant, even for central companies.
 * Unlike CustomTenantScope, this scope ignores the is_central_company flag
 * and enforces tenant isolation for all models that use it.
 * 
 * Usage:
 * - Add to any model: static::addGlobalScope(new ForcedTenantScope);
 * - Bypass filtering: Model::withoutForcedTenantFiltering()->get()
 */
class ForcedTenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * This scope ALWAYS filters by tenant, regardless of central company status.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply tenant filtering if we're in a tenant context
        if (tenant() && tenant('id')) {
            // Use the model's table name to avoid conflicts in joins
            $builder->where($model->getTable() . '.company_id', tenant('id'));
        }
    }

    /**
     * Extend the query builder with macros.
     * Provides a way to bypass the forced tenant filtering when needed.
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withoutForcedTenantFiltering', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
