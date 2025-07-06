<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Models;

use Illuminate\Database\Eloquent\Model;
use BasePackage\Shared\Traits\UuidTrait;
use Modules\RoleAndPermission\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageFeature extends Model
{
    use UuidTrait;
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'is_enabled',
        'limit',
        'permission_id',
        'package_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limit' => 'integer',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
