<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Leave\LeaveType\Database\factories\LeaveTypeFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

//use BasePackage\Shared\Traits\HasTranslations;

class LeaveType extends Model
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
        'is_payed',
        'is_deduct_from_balance',
        "company_id",
    ];

    protected $casts = [
        'id' => 'string',
//        'is_payed' => 'boolean',
//        'is_deduct_from_balance' => 'boolean',
    ];

    protected static function newFactory(): LeaveTypeFactory
    {
        return LeaveTypeFactory::new();
    }
}
