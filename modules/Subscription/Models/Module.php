<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json',
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
     * Get the packages that include this module.
     *
     * @return BelongsToMany<Package>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }
}
