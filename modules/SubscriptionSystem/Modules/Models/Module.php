<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use BasePackage\Shared\Traits\HasTranslations;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubscriptionSystem\Subscription\Models\Package;
class Module extends Model
{
    use UuidTrait;
    use BaseFilterable;
    // use SoftDeletes;
    use HasTranslations;

    public $incrementing = false;

    protected $keyType = 'string';
    public array $translatable = ['name'];

    protected $fillable = [
        // 'name',
        'slug'
    ];

    protected $casts = [
        'id' => 'string',
        // 'name' => 'json',
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

    // /**
    //  * Get the packages that include this module.
    //  *
    //  * @return BelongsToMany<Package>
    //  */
    // public function packages(): BelongsToMany
    // {
    //     return $this->belongsToMany(Package::class);
    // }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
