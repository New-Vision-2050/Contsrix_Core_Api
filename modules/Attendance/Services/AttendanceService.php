<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSetting;
use Modules\Attendance\Models\BreakRecord;
use Modules\User\Models\User;

class AttendanceService
{
    /**
     * Get active attendance record for a user
     *
     * @param int $userId
     * @return AttendanceRecord|null
     */
    public function getActiveAttendanceRecord($userId)
    {
        $today = Carbon::today();
        return AttendanceRecord::where('user_id', $userId)
            ->whereDate('date', $today)
            ->where('status', '!=', 'completed')
            ->first();
    }

    /**
     * Get today's attendance record for a user
     *
     * @param int $userId
     * @return AttendanceRecord|null
     */
    public function getTodayAttendance($userId)
    {
        $today = Carbon::today();
        return AttendanceRecord::where('user_id', $userId)
            ->whereDate('date', $today)
            ->first();
    }

    /**
     * Get a user's attendance history
     *
     * @param int $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAttendanceHistory($userId, $startDate = null, $endDate = null, $perPage = 15)
    {
        $query = AttendanceRecord::where('user_id', $userId);
        
        if ($startDate) {
            $query->whereDate('date', '>=', Carbon::parse($startDate));
        }
        
        if ($endDate) {
            $query->whereDate('date', '<=', Carbon::parse($endDate));
        }
        
        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

    /**
     * Get active break for an attendance record
     *
     * @param int $attendanceRecordId
     * @return BreakRecord|null
     */
    public function getActiveBreak($attendanceRecordId)
    {
        return BreakRecord::where('attendance_record_id', $attendanceRecordId)
            ->whereNull('end_time')
            ->first();
    }

    /**
     * Get total number of breaks for an attendance record
     *
     * @param int $attendanceRecordId
     * @return int
     */
    public function getBreakCount($attendanceRecordId)
    {
        return BreakRecord::where('attendance_record_id', $attendanceRecordId)->count();
    }

    /**
     * Clock in a user
     *
     * @param int $userId
     * @param array $data
     * @return AttendanceRecord
     */
    public function clockIn($userId, array $data)
    {
        $now = Carbon::now();
        $settings = AttendanceSetting::getSettings();
        
        // Get user's scheduled working hours from settings or defaults
        $workStartTime = $settings ? Carbon::parse($settings->work_start_time)->format('H:i:s') : '09:00:00';
        
        // Check if late or on time
        $isLate = $now->format('H:i:s') > $workStartTime;
        $lateDuration = $isLate ? $now->diffInMinutes(Carbon::parse($workStartTime)) : 0;
        
        // Create the attendance record
        $record = new AttendanceRecord();
        $record->user_id = $userId;
        $record->date = $now->format('Y-m-d');
        $record->clock_in_time = $now->format('H:i:s');
        $record->status = 'active';
        $record->is_late = $isLate;
        $record->late_minutes = $lateDuration;
        
        // Add location data if provided
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $record->clock_in_latitude = $data['latitude'];
            $record->clock_in_longitude = $data['longitude'];
        }
        
        // Add device info if provided
        if (isset($data['device_info'])) {
            $record->clock_in_device_info = $data['device_info'];
        }
        
        if (isset($data['ip_address'])) {
            $record->clock_in_ip = $data['ip_address'];
        }
        
        // Add notes if provided
        if (isset($data['notes'])) {
            $record->notes = $data['notes'];
        }
        
        $record->save();
        
