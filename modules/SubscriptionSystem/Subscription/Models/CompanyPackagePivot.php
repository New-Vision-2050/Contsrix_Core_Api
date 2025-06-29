<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Subscription\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
class CompanyPackagePivot extends Model
{
    use UuidTrait;
    protected $table = 'company_package';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'subscribed_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'company_id',
        'package_id',
        'subscribed_at',
        'expires_at',
        'is_active',
    ];
}
