<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Attendance\Models\LeaveType;
use Modules\Attendance\Models\LeaveBalance;
use Modules\Attendance\Models\AttendanceSetting;
use Modules\User\Models\User;

class LeaveService
{
    /**
     * Get leave requests for a specific user
     *
     * @param int $userId
     * @param string|null $status
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserLeaveRequests($userId, $status = null, $startDate = null, $endDate = null, $perPage = 15)
    {
        $query = LeaveRequest::where('user_id', $userId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($startDate) {
            $query->whereDate('start_date', '>=', Carbon::parse($startDate));
        }
        
        if ($endDate) {
            $query->whereDate('end_date', '<=', Carbon::parse($endDate));
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get leave requests for approval by approver ID (supervisor or HR)
     *
     * @param int $approverId
     * @param string|null $status
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getLeaveRequestsForApprover($approverId, $status = null, $startDate = null, $endDate = null, $perPage = 15)
    {
        $user = User::findOrFail($approverId);
        
        if ($user->hasRole(['admin', 'hr_manager'])) {
            // HR managers see requests that have supervisor approval and need HR approval
            // Or all requests if they have admin role
            $query = LeaveRequest::where(function($q) {
                $q->where('supervisor_approved', true)
                  ->where('hr_approved', null);
            })->orWhere(function($q) use ($approverId) {
                $q->where('supervisor_id', $approverId);
            });
        } else {
            // Supervisors only see requests where they are the supervisor
            $query = LeaveRequest::where('supervisor_id', $approverId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($startDate) {
            $query->whereDate('start_date', '>=', Carbon::parse($startDate));
        }
        
        if ($endDate) {
            $query->whereDate('end_date', '<=', Carbon::parse($endDate));
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new leave request
     *
     * @param int $userId
     * @param array $data
     * @param float $totalDays
     * @return LeaveRequest
     */
    public function createLeaveRequest($userId, array $data, $totalDays)
    {
        DB::beginTransaction();
        
        try {
            $user = User::findOrFail($userId);
            $leaveType = LeaveType::findOrFail($data['leave_type_id']);
            
            // Determine supervisor automatically from management hierarchy
            $supervisorId = $this->getSupervisorId($userId);
            
            $leaveRequest = new LeaveRequest();
            $leaveRequest->user_id = $userId;
            $leaveRequest->leave_type_id = $data['leave_type_id'];
            $leaveRequest->supervisor_id = $supervisorId;
            $leaveRequest->start_date = $data['start_date'];
            $leaveRequest->end_date = $data['end_date'];
            $leaveRequest->leave_duration_type = $data['leave_duration_type'] ?? 'full_day';
            $leaveRequest->total_days = $totalDays;
            $leaveRequest->reason = $data['reason'] ?? null;
            $leaveRequest->attachment_path = $data['attachment_path'] ?? null;
            $leaveRequest->status = 'pending';
            
            // If no approval required, auto-approve
            if (!$leaveType->requires_approval) {
                $leaveRequest->status = 'approved';
                $leaveRequest->supervisor_approved = true;
                $leaveRequest->supervisor_approved_at = Carbon::now();
                $leaveRequest->hr_approved = true;
                $leaveRequest->hr_approved_at = Carbon::now();
                
                // Consume leave balance immediately
                $year = Carbon::parse($data['start_date'])->year;
                $leaveBalance = LeaveBalance::getOrCreate($userId, $data['leave_type_id'], $year);
                $leaveBalance->consumeDays($totalDays);
            } else {
                // Reserve the days if approval is required
                $year = Carbon::parse($data['start_date'])->year;
                $leaveBalance = LeaveBalance::getOrCreate($userId, $data['leave_type_id'], $year);
                $leaveBalance->reserveDays($totalDays);
            }
            
            $leaveRequest->save();
            
            DB::commit();
            
            return $leaveRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing leave request
     *
     * @param LeaveRequest $leaveRequest
     * @param array $data
     * @param float $totalDays
     * @return LeaveRequest
     */
    public function updateLeaveRequest(LeaveRequest $leaveRequest, array $data, $totalDays)
    {
        DB::beginTransaction();
        
        try {
            // Release the reserved days from the old balance
            $oldYear = Carbon::parse($leaveRequest->start_date)->year;
            $oldBalance = LeaveBalance::getOrCreate(
                $leaveRequest->user_id,
                $leaveRequest->leave_type_id,
                $oldYear
            );
            $oldBalance->releaseDays($leaveRequest->total_days);
            
            // Update leave request details
            $leaveRequest->leave_type_id = $data['leave_type_id'];
            $leaveRequest->start_date = $data['start_date'];
            $leaveRequest->end_date = $data['end_date'];
            $leaveRequest->leave_duration_type = $data['leave_duration_type'] ?? 'full_day';
            $leaveRequest->total_days = $totalDays;
            $leaveRequest->reason = $data['reason'] ?? $leaveRequest->reason;
            
            if (isset($data['attachment_path'])) {
                $leaveRequest->attachment_path = $data['attachment_path'];
            }
            
            // If the leave type doesn't require approval, auto-approve
            $leaveType = LeaveType::find($data['leave_type_id']);
            if (!$leaveType->requires_approval) {
                $leaveRequest->status = 'approved';
                $leaveRequest->supervisor_approved = true;
                $leaveRequest->supervisor_approved_at = Carbon::now();
                $leaveRequest->hr_approved = true;
                $leaveRequest->hr_approved_at = Carbon::now();
                
                // Consume leave balance immediately
                $newYear = Carbon::parse($data['start_date'])->year;
                $newBalance = LeaveBalance::getOrCreate(
                    $leaveRequest->user_id,
                    $data['leave_type_id'],
                    $newYear
                );
                $newBalance->consumeDays($totalDays);
            } else {
                // Reset approval statuses
                $leaveRequest->supervisor_approved = null;
                $leaveRequest->supervisor_approved_at = null;
                $leaveRequest->supervisor_comments = null;
                $leaveRequest->hr_approved = null;
                $leaveRequest->hr_approved_at = null;
                $leaveRequest->hr_comments = null;
                $leaveRequest->status = 'pending';
                
                // Reserve days in the new balance
                $newYear = Carbon::parse($data['start_date'])->year;
                $newBalance = LeaveBalance::getOrCreate(
                    $leaveRequest->user_id,
                    $data['leave_type_id'],
                    $newYear
                );
                $newBalance->reserveDays($totalDays);
            }
            
            $leaveRequest->save();
            
            DB::commit();
            
            return $leaveRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve a leave request
     *
     * @param int $leaveRequestId
     * @param int $approverId
     * @param string|null $comments
     * @param bool $isHrApproval
     * @return LeaveRequest
     */
    public function approveLeaveRequest($leaveRequestId, $approverId, $comments = null, $isHrApproval = false)
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
            
            if ($leaveRequest->status !== 'pending') {
                throw new \Exception('Only pending leave requests can be approved');
            }
            
            if ($isHrApproval) {
                // HR approval
                if (!$leaveRequest->supervisor_approved) {
                    throw new \Exception('Supervisor approval required before HR can approve');
                }
                
                $leaveRequest->hr_approved = true;
                $leaveRequest->hr_approved_at = Carbon::now();
                $leaveRequest->hr_approved_by = $approverId;
                $leaveRequest->hr_comments = $comments;
                $leaveRequest->status = 'approved';
                
                // Process the leave balance - convert from reserved to used
                $year = Carbon::parse($leaveRequest->start_date)->year;
                $leaveBalance = LeaveBalance::getOrCreate(
                    $leaveRequest->user_id,
                    $leaveRequest->leave_type_id,
                    $year
                );
                $leaveBalance->reservedToConsumed($leaveRequest->total_days);
            } else {
                // Supervisor approval
                $leaveRequest->supervisor_approved = true;
                $leaveRequest->supervisor_approved_at = Carbon::now();
                $leaveRequest->supervisor_approved_by = $approverId;
                $leaveRequest->supervisor_comments = $comments;
                
                // Check if HR approval is required by the leave type
                $leaveType = LeaveType::find($leaveRequest->leave_type_id);
                if (!$leaveType->requires_hr_approval) {
                    // If HR approval is not needed, mark as fully approved
                    $leaveRequest->hr_approved = true;
                    $leaveRequest->hr_approved_at = Carbon::now();
                    $leaveRequest->status = 'approved';
                    
                    // Process the leave balance
                    $year = Carbon::parse($leaveRequest->start_date)->year;
                    $leaveBalance = LeaveBalance::getOrCreate(
                        $leaveRequest->user_id,
                        $leaveRequest->leave_type_id,
                        $year
                    );
                    $leaveBalance->reservedToConsumed($leaveRequest->total_days);
                }
            }
            
            $leaveRequest->save();
            
            DB::commit();
            
            return $leaveRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a leave request
     *
     * @param int $leaveRequestId
     * @param int $rejecterId
     * @param string $comments
     * @param bool $isHrRejection
     * @return LeaveRequest
     */
    public function rejectLeaveRequest($leaveRequestId, $rejecterId, $comments, $isHrRejection = false)
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
            
            if ($leaveRequest->status !== 'pending') {
                throw new \Exception('Only pending leave requests can be rejected');
            }
            
            if ($isHrRejection) {
                // HR rejection
                $leaveRequest->hr_approved = false;
                $leaveRequest->hr_approved_at = Carbon::now();
                $leaveRequest->hr_approved_by = $rejecterId;
                $leaveRequest->hr_comments = $comments;
            } else {
                // Supervisor rejection
                $leaveRequest->supervisor_approved = false;
                $leaveRequest->supervisor_approved_at = Carbon::now();
                $leaveRequest->supervisor_approved_by = $rejecterId;
                $leaveRequest->supervisor_comments = $comments;
            }
            
            $leaveRequest->status = 'rejected';
            $leaveRequest->save();
            
            // Return the reserved days to the balance
            $year = Carbon::parse($leaveRequest->start_date)->year;
            $leaveBalance = LeaveBalance::getOrCreate(
                $leaveRequest->user_id,
                $leaveRequest->leave_type_id,
                $year
            );
            $leaveBalance->releaseDays($leaveRequest->total_days);
            
            DB::commit();
            
            return $leaveRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a leave request
     *
     * @param int $leaveRequestId
     * @param string $reason
     * @return LeaveRequest
     */
    public function cancelLeaveRequest($leaveRequestId, $reason = null)
    {
        DB::beginTransaction();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($leaveRequestId);
            
            if (in_array($leaveRequest->status, ['rejected', 'cancelled'])) {
                throw new \Exception('This leave request cannot be cancelled');
            }
            
            $wasApproved = $leaveRequest->status === 'approved';
            
            $leaveRequest->status = 'cancelled';
            $leaveRequest->cancellation_reason = $reason;
            $leaveRequest->cancelled_at = Carbon::now();
            $leaveRequest->save();
            
            // Return the days to the balance
            $year = Carbon::parse($leaveRequest->start_date)->year;
            $leaveBalance = LeaveBalance::getOrCreate(
                $leaveRequest->user_id,
                $leaveRequest->leave_type_id,
                $year
            );
            
            if ($wasApproved) {
                // If it was approved, return consumed days
                $leaveBalance->returnDays($leaveRequest->total_days);
            } else {
                // If it was pending, release reserved days
                $leaveBalance->releaseDays($leaveRequest->total_days);
            }
            
            DB::commit();
            
            return $leaveRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get leave balances for a user
     *
     * @param int $userId
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserLeaveBalances($userId, $year = null)
    {
        $year = $year ?: Carbon::now()->year;
        
        // Get all active leave types
        $leaveTypes = LeaveType::where('is_active', true)->get();
        
        $balances = [];
        foreach ($leaveTypes as $leaveType) {
            // Get or create balance for this type and year
            $balance = LeaveBalance::getOrCreate($userId, $leaveType->id, $year);
            $balances[] = [
                'leave_type' => $leaveType->name,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
                'allocated_days' => $balance->allocated_days,
                'carryover_days' => $balance->carryover_days,
                'used_days' => $balance->used_days,
                'pending_days' => $balance->pending_days,
                'remaining_days' => $balance->remaining_days,
                'expired_days' => $balance->expired_days
            ];
        }
        
        return collect($balances);
    }

    /**
     * Helper method to get supervisor ID from management hierarchy
     *
     * @param int $userId
     * @return int|null
     */
    private function getSupervisorId($userId)
    {
        try {
            // Get user's management hierarchy information
            $user = User::findOrFail($userId);
            
            // Check if the user is assigned to a management hierarchy
            if (!$user->management_hierarchy_id) {
                return null;
            }
            
            // Find the user's management hierarchy record
            $userHierarchy = DB::table('management_hierarchies')
                ->where('id', $user->management_hierarchy_id)
                ->first();
                
            if (!$userHierarchy || !$userHierarchy->parent_id) {
                return null;
            }
            
            // Find the parent (supervisor) management hierarchy record
            $parentHierarchy = DB::table('management_hierarchies')
                ->where('id', $userHierarchy->parent_id)
                ->first();
                
            if (!$parentHierarchy) {
                return null;
            }
            
            // Find the supervisor user assigned to this parent hierarchy
            $supervisor = User::where('management_hierarchy_id', $parentHierarchy->id)
                ->where('is_supervisor', true)
                ->first();
                
            // Return the supervisor's ID if found
            return $supervisor ? $supervisor->id : null;
        } catch (\Exception $e) {
            \Log::error('Error determining supervisor: ' . $e->getMessage());
            return null;
        }
    }
}
