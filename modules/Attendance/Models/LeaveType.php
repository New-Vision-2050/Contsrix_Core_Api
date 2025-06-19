<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomBelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;

class LeaveType extends Model implements Auditable
{
    use CustomBelongsToTenant;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'leave_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days_per_year',
        'requires_approval',
        'is_paid',
        'allow_half_day',
        'is_active',
        'min_days_notice_required',
        'allow_carryover',
        'max_carryover_days',
        'is_sick_leave',
        'requires_attachment',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'requires_approval' => 'boolean',
        'is_paid' => 'boolean',
        'allow_half_day' => 'boolean',
        'is_active' => 'boolean',
        'allow_carryover' => 'boolean',
        'is_sick_leave' => 'boolean',
        'requires_attachment' => 'boolean',
    ];

    /**
     * Get the leave balances for this leave type
     */
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get the leave requests for this leave type
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get only active leave types
     */
    public static function getActive()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * Check if leave type requires attachment
     */
    public function requiresAttachment()
    {
        return $this->requires_attachment;
    }

    /**
     * Check if leave type requires advance notice
     */
    public function requiresAdvanceNotice()
    {
        return $this->min_days_notice_required > 0;
    }

    /**
     * Check if a leave request meets advance notice requirements
     * 
     * @param \DateTime|string $startDate
     * @return bool
     */
    public function meetsAdvanceNotice($startDate)
    {
        if (!$this->requiresAdvanceNotice()) {
            return true;
        }

        $today = now();
        $start = $startDate instanceof \DateTime ? $startDate : new \DateTime($startDate);
        
        $daysUntilLeave = $today->diff($start)->days;
        
        return $daysUntilLeave >= $this->min_days_notice_required;
    }
}
