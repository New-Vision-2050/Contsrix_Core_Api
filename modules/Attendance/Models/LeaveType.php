<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Casts\UuidCast;
use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\HasTranslations;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Company\CompanyCore\Models\Company;
use OwenIt\Auditing\Contracts\Auditable;

class LeaveType extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use HasTranslations;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'leave_types';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'max_days_per_year',
        'max_consecutive_days',
        'min_notice_days',
        'is_paid',
        'is_active',
        'requires_approval',
        'requires_attachment',
        'color_code',
        'sort_order',
        'accrual_rate',
        'carry_over_limit',
        'blackout_periods',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'max_days_per_year' => 'integer',
        'max_consecutive_days' => 'integer',
        'min_notice_days' => 'integer',
        'sort_order' => 'integer',
        'carry_over_limit' => 'integer',
        'accrual_rate' => 'decimal:2',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'requires_attachment' => 'boolean',
        'blackout_periods' => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the company that owns the leave type.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get all leave requests for this leave type.
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }

    /**
     * Get all leave balances for this leave type.
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type_id');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get active leave types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get paid leave types.
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope to get unpaid leave types.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Check if this leave type is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if this leave type is paid.
     */
    public function isPaid(): bool
    {
        return $this->is_paid === true;
    }

    /**
     * Check if this leave type requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval === true;
    }

    /**
     * Check if this leave type requires attachment.
     */
    public function requiresAttachment(): bool
    {
        return $this->requires_attachment === true;
    }

    /**
     * Get the default leave types for a company.
     */
    public static function getDefaultTypes(): array
    {
        return [
            [
                'name' => ['en' => 'Annual Leave', 'ar' => 'إجازة سنوية'],
                'description' => ['en' => 'Annual vacation leave', 'ar' => 'إجازة سنوية للراحة'],
                'max_days_per_year' => 21,
                'max_consecutive_days' => 14,
                'min_notice_days' => 7,
                'is_paid' => true,
                'is_active' => true,
                'requires_approval' => true,
                'requires_attachment' => false,
                'color_code' => '#4CAF50',
                'sort_order' => 1,
                'accrual_rate' => 1.75, // 21 days / 12 months
                'carry_over_limit' => 5,
            ],
            [
                'name' => ['en' => 'Sick Leave', 'ar' => 'إجازة مرضية'],
                'description' => ['en' => 'Medical leave for illness', 'ar' => 'إجازة مرضية للعلاج'],
                'max_days_per_year' => 15,
                'max_consecutive_days' => 7,
                'min_notice_days' => 0,
                'is_paid' => true,
                'is_active' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'color_code' => '#F44336',
                'sort_order' => 2,
                'accrual_rate' => 1.25, // 15 days / 12 months
                'carry_over_limit' => 0,
            ],
            [
                'name' => ['en' => 'Personal Leave', 'ar' => 'إجازة شخصية'],
                'description' => ['en' => 'Personal time off', 'ar' => 'إجازة لأمور شخصية'],
                'max_days_per_year' => 5,
                'max_consecutive_days' => 3,
                'min_notice_days' => 3,
                'is_paid' => false,
                'is_active' => true,
                'requires_approval' => true,
                'requires_attachment' => false,
                'color_code' => '#FF9800',
                'sort_order' => 3,
                'accrual_rate' => 0.42, // 5 days / 12 months
                'carry_over_limit' => 0,
            ],
            [
                'name' => ['en' => 'Emergency Leave', 'ar' => 'إجازة طارئة'],
                'description' => ['en' => 'Emergency leave for urgent matters', 'ar' => 'إجازة طارئة للحالات العاجلة'],
                'max_days_per_year' => 3,
                'max_consecutive_days' => 2,
                'min_notice_days' => 0,
                'is_paid' => true,
                'is_active' => true,
                'requires_approval' => true,
                'requires_attachment' => true,
                'color_code' => '#9C27B0',
                'sort_order' => 4,
                'accrual_rate' => 0.25, // 3 days / 12 months
                'carry_over_limit' => 0,
            ],
        ];
    }
}
