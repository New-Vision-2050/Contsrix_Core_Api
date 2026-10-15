<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Models;

use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Advance\AdvancePolicy\Models\AdvancePolicy;
use Modules\Company\CompanyCore\Models\Company;
use Modules\User\Models\User;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserAdvancePolicy extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use BelongsToTenant;

    protected $table = 'user_advance_policies';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'advance_policy_id',
        'company_id',
        'is_default',
        'assigned_by',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'advance_policy_id' => 'string',
        'company_id' => 'string',
        'assigned_by' => 'string',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function advancePolicy(): BelongsTo
    {
        return $this->belongsTo(AdvancePolicy::class, 'advance_policy_id');
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
