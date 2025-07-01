<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\SubscriptionSystem\Subscription\Enums\FeatureLimitTypeEnum;

class PackageFeatureSetting extends Model
{
    use UuidTrait;
    use BaseFilterable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'package_id',
        'feature_id',
        'is_enabled',
        'limit',
        'limit_type',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limit' => 'integer',
        'limit_type' => FeatureLimitTypeEnum::class,

    ];


    /**
     * Get the package that owns this setting.
     *
     * @return BelongsTo<Package>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the feature that this setting configures.
     *
     * @return BelongsTo<Feature>
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
