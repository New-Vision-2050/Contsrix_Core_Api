<?php

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\LeaveService;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\LeaveRequest;
use Modules\User\Models\User;

class ReportingController extends Controller
{
    protected $attendanceService;
    protected $leaveService;

    /**
     * Create a new controller instance.
     *
     * @param AttendanceService $attendanceService
     * @param LeaveService $leaveService
     */
    public function __construct(AttendanceService $attendanceService, LeaveService $leaveService)
    {
        $this->attendanceService = $attendanceService;
        $this->leaveService = $leaveService;
    }

    /**
     * Generate attendance summary report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attendanceSummary(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $userId = $request->input('user_id');
        
        // Check permissions
        if ($userId && $userId != $user->id && !$user->hasRole(['admin', 'hr_manager', 'supervisor'])) {
            return response()->json([
                'message' => 'Unauthorized to view attendance report for other users'
            ], 403);
        }
        
        // If no user_id is specified, use the authenticated user
        $targetUserId = $userId ?: $user->id;
        
        try {
            $summary = $this->attendanceService->getAttendanceSummary($targetUserId, $startDate, $endDate);
            
            return response()->json([
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate attendance summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate departmental attendance report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function departmentAttendance(Request $request)
    {
        $user = Auth::user();
        
        // Only admins, HR, and supervisors can access department reports
        if (!$user->hasRole(['admin', 'hr_manager', 'supervisor'])) {
            return response()->json([
                'message' => 'Unauthorized to view department attendance report'
            ], 403);
        }
        
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $departmentId = $request->input('department_id');
        $managementHierarchyId = $request->input('management_hierarchy_id');
        
        try {
            if ($managementHierarchyId) {
                // Get users in this management hierarchy
                $users = User::where('management_hierarchy_id', $managementHierarchyId)->get();
            } elseif ($departmentId) {
                // Get users in this department
                $users = User::where('department_id', $departmentId)->get();
            } else {
                // If supervisor, get their direct reports based on management hierarchy
                if ($user->hasRole('supervisor') && !$user->hasRole(['admin', 'hr_manager'])) {
                    // Get the supervisor's management hierarchy
                    $supervisorHierarchy = DB::table('management_hierarchies')
                        ->where('id', $user->management_hierarchy_id)
                        ->first();
                    
                    if (!$supervisorHierarchy) {
                        return response()->json([
                            'message' => 'No management hierarchy found for this supervisor'
                        ], 404);
                    }
                    
                    // Get users who report to this supervisor
                    $users = User::whereHas('managementHierarchy', function ($query) use ($supervisorHierarchy) {
                        $query->where('parent_id', $supervisorHierarchy->id);
                    })->get();
                } else {
                    // For admins and HR, get all users
                    $users = User::all();
                }
            }
            
            $reports = [];
            foreach ($users as $user) {
                $summary = $this->attendanceService->getAttendanceSummary($user->id, $startDate, $endDate);
                $reports[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department ? $user->department->name : null,
                    'position' => $user->position,
                    'summary' => $summary
                ];
            }
            
            return response()->json([
                'data' => $reports,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_users' => count($reports)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate department attendance report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate leave summary report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveSummary(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', Carbon::now()->year);
        $userId = $request->input('user_id');
        
        // Check permissions
        if ($userId && $userId != $user->id && !$user->hasRole(['admin', 'hr_manager', 'supervisor'])) {
            return response()->json([
                'message' => 'Unauthorized to view leave report for other users'
            ], 403);
        }
        
        // If no user_id is specified, use the authenticated user
        $targetUserId = $userId ?: $user->id;
        
        try {
            // Get leave balances
            $balances = $this->leaveService->getUserLeaveBalances($targetUserId, $year);
            
            // Get leave requests for the year
            $startDate = Carbon::createFromDate($year, 1, 1)->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, 12, 31)->format('Y-m-d');
            
            $leaveRequests = LeaveRequest::where('user_id', $targetUserId)
                ->whereBetween('start_date', [$startDate, $endDate])
                ->orderBy('start_date', 'desc')
                ->get();
            
            return response()->json([
                'data' => [
                    'balances' => $balances,
                    'requests' => $leaveRequests
                ],
                'meta' => [
                    'year' => $year,
                    'user_id' => $targetUserId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate leave summary: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate departmental leave report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function departmentLeave(Request $request)
    {
        $user = Auth::user();
        
        // Only admins, HR, and supervisors can access department reports
        if (!$user->hasRole(['admin', 'hr_manager', 'supervisor'])) {
            return response()->json([
                'message' => 'Unauthorized to view department leave report'
            ], 403);
        }
        
        $year = $request->input('year', Carbon::now()->year);
        $departmentId = $request->input('department_id');
        $managementHierarchyId = $request->input('management_hierarchy_id');
        
        try {
            if ($managementHierarchyId) {
                // Get users in this management hierarchy
                $users = User::where('management_hierarchy_id', $managementHierarchyId)->get();
            } elseif ($departmentId) {
                // Get users in this department
                $users = User::where('department_id', $departmentId)->get();
            } else {
                // If supervisor, get their direct reports based on management hierarchy
                if ($user->hasRole('supervisor') && !$user->hasRole(['admin', 'hr_manager'])) {
                    // Get the supervisor's management hierarchy
                    $supervisorHierarchy = \DB::table('management_hierarchies')
                        ->where('id', $user->management_hierarchy_id)
                        ->first();
                    
                    if (!$supervisorHierarchy) {
                        return response()->json([
                            'message' => 'No management hierarchy found for this supervisor'
                        ], 404);
                    }
                    
                    // Get users who report to this supervisor
                    $users = User::whereHas('managementHierarchy', function ($query) use ($supervisorHierarchy) {
                        $query->where('parent_id', $supervisorHierarchy->id);
                    })->get();
                } else {
                    // For admins and HR, get all users
                    $users = User::all();
                }
            }
            
            $reports = [];
            foreach ($users as $user) {
                $balances = $this->leaveService->getUserLeaveBalances($user->id, $year);
                
                // Count leave days by status
                $startDate = Carbon::createFromDate($year, 1, 1)->format('Y-m-d');
                $endDate = Carbon::createFromDate($year, 12, 31)->format('Y-m-d');
                
                $leaveRequests = LeaveRequest::where('user_id', $user->id)
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->get();
                
                $approvedDays = 0;
                $pendingDays = 0;
                $rejectedDays = 0;
                
                foreach ($leaveRequests as $request) {
                    if ($request->status === 'approved') {
                        $approvedDays += $request->total_days;
                    } elseif ($request->status === 'pending') {
                        $pendingDays += $request->total_days;
                    } elseif ($request->status === 'rejected') {
                        $rejectedDays += $request->total_days;
                    }
                }
                
                $reports[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department ? $user->department->name : null,
                    'position' => $user->position,
                    'balances' => $balances,
                    'leave_summary' => [
                        'approved_days' => $approvedDays,
                        'pending_days' => $pendingDays,
                        'rejected_days' => $rejectedDays,
                        'total_requests' => count($leaveRequests)
                    ]
                ];
            }
            
            return response()->json([
                'data' => $reports,
                'meta' => [
                    'year' => $year,
                    'total_users' => count($reports)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate department leave report: ' . $e->getMessage()
            ], 500);
        }
    }
}
