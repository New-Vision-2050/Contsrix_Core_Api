<?php

declare(strict_types=1);

namespace Modules\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use BasePackage\Shared\Traits\BaseFilterable;
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
        'module_id'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'json',
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
}
