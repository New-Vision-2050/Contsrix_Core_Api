<?php

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSetting;
use Modules\Attendance\Models\BreakRecord;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Requests\ClockInRequest;
use Modules\Attendance\Requests\ClockOutRequest;
use Modules\Attendance\Requests\BreakRequest;
use Modules\Attendance\Resources\AttendanceResource;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $attendanceService;

    /**
     * Create a new controller instance.
     *
     * @param AttendanceService $attendanceService
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Clock in an employee
     *
     * @param ClockInRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clockIn(ClockInRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        
        try {
            // Check if already clocked in
            $activeRecord = $this->attendanceService->getActiveAttendanceRecord($user->id);
            if ($activeRecord) {
                return response()->json([
                    'message' => 'You are already clocked in for today',
                    'data' => new AttendanceResource($activeRecord)
                ], 400);
            }
            
            // Create attendance record
            $record = $this->attendanceService->clockIn($user->id, $data);
            
            return response()->json([
                'message' => 'Clock in successful',
                'data' => new AttendanceResource($record)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Clock in failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clock out an employee
     *
     * @param ClockOutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clockOut(ClockOutRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        
        try {
            // Check if clocked in
            $activeRecord = $this->attendanceService->getActiveAttendanceRecord($user->id);
            if (!$activeRecord) {
                return response()->json([
                    'message' => 'You must clock in before clocking out'
                ], 400);
            }
            
            // Check for active breaks
            $activeBreak = $this->attendanceService->getActiveBreak($activeRecord->id);
            if ($activeBreak) {
                // Auto end the break
                $this->attendanceService->endBreak($activeBreak->id);
            }
            
            // Process clock out
            $record = $this->attendanceService->clockOut($activeRecord->id, $data);
            
            return response()->json([
                'message' => 'Clock out successful',
                'data' => new AttendanceResource($record)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Clock out failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a break
     *
     * @param BreakRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startBreak(BreakRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        
        try {
            // Check if clocked in
            $activeRecord = $this->attendanceService->getActiveAttendanceRecord($user->id);
            if (!$activeRecord) {
                return response()->json([
                    'message' => 'You must be clocked in to start a break'
                ], 400);
            }
            
            // Check for existing breaks
            $activeBreak = $this->attendanceService->getActiveBreak($activeRecord->id);
            if ($activeBreak) {
                return response()->json([
                    'message' => 'You already have an active break'
                ], 400);
            }
            
            // Check max breaks limit
            $settings = AttendanceSetting::getSettings();
            $breakCount = $this->attendanceService->getBreakCount($activeRecord->id);
            if ($settings && $breakCount >= $settings->max_breaks_per_day) {
                return response()->json([
                    'message' => 'You have reached the maximum number of breaks allowed per day'
                ], 400);
            }
            
            // Start break
            $break = $this->attendanceService->startBreak($activeRecord->id, $data);
            
            return response()->json([
                'message' => 'Break started successfully',
                'data' => $break
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start break: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * End a break
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function endBreak(Request $request)
    {
        $user = Auth::user();
        
        try {
            // Check if clocked in
            $activeRecord = $this->attendanceService->getActiveAttendanceRecord($user->id);
            if (!$activeRecord) {
                return response()->json([
                    'message' => 'You must be clocked in to end a break'
                ], 400);
            }
            
            // Check for active break
            $activeBreak = $this->attendanceService->getActiveBreak($activeRecord->id);
            if (!$activeBreak) {
                return response()->json([
                    'message' => 'You do not have an active break to end'
                ], 400);
            }
            
            // End break
            $break = $this->attendanceService->endBreak($activeBreak->id);
            
            return response()->json([
                'message' => 'Break ended successfully',
                'data' => $break
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to end break: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current attendance status for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentStatus()
    {
        $user = Auth::user();
        
        try {
            $status = $this->attendanceService->getUserAttendanceStatus($user->id);
            
            return response()->json([
                'data' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get attendance status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance history for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        try {
            $history = $this->attendanceService->getAttendanceHistory($user->id, $startDate, $endDate, $perPage);
            
            return response()->json([
                'data' => AttendanceResource::collection($history->items()),
                'meta' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get attendance history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's attendance record for the authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function today()
    {
        $user = Auth::user();
        
        try {
            $record = $this->attendanceService->getTodayAttendance($user->id);
            
            if (!$record) {
                return response()->json([
                    'message' => 'No attendance record found for today',
                    'data' => null
                ]);
            }
            
            return response()->json([
                'data' => new AttendanceResource($record)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get today\'s attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        try {
            $settings = AttendanceSetting::getSettings();
            
            return response()->json([
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get attendance settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update attendance settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        try {
            $settings = AttendanceSetting::getSettings();
            if (!$settings) {
                $settings = new AttendanceSetting();
            }
            
            $settings->fill($request->all());
            $settings->save();
            
            return response()->json([
                'message' => 'Attendance settings updated successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update attendance settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
