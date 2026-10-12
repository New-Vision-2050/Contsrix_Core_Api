<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Leave\LeavePolicy\Models\LeavePolicy;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserLeavePolicy extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'user_leave_policies';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'leave_policy_id',
        'company_id',
        'is_default',
        'assigned_by',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'leave_policy_id' => 'string',
        'company_id' => 'string',
        'assigned_by' => 'string',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class, 'leave_policy_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
