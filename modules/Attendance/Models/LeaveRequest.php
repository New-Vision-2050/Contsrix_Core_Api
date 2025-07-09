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
use Carbon\Carbon;

class LeaveRequest extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'leave_requests';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'is_emergency',
        'attachments',
        'notes',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'leave_type_id' => UuidCast::class,
        'requested_by' => UuidCast::class,
        'approved_by' => UuidCast::class,
        'rejected_by' => UuidCast::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'total_days' => 'integer',
        'is_emergency' => 'boolean',
        'attachments' => 'array',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'approved_at',
        'rejected_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user that owns the leave request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the company that owns the leave request.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the leave type for this request.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    /**
     * Get the user who requested this leave.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved this leave request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this leave request.
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if the leave request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the leave request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the leave request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the leave request overlaps with given dates.
     */
    public function overlaps($startDate, $endDate): bool
    {
        return $this->start_date <= $endDate && $this->end_date >= $startDate;
    }

    /**
     * Calculate total leave days.
     */
    public function calculateTotalDays(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        return $startDate->diffInDays($endDate) + 1;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): string
    {
        $days = $this->total_days ?? $this->calculateTotalDays();
        
        if ($days === 1) {
            return '1 day';
        }
        
        return "{$days} days";
    }

    /**
     * Check if leave is currently active.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $today = Carbon::today();
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        return $today->between($startDate, $endDate);
    }
}
