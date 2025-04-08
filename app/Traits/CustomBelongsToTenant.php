<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\CustomTenantScope;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * @property-read Tenant $tenant
 */
trait CustomBelongsToTenant
{
    public static function bootCustomBelongsToTenant()
    {
        static::addGlobalScope(new CustomTenantScope);

        static::creating(function ($model) {
            if (! $model->getAttribute(\Stancl\Tenancy\Database\Concerns\BelongsToTenant::$tenantIdColumn) && ! $model->relationLoaded('tenant')) {
                if (tenancy()->initialized) {
                    $model->setAttribute(\Stancl\Tenancy\Database\Concerns\BelongsToTenant::$tenantIdColumn, tenant()->getTenantKey());
                    $model->setRelation('tenant', tenant());
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'), \Stancl\Tenancy\Database\Concerns\BelongsToTenant::$tenantIdColumn);
    }
}
