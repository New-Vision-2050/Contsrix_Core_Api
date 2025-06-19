<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomBelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;
use Modules\User\Models\User;

class LeaveBalance extends Model implements Auditable
{
    use CustomBelongsToTenant;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'leave_balances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'used_days',
        'pending_days',
        'carryover_days',
        'last_accrual_date',
        'expires_at',
        'notes',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'entitled_days' => 'float',
        'used_days' => 'float',
        'pending_days' => 'float',
        'carryover_days' => 'float',
        'last_accrual_date' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Get the user that owns this leave balance.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the leave type associated with this balance.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    /**
     * Calculate remaining balance (entitled + carryover - used - pending)
     * 
     * @return float
     */
    public function getRemainingDaysAttribute()
    {
        return $this->entitled_days + $this->carryover_days - $this->used_days - $this->pending_days;
    }

    /**
     * Determine if there's enough balance for a leave request
     * 
     * @param float $requestDays
     * @return bool
     */
    public function hasEnoughBalance($requestDays)
    {
        return $this->remaining_days >= $requestDays;
    }

    /**
     * Reserve days for a pending leave request
     * 
     * @param float $days
     * @return bool
     */
    public function reserveDays($days)
    {
        if (!$this->hasEnoughBalance($days)) {
            return false;
        }
        
        $this->pending_days += $days;
        return $this->save();
    }

    /**
     * Release reserved days when a leave request is cancelled or rejected
     * 
     * @param float $days
     * @return bool
     */
    public function releaseDays($days)
    {
        $this->pending_days = max(0, $this->pending_days - $days);
        return $this->save();
    }

    /**
     * Consume days when a leave is approved
     * 
     * @param float $days
     * @return bool
     */
    public function consumeDays($days)
    {
        $this->used_days += $days;
        $this->pending_days = max(0, $this->pending_days - $days);
        return $this->save();
    }

    /**
     * Return used days when an approved leave is cancelled
     * 
     * @param float $days
     * @return bool
     */
    public function returnDays($days)
    {
        $this->used_days = max(0, $this->used_days - $days);
        return $this->save();
    }

    /**
     * Get or create a leave balance for a user and leave type
     * 
     * @param string $userId
     * @param int $leaveTypeId
     * @param int|null $year
     * @return LeaveBalance
     */
    public static function getOrCreate($userId, $leaveTypeId, $year = null)
    {
        $year = $year ?? Carbon::now()->year;
        
        $balance = self::firstOrNew([
            'user_id' => $userId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year
        ]);
        
        if (!$balance->exists) {
            // Initialize with default values from leave type
            $leaveType = LeaveType::find($leaveTypeId);
            if ($leaveType) {
                $balance->entitled_days = $leaveType->default_days_per_year;
                $balance->save();
            }
        }
        
        return $balance;
    }
}
