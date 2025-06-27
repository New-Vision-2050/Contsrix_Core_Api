<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use Modules\Subscription\Models\Package;
use BasePackage\Shared\Traits\BaseFilterable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'slug',
        'module_id',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json',
    ];

    /**
     * Get the packages that include this module.
     *
     * @return BelongsToMany<Package>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class);
    }

    /**
     * Get the parent module.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'module_id');
    }

    /**
     * Get the sub-modules (children).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'module_id');
    }
}
