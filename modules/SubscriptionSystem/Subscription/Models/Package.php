<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use Modules\SubscriptionSystem\Modules\Models\Module;
use Modules\SubscriptionSystem\Subscription\Enums\PackageBillingCycleEnum;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use BasePackage\Shared\Traits\HasTranslations;

class Package extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use HasTranslations;

    public array $translatable = ['name'];
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [

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
