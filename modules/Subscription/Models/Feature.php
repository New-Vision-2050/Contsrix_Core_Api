<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Subscription\Module\Models\Module;
use Modules\Subscription\Enums\FeatureLimitTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feature extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'module_id',
        'package_id',
        'is_enabled',
        'limit',
        'limit_type',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json',
        'is_enabled' => 'boolean',
        'limit' => 'integer',
        'limit_type' => FeatureLimitTypeEnum::class,
    ];

    /**
     * Get the module that owns the Feature
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

     /**
     * Get the package that owns this setting.
     *
     * @return BelongsTo<Package>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
