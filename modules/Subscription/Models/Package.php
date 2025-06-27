<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Subscription\Enums\PackageBillingCycleEnum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'price',
        'billing_cycle',
        'is_active'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json',
        'billing_cycle' => PackageBillingCycleEnum::class,
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Get all of the features for the Module
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    /**
     * Get the modules associated with the package.
     *
     * @return BelongsToMany<Module>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Company\CompanyCore\Models\Company::class,
            'company_package',
            'package_id',
            'company_id'
        )
            ->using(CompanyPackagePivot::class)
            ->withPivot(['subscribed_at', 'expires_at', 'is_active'])
            ->withTimestamps();
    }
}
