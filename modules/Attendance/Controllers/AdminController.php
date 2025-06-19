<?php

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Modules\Attendance\Models\AttendanceSetting;
use Modules\Attendance\Models\LeaveBalance;
use Modules\Attendance\Models\LeaveType;
use Modules\User\Models\User;

class AdminController extends Controller
{
    /**
     * Get attendance settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        try {
            $settings = AttendanceSetting::first();
            
            return response()->json([
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve attendance settings: ' . $e->getMessage()
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
            $settings = AttendanceSetting::firstOrNew([]);
            
            $settings->fill($request->only([
                'work_start_time',
                'work_end_time',
                'attendance_grace_period',
                'weekend_days',
                'min_work_hours',
                'max_work_hours',
                'overtime_threshold',
                'overtime_multiplier',
                'max_breaks_per_day',
                'max_break_duration',
                'enable_location_tracking',
                'allow_remote_clock_in',
                'max_allowed_distance',
                'require_approval_for_overtime',
                'enable_notifications',
                'notification_channels'
            ]));
            
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

    /**
     * Manually update attendance record
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAttendanceRecord(Request $request, $id)
    {
        try {
            $record = \Modules\Attendance\Models\AttendanceRecord::findOrFail($id);
            
            $record->fill($request->only([
                'clock_in_time',
                'clock_out_time',
                'is_late',
                'late_minutes',
                'is_early_departure',
                'early_departure_minutes',
                'status',
                'notes'
            ]));
            
            // Recalculate work hours if clock times have changed
            if ($request->has('clock_in_time') || $request->has('clock_out_time')) {
                $record->calculateWorkHours();
                $record->calculateOvertime();
            }
            
            $record->save();
            
            return response()->json([
                'message' => 'Attendance record updated successfully',
                'data' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update attendance record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually allocate leave days to users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function allocateLeave(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2000|max:2100',
            'days' => 'required|numeric|min:0.5',
            'reason' => 'nullable|string|max:255'
        ]);
        
        try {
            $leaveBalance = LeaveBalance::getOrCreate(
                $data['user_id'],
                $data['leave_type_id'],
                $data['year']
            );
            
            // Add to the allocation
            $leaveBalance->allocated_days += $data['days'];
            $leaveBalance->updated_by = Auth::id();
            $leaveBalance->last_updated_reason = $data['reason'] ?? 'Manual allocation by admin';
            $leaveBalance->save();
            
            return response()->json([
                'message' => 'Leave days allocated successfully',
                'data' => $leaveBalance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to allocate leave days: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process year-end leave carryover
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processCarryover(Request $request)
    {
        $data = $request->validate([
            'from_year' => 'required|integer|min:2000|max:2100',
            'to_year' => 'required|integer|min:2000|max:2100',
            'user_id' => 'nullable|exists:users,id'
        ]);
        
        try {
            $fromYear = $data['from_year'];
            $toYear = $data['to_year'];
            
            if ($toYear <= $fromYear) {
                return response()->json([
                    'message' => 'To year must be greater than from year'
                ], 400);
            }
            
            // Get users to process
            $users = [];
            if (isset($data['user_id'])) {
                $users = User::where('id', $data['user_id'])->get();
            } else {
                $users = User::all();
            }
            
            $results = [];
            
            foreach ($users as $user) {
                // Get leave types that allow carryover
                $leaveTypes = LeaveType::where('allow_carryover', true)->get();
                
                foreach ($leaveTypes as $leaveType) {
                    // Get from balance
                    $fromBalance = LeaveBalance::where('user_id', $user->id)
                        ->where('leave_type_id', $leaveType->id)
                        ->where('year', $fromYear)
                        ->first();
                    
                    if (!$fromBalance) {
                        continue; // No balance to carry over
                    }
                    
                    // Calculate carryover days based on remaining days and max allowed
                    $remainingDays = $fromBalance->remaining_days;
                    $maxCarryoverDays = $leaveType->max_carryover_days;
                    $carryoverDays = min($remainingDays, $maxCarryoverDays);
                    
                    if ($carryoverDays <= 0) {
                        continue; // Nothing to carry over
                    }
                    
                    // Get or create target year balance
                    $toBalance = LeaveBalance::getOrCreate($user->id, $leaveType->id, $toYear);
                    
                    // Add carryover days
                    $toBalance->carryover_days += $carryoverDays;
                    $toBalance->updated_by = Auth::id();
                    $toBalance->last_updated_reason = "Automatic carryover from {$fromYear}";
                    $toBalance->save();
                    
                    // Update from balance to mark days as expired
                    $fromBalance->expired_days += ($remainingDays - $carryoverDays);
                    $fromBalance->save();
                    
                    $results[] = [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name
                        ],
                        'leave_type' => [
                            'id' => $leaveType->id,
                            'name' => $leaveType->name
                        ],
                        'from_year' => $fromYear,
                        'to_year' => $toYear,
                        'carried_over_days' => $carryoverDays,
                        'expired_days' => ($remainingDays - $carryoverDays)
                    ];
                }
            }
            
            return response()->json([
                'message' => 'Leave carryover processed successfully',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process leave carryover: ' . $e->getMessage()
            ], 500);
        }
    }
}
