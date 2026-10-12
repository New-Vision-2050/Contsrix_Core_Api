<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Leave\LeavePolicy\Database\factories\LeavePolicyFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Modules\Leave\LeaveType\Models\LeaveType;
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
        'leave_type_id',
        'total_days',
        'max_balance_days',
        'min_notice_days',
        'day_calculation_type',
        'entitlement_type',
        'entitlement_start_from',
        'promotion_type',
        'promotion_duration_months',
        'is_rollover_allowed',
        'rollover_duration_months',
        'allow_exceed_balance',
        'max_days',
        "company_id",
    ];

    protected $casts = [
        'id' => 'string',
        'leave_type_id' => 'string',
        'total_days' => 'integer',
        'max_balance_days' => 'integer',
        'min_notice_days' => 'integer',
        'promotion_duration_months' => 'integer',
        'is_rollover_allowed' => 'boolean',
        'rollover_duration_months' => 'integer',
        'allow_exceed_balance' => 'boolean',
        'max_days' => 'integer',
    ];

    protected static function newFactory(): LeavePolicyFactory
    {
        return LeavePolicyFactory::new();
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
