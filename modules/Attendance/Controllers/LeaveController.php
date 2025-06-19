<?php

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Attendance\Models\LeaveType;
use Modules\Attendance\Models\LeaveBalance;
use Modules\Attendance\Services\LeaveService;
use Carbon\Carbon;

class LeaveController extends Controller
{
    protected $leaveService;

    /**
     * Create a new controller instance.
     *
     * @param LeaveService $leaveService
     */
    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Display a listing of leave requests for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page', 15);
        
        try {
            if ($user->hasRole(['admin', 'hr_manager'])) {
                $leaveRequests = $this->leaveService->getLeaveRequestsForApprover($user->id, $status, $startDate, $endDate, $perPage);
            } else {
                $leaveRequests = $this->leaveService->getUserLeaveRequests($user->id, $status, $startDate, $endDate, $perPage);
            }
            
            return response()->json([
                'data' => $leaveRequests->items(),
                'meta' => [
                    'current_page' => $leaveRequests->currentPage(),
                    'last_page' => $leaveRequests->lastPage(),
                    'per_page' => $leaveRequests->perPage(),
                    'total' => $leaveRequests->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve leave requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created leave request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_duration_type' => 'required|in:full_day,first_half,second_half',
            'reason' => 'nullable|string|max:255',
            'attachment_path' => 'nullable|string'
        ]);
        
        try {
            $totalDays = LeaveRequest::calculateLeaveDays(
                $data['start_date'],
                $data['end_date'],
                $data['leave_duration_type']
            );
            
            $leaveRequest = $this->leaveService->createLeaveRequest($user->id, $data, $totalDays);
            
            return response()->json([
                'message' => 'Leave request submitted successfully',
                'data' => $leaveRequest
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified leave request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            
            if ($leaveRequest->user_id !== $user->id && 
                !$user->hasRole(['admin', 'hr_manager']) && 
                $leaveRequest->supervisor_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized to view this leave request'
                ], 403);
            }
            
            return response()->json([
                'data' => $leaveRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified leave request
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_duration_type' => 'required|in:full_day,first_half,second_half',
            'reason' => 'nullable|string|max:255',
            'attachment_path' => 'nullable|string'
        ]);
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            
            if ($leaveRequest->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized to update this leave request'
                ], 403);
            }
            
            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'Only pending leave requests can be updated'
                ], 400);
            }
            
            $totalDays = LeaveRequest::calculateLeaveDays(
                $data['start_date'],
                $data['end_date'],
                $data['leave_duration_type']
            );
            
            $updated = $this->leaveService->updateLeaveRequest($leaveRequest, $data, $totalDays);
            
            return response()->json([
                'message' => 'Leave request updated successfully',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified leave request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            
            if ($leaveRequest->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized to cancel this leave request'
                ], 403);
            }
            
            if (in_array($leaveRequest->status, ['rejected', 'cancelled'])) {
                return response()->json([
                    'message' => 'This leave request cannot be cancelled'
                ], 400);
            }
            
            $this->leaveService->cancelLeaveRequest($id, 'Cancelled by user');
            
            return response()->json([
                'message' => 'Leave request cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a leave request
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        $comments = $request->input('comments');
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            
            if (!$user->hasRole(['admin', 'hr_manager', 'supervisor'])) {
                return response()->json([
                    'message' => 'Unauthorized to approve leave requests'
                ], 403);
            }
            
            $isHrApproval = $user->hasRole(['admin', 'hr_manager']);
            $result = $this->leaveService->approveLeaveRequest($id, $user->id, $comments, $isHrApproval);
            
            return response()->json([
                'message' => 'Leave request approved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a leave request
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $comments = $request->input('comments');
        
        if (empty($comments)) {
            return response()->json([
                'message' => 'Comments are required when rejecting a leave request'
            ], 400);
        }
        
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);
            
            if (!$user->hasRole(['admin', 'hr_manager', 'supervisor'])) {
                return response()->json([
                    'message' => 'Unauthorized to reject leave requests'
                ], 403);
            }
            
            $isHrRejection = $user->hasRole(['admin', 'hr_manager']);
            $result = $this->leaveService->rejectLeaveRequest($id, $user->id, $comments, $isHrRejection);
            
            return response()->json([
                'message' => 'Leave request rejected',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave balance for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', Carbon::now()->year);
        
        try {
            $balances = $this->leaveService->getUserLeaveBalances($user->id, $year);
            
            return response()->json([
                'data' => $balances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve leave balances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available leave types
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveTypes()
    {
        try {
            $leaveTypes = LeaveType::where('is_active', true)->get();
            
            return response()->json([
                'data' => $leaveTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve leave types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave types for admin
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLeaveTypes()
    {
        try {
            $leaveTypes = LeaveType::all();
            
            return response()->json([
                'data' => $leaveTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve leave types: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a leave type
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createLeaveType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:leave_types,code',
            'description' => 'nullable|string',
            'default_days_per_year' => 'required|numeric|min:0',
            'requires_approval' => 'boolean',
            'is_paid' => 'boolean',
            'allow_half_day' => 'boolean',
            'min_days_notice_required' => 'integer|min:0',
            'allow_carryover' => 'boolean',
            'max_carryover_days' => 'integer|min:0',
            'is_sick_leave' => 'boolean',
            'requires_attachment' => 'boolean',
        ]);
        
        try {
            $data['created_by'] = Auth::id();
            $leaveType = LeaveType::create($data);
            
            return response()->json([
                'message' => 'Leave type created successfully',
                'data' => $leaveType
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create leave type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a leave type
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLeaveType(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:leave_types,code,'.$id,
            'description' => 'nullable|string',
            'default_days_per_year' => 'required|numeric|min:0',
            'requires_approval' => 'boolean',
            'is_paid' => 'boolean',
            'allow_half_day' => 'boolean',
            'is_active' => 'boolean',
            'min_days_notice_required' => 'integer|min:0',
            'allow_carryover' => 'boolean',
            'max_carryover_days' => 'integer|min:0',
            'is_sick_leave' => 'boolean',
            'requires_attachment' => 'boolean',
        ]);
        
        try {
            $leaveType = LeaveType::findOrFail($id);
            $data['updated_by'] = Auth::id();
            $leaveType->update($data);
            
            return response()->json([
                'message' => 'Leave type updated successfully',
                'data' => $leaveType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update leave type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a leave type
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLeaveType($id)
    {
        try {
            $leaveType = LeaveType::findOrFail($id);
            
            // Check if leave type is in use
            $hasRequests = LeaveRequest::where('leave_type_id', $id)->exists();
            if ($hasRequests) {
                // Instead of deleting, just mark as inactive
                $leaveType->is_active = false;
                $leaveType->save();
                
                return response()->json([
                    'message' => 'Leave type has been deactivated as it is in use'
                ]);
            }
            
            $leaveType->delete();
            
            return response()->json([
                'message' => 'Leave type deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete leave type: ' . $e->getMessage()
            ], 500);
        }
    }
}
