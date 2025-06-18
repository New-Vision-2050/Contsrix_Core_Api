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

        return $this->attendanceRepository->update($attendance->id, $updateData);
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

        return $this->attendanceRepository->update($attendance->id, $updateData);
    }

    /**
     * Get current attendance for user
     */
    public function getCurrentAttendance(string $userId): ?Attendance
    {
        return $this->attendanceRepository->getCurrentAttendance($userId);
    }

    /**
     * Get attendance history
     */
    public function getAttendanceHistory(array $filters): Collection
    {
        return $this->attendanceRepository->getAttendanceHistory($filters);
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
        $attendance = $this->attendanceRepository->findById($attendanceId);
        
        if (!$attendance) {
            throw AttendanceException::attendanceNotFound();
        }

        // Check if attendance is from previous days and prevent modification
        if (Carbon::parse($attendance->clock_in_time)->isYesterday() || Carbon::parse($attendance->clock_in_time)->isPast()) {
            if (!Carbon::parse($attendance->clock_in_time)->isToday()) {
                throw AttendanceException::cannotModifyPastAttendance();
            }
        }

        return $this->attendanceRepository->update($attendanceId, $data);
    }

    /**
     * Approve attendance record
     */
    public function approveAttendance(string $attendanceId, string $approvedBy, ?string $notes = null): Attendance
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        
        if (!$attendance) {
            throw AttendanceException::attendanceNotFound();
        }

        if ($attendance->status === 'approved') {
            throw AttendanceException::attendanceAlreadyApproved();
        }

        $updateData = [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'notes' => $attendance->notes . ($notes ? "\nApproval notes: " . $notes : '')
        ];

        return $this->attendanceRepository->update($attendanceId, $updateData);
    }

    /**
     * Reject attendance record
     */
    public function rejectAttendance(string $attendanceId, string $rejectedBy, string $reason): Attendance
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        
        if (!$attendance) {
            throw AttendanceException::attendanceNotFound();
        }

        $updateData = [
            'status' => 'rejected',
            'notes' => $attendance->notes . "\nRejected by: " . $rejectedBy . "\nReason: " . $reason
        ];

        return $this->attendanceRepository->update($attendanceId, $updateData);
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendance(string $attendanceId): bool
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        
        if (!$attendance) {
            throw AttendanceException::attendanceNotFound();
        }

        if ($attendance->status === 'approved') {
            throw AttendanceException::cannotDeleteApprovedAttendance();
        }

        return $this->attendanceRepository->delete($attendanceId);
    }

    /**
     * Get team attendance for supervisors
     */
    public function getTeamAttendance(string $supervisorId, array $filters): Collection
    {
        // This would typically involve getting team members under the supervisor
        // For now, we'll return all attendance records with filters
        return $this->attendanceRepository->getAttendanceHistory($filters);
    }
}
