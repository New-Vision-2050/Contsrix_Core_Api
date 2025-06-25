<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\DTO\ClockOutDTO;
use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Exceptions\AttendanceException;
use Ramsey\Uuid\Uuid;

class AttendanceService
{
    public function __construct(
        private AttendanceRepository $attendanceRepository
    ) {}

    /**
     * Clock in employee
     */
    public function clockIn(ClockInDTO $clockInDTO): Attendance
    {
        // Check if user is already clocked in
        $existingAttendance = $this->attendanceRepository->getCurrentAttendance($clockInDTO->getUserId());
        
        if ($existingAttendance && !$existingAttendance->clock_out_time) {
            throw AttendanceException::alreadyClockedIn();
        }

        // Create new attendance record
        $attendanceData = [
            'user_id' => $clockInDTO->getUserId(),
            'company_id' => $clockInDTO->getCompanyId(),
            'clock_in_time' => $clockInDTO->getClockInTime(),
            'clock_in_location' => $clockInDTO->getLocation(),
            'notes' => $clockInDTO->getNotes(),
            'ip_address' => $clockInDTO->getIpAddress(),
            'user_agent' => $clockInDTO->getUserAgent(),
            'status' => 'active'
        ];

        return $this->attendanceRepository->create($attendanceData);
    }

    /**
     * Clock out employee
     */
    public function clockOut(ClockOutDTO $clockOutDTO): Attendance
    {
        // Get current attendance
        $attendance = $this->attendanceRepository->getCurrentAttendance($clockOutDTO->getUserId());
        
        if (!$attendance) {
            throw AttendanceException::notClockedIn();
        }

        if ($attendance->clock_out_time) {
            throw AttendanceException::alreadyClockedOut();
        }

        // Validate clock out time
        $clockOutTime = Carbon::parse($clockOutDTO->getClockOutTime());
        $clockInTime = Carbon::parse($attendance->clock_in_time);
        
        if ($clockOutTime->lt($clockInTime)) {
            throw AttendanceException::invalidClockOutTime();
        }

        // Update attendance record
        $updateData = [
            'clock_out_time' => $clockOutDTO->getClockOutTime(),
            'clock_out_location' => $clockOutDTO->getLocation(),
            'notes' => $attendance->notes . ($clockOutDTO->getNotes() ? "\n" . $clockOutDTO->getNotes() : ''),
            'status' => 'completed'
        ];

        return $this->attendanceRepository->update($attendance->id, $updateData);
    }

    /**
     * Start break
     */
    public function startBreak(string $userId, ?string $notes = null): Attendance
    {
        $attendance = $this->attendanceRepository->getCurrentAttendance($userId);
        
        if (!$attendance) {
            throw AttendanceException::notClockedIn();
        }

        if ($attendance->break_start_time && !$attendance->break_end_time) {
            throw AttendanceException::onBreak();
        }

        $updateData = [
            'break_start_time' => now(),
            'notes' => $attendance->notes . ($notes ? "\nBreak started: " . $notes : '')
        ];

        return $this->attendanceRepository->updateAttendance(Uuid::fromString($attendance->id), $updateData);
    }

    /**
     * End break
     */
    public function endBreak(string $userId, ?string $notes = null): Attendance
    {
        $attendance = $this->attendanceRepository->getCurrentAttendance($userId);
        
        if (!$attendance) {
            throw AttendanceException::notClockedIn();
        }

        if (!$attendance->break_start_time || $attendance->break_end_time) {
            throw AttendanceException::notOnBreak();
        }

        $updateData = [
            'break_end_time' => now(),
            'notes' => $attendance->notes . ($notes ? "\nBreak ended: " . $notes : '')
        ];

        return $this->attendanceRepository->updateAttendance(Uuid::fromString($attendance->id), $updateData);
    }

    /**
     * Get current attendance for user
     */
    public function getCurrentAttendance(string $userId): ?Attendance
    {
        return $this->attendanceRepository->getCurrentAttendance($userId);
    }

    /**
     * Get attendance history with filtering and pagination
     */
    public function getAttendanceHistory(array $filters, ?int $page = null, ?int $perPage = 10): array
    {
        return $this->attendanceRepository->getAttendanceHistory($filters, $page, $perPage);
    }

    /**
     * Get attendance list with filtering and pagination
     */
    public function getAttendanceList(array $filters, ?int $page = null, ?int $perPage = 10): array
    {
        return $this->attendanceRepository->getAttendanceList($filters, $page, $perPage);
    }

    /**
     * Get attendance summary
     */
    public function getAttendanceSummary(string $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        $attendances = $this->attendanceRepository->getAttendanceByDateRange($userId, $startDate, $endDate);

        $summary = [
            'total_days' => $attendances->count(),
            'total_work_hours' => $attendances->sum('total_work_hours'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'total_break_hours' => $attendances->sum('total_break_hours'),
            'late_days' => $attendances->where('is_late', true)->count(),
            'early_departures' => $attendances->where('is_early_departure', true)->count(),
            'average_work_hours' => $attendances->count() > 0 ? $attendances->avg('total_work_hours') : 0,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]
        ];

        return $summary;
    }

