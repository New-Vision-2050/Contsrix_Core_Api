<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CustomBelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;
use Modules\User\Models\User;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class LeaveRequest extends Model implements Auditable
{
    use CustomBelongsToTenant;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'leave_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'management_hierarchy_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'leave_duration_type',
        'total_days',
        'reason',
        'supervisor_id',
        'status',
        'supervisor_action_date',
        'hr_approver_id',
        'hr_action_date',
        'hr_status',
        'attachment_path',
        'supervisor_comments',
        'hr_comments',
        'cancel_reason'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'float',
        'supervisor_action_date' => 'datetime',
        'hr_action_date' => 'datetime',
    ];

    /**
     * Get the employee who requested the leave.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the supervisor who approved/rejected the leave.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the HR personnel who approved/rejected the leave.
     */
    public function hrApprover()
    {
        return $this->belongsTo(User::class, 'hr_approver_id');
    }

    /**
     * Get the leave type for this request.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    /**
     * Get the management hierarchy for this request.
     */
    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    /**
     * Calculate total days for a leave request
     * Handles half days and weekends appropriately
     *
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @param string $durationType full_day|first_half|second_half
     * @return float
     */
    public static function calculateLeaveDays($startDate, $endDate, $durationType = 'full_day')
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // Same day request
        if ($start->isSameDay($end)) {
            return $durationType === 'full_day' ? 1.0 : 0.5;
        }
        
        // Multi-day request
        $settings = AttendanceSetting::getSettings();
        $totalDays = 0;
        $currentDate = clone $start;
        
        while ($currentDate->lte($end)) {
            // Skip weekends based on settings
            if ($settings && !$settings->isWeekend($currentDate->format('Y-m-d'))) {
                // First day might be half-day
                if ($currentDate->isSameDay($start) && $durationType === 'second_half') {
                    $totalDays += 0.5;
                }
                // Last day might be half-day
                elseif ($currentDate->isSameDay($end) && $durationType === 'first_half') {
                    $totalDays += 0.5;
                }
                // All other days are full days
                else {
                    $totalDays += 1.0;
                }
            }
            
            $currentDate->addDay();
        }
        
        return $totalDays;
    }

    /**
     * Calculate and update the total days for this leave request
     */
    public function updateTotalDays()
    {
        $this->total_days = self::calculateLeaveDays(
            $this->start_date,
            $this->end_date,
            $this->leave_duration_type
        );
        
        return $this;
    }

    /**
     * Check for conflicting leave requests
     *
     * @return bool
     */
    public function hasConflicts()
    {
        return self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) {
                $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                    ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                    ->orWhere(function ($q) {
                        $q->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                    });
            })
            ->exists();
    }

    /**
     * Process approval of leave request
     *
     * @param string $approverId
     * @param string $comments
     * @param bool $isHrApproval
     * @return bool
     */
    public function approve($approverId, $comments = null, $isHrApproval = false)
    {
        if ($isHrApproval) {
            $this->hr_approver_id = $approverId;
            $this->hr_action_date = now();
            $this->hr_status = 'approved';
            $this->hr_comments = $comments;
            
            // If HR approval is final, update status and consume days
            $this->status = 'approved';
            
            // Update leave balance
            $balance = LeaveBalance::getOrCreate(
                $this->user_id, 
                $this->leave_type_id, 
                Carbon::parse($this->start_date)->year
            );
            
            return $balance->consumeDays($this->total_days) && $this->save();
        } else {
            $this->supervisor_id = $approverId;
            $this->supervisor_action_date = now();
            $this->supervisor_comments = $comments;
            
            // If no HR approval needed or it's already approved by HR
            $leaveType = $this->leaveType;
            if (!$leaveType || !$leaveType->requires_approval) {
                $this->status = 'approved';
                
                // Update leave balance
                $balance = LeaveBalance::getOrCreate(
                    $this->user_id, 
                    $this->leave_type_id, 
                    Carbon::parse($this->start_date)->year
                );
                
                return $balance->consumeDays($this->total_days) && $this->save();
            }
            
            // Otherwise, just update status and save
            $this->status = 'approved';
            return $this->save();
        }
    }

    /**
     * Process rejection of leave request
     *
     * @param string $rejecterId
     * @param string $comments
     * @param bool $isHrRejection
     * @return bool
     */
    public function reject($rejecterId, $comments, $isHrRejection = false)
    {
        if ($isHrRejection) {
            $this->hr_approver_id = $rejecterId;
            $this->hr_action_date = now();
            $this->hr_status = 'rejected';
            $this->hr_comments = $comments;
        } else {
            $this->supervisor_id = $rejecterId;
            $this->supervisor_action_date = now();
            $this->supervisor_comments = $comments;
        }
        
        $this->status = 'rejected';
        
        // Release pending days from leave balance
        $balance = LeaveBalance::getOrCreate(
            $this->user_id, 
            $this->leave_type_id, 
            Carbon::parse($this->start_date)->year
        );
        
        return $balance->releaseDays($this->total_days) && $this->save();
    }

    /**
     * Process cancellation of leave request
     *
     * @param string $reason
     * @return bool
     */
    public function cancelRequest($reason)
    {
        $this->cancel_reason = $reason;
        $this->status = 'cancelled';
        
        // If it was approved, return the days to balance
        if ($this->getOriginal('status') === 'approved') {
            $balance = LeaveBalance::getOrCreate(
                $this->user_id, 
                $this->leave_type_id, 
                Carbon::parse($this->start_date)->year
            );
            
            return $balance->returnDays($this->total_days) && $this->save();
        }
        
        // If it was pending, release the pending days
        if ($this->getOriginal('status') === 'pending') {
            $balance = LeaveBalance::getOrCreate(
                $this->user_id, 
                $this->leave_type_id, 
                Carbon::parse($this->start_date)->year
            );
            
            return $balance->releaseDays($this->total_days) && $this->save();
        }
        
        return $this->save();
    }

    /**
     * Check if leave request is for current or future dates
     *
     * @return bool
     */
    public function isActive()
    {
        return Carbon::parse($this->end_date)->endOfDay()->isFuture();
    }
}
