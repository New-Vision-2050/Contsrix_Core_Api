<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\RoleAndPermission\Models\Permission;

class FeaturePermission extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'feature_permission';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'feature_id',
        'permission_id',
    ];

    /**
     * Get the feature that owns the pivot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Get the permission that owns the pivot.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