    /**
     * Update attendance record
     */
    public function updateAttendance(string $attendanceId, array $data): Attendance
    {
        $uuid = Uuid::fromString($attendanceId);
        $attendance = $this->attendanceRepository->getAttendance($uuid);
        
        // Check if attendance is from previous days and prevent modification
        if (Carbon::parse($attendance->clock_in_time)->isYesterday() || Carbon::parse($attendance->clock_in_time)->isPast()) {
            if (!Carbon::parse($attendance->clock_in_time)->isToday()) {
                throw AttendanceException::cannotModifyPastAttendance();
            }
        }

        return $this->attendanceRepository->updateAttendance($uuid, $data);
    }

    /**
     * Approve attendance record
     */
    public function approveAttendance(string $attendanceId, string $approvedBy, ?string $notes = null): Attendance
    {
        $uuid = Uuid::fromString($attendanceId);
        $attendance = $this->attendanceRepository->getAttendance($uuid);
        
        if ($attendance->status === 'approved') {
            throw AttendanceException::attendanceAlreadyApproved();
        }

        $data = [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ];

        return $this->attendanceRepository->updateAttendance($uuid, $data);
    }

    /**
     * Reject attendance record
     */
    public function rejectAttendance(string $attendanceId, string $rejectedBy, string $reason): Attendance
    {
        $uuid = Uuid::fromString($attendanceId);
        $attendance = $this->attendanceRepository->getAttendance($uuid);
        
        if ($attendance->status === 'approved') {
            throw AttendanceException::cannotRejectApprovedAttendance();
        }

        $data = [
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'approval_notes' => $reason,
        ];

        return $this->attendanceRepository->updateAttendance($uuid, $data);
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance(string $attendanceId): bool
    {
        $uuid = Uuid::fromString($attendanceId);
        $attendance = $this->attendanceRepository->getAttendance($uuid);
        
        if ($attendance->status === 'approved') {
            throw AttendanceException::cannotDeleteApprovedAttendance();
        }

        return $this->attendanceRepository->deleteAttendance($uuid);
    }

    /**
     * Get team attendance with filtering and pagination (for supervisors)
     */
    public function getTeamAttendance(string $supervisorId, array $filters, ?int $page = null, ?int $perPage = 10): array
    {
        // Add supervisor's team filter logic here if needed
        // For now, just use the filters as provided
        return $this->attendanceRepository->getAttendanceHistory($filters, $page, $perPage);
    }

    /**
     * Get late arrivals with filtering and pagination
     */
    public function getLateArrivals(array $filters, ?int $page = null, ?int $perPage = 10): array
    {
        return $this->attendanceRepository->getLateArrivals($filters, $page, $perPage);
    }

    /**
     * Get early departures with filtering and pagination
     */
    public function getEarlyDepartures(array $filters, ?int $page = null, ?int $perPage = 10): array
    {
        return $this->attendanceRepository->getEarlyDepartures($filters, $page, $perPage);
    }

    /**
     * Get overtime records with filtering and pagination
     */
    public function getOvertimeRecords(array $filters, ?int $page = null, ?int $perPage = 10): array
    {
        return $this->attendanceRepository->getOvertimeRecords($filters, $page, $perPage);
    }

    /**
     * End shift automatically based on constraint enforcement
     *
     * @param string $attendanceId ID of the attendance record to end
     * @param string $method The method used to end the shift (e.g., 'auto_radius_enforcement', 'auto_time_limit')
     * @param string $notes Additional notes about why the shift was ended
     * @param bool $markAbsent Whether to mark the day as absent in attendance records
     * @return Attendance|bool The updated attendance record or false if the operation failed
     */
    public function endShiftAutomatically(string $attendanceId, string $method, string $notes, bool $markAbsent = false): Attendance|bool
    {
        $uuid = Uuid::fromString($attendanceId);
        $attendance = $this->attendanceRepository->getAttendance($uuid);
        
        if (!$attendance || !$attendance->isActive()) {
            return false; // Cannot end an inactive or already completed shift
        }
        
        // Set clock out time to current time
        $timestamp = Carbon::now();
        $updateData = [
            'clock_out_time' => $timestamp,
            'status' => Attendance::STATUS_COMPLETED,
            'shift_end_method' => $method,
            'notes' => ($attendance->notes ? $attendance->notes . "\n\n" : '') . 
                      "[{$timestamp->format('Y-m-d H:i:s')}] Auto-ended: {$notes}"
        ];
        
        // If configured to mark day as absent
        if ($markAbsent) {
            $updateData['is_absent'] = true;
            $updateData['absence_reason'] = "Automatically marked absent due to constraint violation: {$method}";
        }
        
        // Update the attendance record
        $attendance = $this->attendanceRepository->updateAttendance($uuid, $updateData);
        
        // Calculate work hours after ending the shift
        if ($attendance) {
            $attendance->calculateWorkHours();
            $attendance->save();
        }
        
        return $attendance;
    }
}
