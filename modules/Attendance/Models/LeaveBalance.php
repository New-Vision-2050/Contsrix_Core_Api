<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Casts\UuidCast;
use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Models\Company;
use OwenIt\Auditing\Contracts\Auditable;

class LeaveBalance extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'leave_balances';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'pending_days',
        'remaining_days',
        'carried_over_days',
        'accrued_days',
        'last_accrual_date',
        'notes',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'leave_type_id' => UuidCast::class,
        'year' => 'integer',
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'carried_over_days' => 'decimal:2',
        'accrued_days' => 'decimal:2',
        'last_accrual_date' => 'date',
    ];

    protected $dates = [
        'last_accrual_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the user that owns the leave balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the company that owns the leave balance.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the leave type for this balance.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to filter by leave type.
     */
    public function scopeForLeaveType($query, $leaveTypeId)
    {
        return $query->where('leave_type_id', $leaveTypeId);
    }

    /**
     * Calculate remaining days.
     */
    public function calculateRemainingDays(): float
    {
        return $this->allocated_days + $this->carried_over_days + $this->accrued_days - $this->used_days - $this->pending_days;
    }

    /**
     * Update remaining days.
     */
    public function updateRemainingDays(): void
    {
        $this->remaining_days = $this->calculateRemainingDays();
        $this->save();
    }

    /**
     * Check if user has sufficient balance for requested days.
     */
    public function hasSufficientBalance(float $requestedDays): bool
    {
        return $this->calculateRemainingDays() >= $requestedDays;
    }

    /**
     * Deduct days from balance.
     */
    public function deductDays(float $days): void
    {
        $this->used_days += $days;
        $this->updateRemainingDays();
    }

    /**
     * Add pending days (for pending leave requests).
     */
    public function addPendingDays(float $days): void
    {
        $this->pending_days += $days;
        $this->updateRemainingDays();
    }

    /**
     * Remove pending days (when leave request is approved/rejected).
     */
    public function removePendingDays(float $days): void
    {
        $this->pending_days = max(0, $this->pending_days - $days);
        $this->updateRemainingDays();
    }

    /**
     * Add accrued days.
     */
    public function addAccruedDays(float $days): void
    {
        $this->accrued_days += $days;
        $this->last_accrual_date = now()->toDateString();
        $this->updateRemainingDays();
    }

    /**
     * Reset balance for new year.
     */
    public function resetForNewYear(int $newYear, float $carryOverDays = 0): void
    {
        $this->year = $newYear;
        $this->carried_over_days = $carryOverDays;
        $this->used_days = 0;
        $this->pending_days = 0;
        $this->accrued_days = 0;
        $this->last_accrual_date = null;
        $this->updateRemainingDays();
    }

    /**
     * Get balance summary.
     */
    public function getSummary(): array
    {
        return [
            'allocated_days' => $this->allocated_days,
            'carried_over_days' => $this->carried_over_days,
            'accrued_days' => $this->accrued_days,
            'used_days' => $this->used_days,
            'pending_days' => $this->pending_days,
            'remaining_days' => $this->calculateRemainingDays(),
            'total_available' => $this->allocated_days + $this->carried_over_days + $this->accrued_days,
        ];
    }
}
