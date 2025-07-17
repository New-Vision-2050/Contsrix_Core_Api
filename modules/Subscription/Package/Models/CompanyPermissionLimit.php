<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Models;

use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;

class CompanyPermissionLimit extends Model
{
    use UuidTrait;

    protected $table = 'company_permissions_limits';

    protected $fillable = [
        'company_id',
        'permission_id',
        'limit',
        'actual_limit',
    ];

    protected $casts = [
        'limit' => 'integer',
        'actual_limit' => 'integer',
    ];

    /**
     * Get the company that owns the permission limit.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the permission that has the limit.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    /**
     * Decrease the actual limit by the specified amount.
     */
    public function decreaseLimit(int $amount = 1): bool
    {
        if ($this->actual_limit >= $amount) {
            $this->actual_limit -= $amount;
            return $this->save();
        }
        return false;
    }

    /**
     * Increase the actual limit by the specified amount.
     */
    public function increaseLimit(int $amount = 1): bool
    {
        // Don't exceed the maximum limit
        $newLimit = min($this->actual_limit + $amount, $this->limit);
        $this->actual_limit = $newLimit;
        return $this->save();
    }

    /**
     * Check if the limit has been exceeded.
     */
    public function isLimitExceeded(): bool
    {
        return $this->actual_limit <= 0;
    }

    /**
     * Reset actual limit to maximum limit.
     */
    public function resetLimit(): bool
    {
        $this->actual_limit = $this->limit;
        return $this->save();
    }
}
