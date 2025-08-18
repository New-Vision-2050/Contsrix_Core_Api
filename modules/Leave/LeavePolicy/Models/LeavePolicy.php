<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Leave\LeavePolicy\Database\factories\LeavePolicyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

//use BasePackage\Shared\Traits\HasTranslations;

class LeavePolicy extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;
    //use HasTranslations;
    //use SoftDeletes;

    //public array $translatable = [];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'total_days',
        'day_type',
        'is_rollover_allowed',
        'max_days_per_request',
        'upgrade_condition',
        'is_allow_half_day',
        "company_id",
    ];

    protected $casts = [
        'id' => 'string',
        'total_days' => 'integer',
//        'is_rollover_allowed' => 'boolean',
        'max_days_per_request' => 'integer',
//        'is_allow_half_day' => 'boolean',
    ];

    protected static function newFactory(): LeavePolicyFactory
    {
        return LeavePolicyFactory::new();
    }
}