        return $record;
    }

    /**
     * Clock out a user
     *
     * @param int $attendanceRecordId
     * @param array $data
     * @return AttendanceRecord
     */
    public function clockOut($attendanceRecordId, array $data)
    {
        $now = Carbon::now();
        $settings = AttendanceSetting::getSettings();
        
        // Get user's scheduled end time
        $workEndTime = $settings ? Carbon::parse($settings->work_end_time)->format('H:i:s') : '17:00:00';
        
        // Check if early departure
        $isEarly = $now->format('H:i:s') < $workEndTime;
        $earlyDuration = $isEarly ? Carbon::parse($workEndTime)->diffInMinutes($now) : 0;
        
        $record = AttendanceRecord::findOrFail($attendanceRecordId);
        $record->clock_out_time = $now->format('H:i:s');
        $record->status = 'completed';
        $record->is_early_departure = $isEarly;
        $record->early_departure_minutes = $earlyDuration;
        
        // Calculate total work hours
        $record->calculateWorkHours();
        $record->calculateOvertime();
        
        // Add location data if provided
        if (isset($data['latitude']) && isset($data['longitude'])) {
            $record->clock_out_latitude = $data['latitude'];
            $record->clock_out_longitude = $data['longitude'];
        }
        
        // Add device info if provided
        if (isset($data['device_info'])) {
            $record->clock_out_device_info = $data['device_info'];
        }
        
        if (isset($data['ip_address'])) {
            $record->clock_out_ip = $data['ip_address'];
        }
        
        // Append notes if provided
        if (isset($data['notes'])) {
            $record->notes = $record->notes ? $record->notes . "\n" . $data['notes'] : $data['notes'];
        }
        
        $record->save();
        
        return $record;
    }

    /**
     * Start a break
     *
     * @param int $attendanceRecordId
     * @param array $data
     * @return BreakRecord
     */
    public function startBreak($attendanceRecordId, array $data)
    {
        $now = Carbon::now();
        
        $break = new BreakRecord();
        $break->attendance_record_id = $attendanceRecordId;
        $break->start_time = $now->format('H:i:s');
        $break->break_type = $data['break_type'] ?? 'lunch';
        $break->notes = $data['notes'] ?? null;
        $break->save();
        
        // Update the attendance record status
        $record = AttendanceRecord::findOrFail($attendanceRecordId);
        $record->status = 'on_break';
        $record->save();
        
        return $break;
    }

    /**
     * End a break
     *
     * @param int $breakId
     * @return BreakRecord
     */
    public function endBreak($breakId)
    {
        $now = Carbon::now();
        
        $break = BreakRecord::findOrFail($breakId);
        $break->end_time = $now->format('H:i:s');
        $break->duration_minutes = $break->calculateDurationMinutes();
        $break->save();
        
        // Update the attendance record status
        $record = AttendanceRecord::findOrFail($break->attendance_record_id);
        $record->status = 'active';
        $record->save();
        
        return $break;
    }

    /**
     * Get user's current attendance status
     *
     * @param int $userId
     * @return array
     */
    public function getUserAttendanceStatus($userId)
    {
        $today = Carbon::today();
        $record = AttendanceRecord::where('user_id', $userId)
            ->whereDate('date', $today)
            ->first();
        
        if (!$record) {
            return [
                'status' => 'not_clocked_in',
                'message' => 'Not clocked in today',
                'record' => null,
                'active_break' => null,
                'break_history' => []
            ];
        }
        
        $activeBreak = null;
        if ($record->status === 'on_break') {
            $activeBreak = BreakRecord::where('attendance_record_id', $record->id)
                ->whereNull('end_time')
                ->first();
        }
        
        $breakHistory = BreakRecord::where('attendance_record_id', $record->id)
            ->whereNotNull('end_time')
            ->get();
        
        return [
            'status' => $record->status,
            'record' => $record,
            'active_break' => $activeBreak,
            'break_history' => $breakHistory
        ];
    }

    /**
     * Get attendance summary for a specific period
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAttendanceSummary($userId, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        
        $records = AttendanceRecord::where('user_id', $userId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();
        
        $totalWorkHours = 0;
        $totalOvertimeHours = 0;
        $totalLateMinutes = 0;
        $totalEarlyMinutes = 0;
        $totalBreakMinutes = 0;
        $presentDays = 0;
        $absentDays = 0;
        $lateDays = 0;
        
        foreach ($records as $record) {
            $totalWorkHours += $record->total_work_hours;
            $totalOvertimeHours += $record->overtime_hours;
            $totalLateMinutes += $record->late_minutes;
            $totalEarlyMinutes += $record->early_departure_minutes;
            
            if ($record->status === 'completed') {
                $presentDays++;
            }
            
            if ($record->is_late) {
                $lateDays++;
            }
            
            // Calculate break minutes
            foreach ($record->breakRecords as $break) {
                $totalBreakMinutes += $break->duration_minutes;
            }
        }
        
        // Calculate absent days (excluding weekends)
        $workingDays = $this->calculateWorkingDays($startDate, $endDate);
        $absentDays = $workingDays - $presentDays;
        
        return [
            'total_work_hours' => $totalWorkHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'total_late_minutes' => $totalLateMinutes,
            'total_early_minutes' => $totalEarlyMinutes,
            'total_break_minutes' => $totalBreakMinutes,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'working_days' => $workingDays
        ];
    }
    
    /**
     * Calculate the number of working days between two dates
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    private function calculateWorkingDays(Carbon $startDate, Carbon $endDate)
    {
        $settings = AttendanceSetting::getSettings();
        $weekends = $settings ? json_decode($settings->weekend_days, true) : [0, 6]; // Default weekend is Saturday and Sunday
        
        $workingDays = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            if (!in_array($currentDate->dayOfWeek, $weekends)) {
                $workingDays++;
            }
            $currentDate->addDay();
        }
        
        return $workingDays;
    }
}
