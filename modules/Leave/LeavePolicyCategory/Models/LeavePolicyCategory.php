<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Modules\Leave\LeavePolicyCategory\Database\factories\LeavePolicyCategoryFactory;
use BasePackage\Shared\Traits\BaseFilterable;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class LeavePolicyCategory extends Model
{
    use HasFactory;
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'leave_policy_categories';

    protected $fillable = [
        'name',
        'company_id',
    ];

    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory(): LeavePolicyCategoryFactory
    {
        return LeavePolicyCategoryFactory::new();
    }

    public function leavePolicies(): HasMany
    {
        return $this->hasMany(LeavePolicy::class, 'leave_category_id');
    }
}
