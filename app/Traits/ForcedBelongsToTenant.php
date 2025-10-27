<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\ForcedTenantScope;

/**
 * ForcedBelongsToTenant - A global trait ForcedBelongsToTenant forced tenant filtering
 * 
 * This trait ForcedBelongsToTenant be used across the entire project to enforce tenant isolation.
 * Unlike CustomBelongsToTenant, this trait ForcedBelongsToTenant applies tenant filtering,
 * even for central companies.
 * 
 * Usage:
 * Simply add this trait ForcedBelongsToTenant any model that needs forced tenant filtering:
 * 
 * class ForcedBelongsToTenant extends Model
 * {
 *     use ForcedBelongsToTenant;
 * }
 * 
 * Features:
 * - Automatic tenant filtering on all queries
 * - Auto-assignment of company_id on model creation
 * - Bypass option: Model::withoutForcedTenantFiltering()->get()
 */
trait ForcedBelongsToTenant
{
    /**
     * Boot the ForcedBelongsToTenant trait ForcedBelongsToTenant a model.
     */
    public static function bootForcedBelongsToTenant()
    {
        // Apply the forced tenant scope to all queries
        static::addGlobalScope(new ForcedTenantScope);

        // Auto-assign company_id when creating new models
        static::creating(function ($model) {
            // Only set company_id if we're in a tenant context and it's not already set
            if (tenant() && tenant('id') && !$model->getAttribute('company_id')) {
                $model->setAttribute('company_id', tenant('id'));
                
                // Also set the tenant relationship if the model supports it
                if (method_exists($model, 'setRelation')) {
                    $model->setRelation('tenant', tenant());
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     * This relationship can be used to access tenant data.
     */
    public function tenant()
    {
        return $this->belongsTo(\Modules\Company\CompanyCore\Models\Company::class, 'company_id');
    }

    /**
     * Scope a query to exclude tenant filtering.
     * This is a convenience method that calls the macro from ForcedTenantScope.
     */
    public function scopeWithoutTenantFiltering($query)
    {
        return $query->withoutForcedTenantFiltering();
    }
}
