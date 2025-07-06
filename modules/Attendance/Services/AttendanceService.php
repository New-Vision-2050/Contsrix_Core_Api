<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceBreak;
use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\DTO\ClockOutDTO;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AttendanceService
{
    public function __construct(
        private AttendanceRepository $attendanceRepository,
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
            'status' => 'active',
            'timezone' => getTimeZoneByRequest()  ?? config('app.timezone'),
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

        $this->attendanceRepository->update($attendance->id, $updateData);
        $attendance->refresh();
        // Calculate and save work hours
        $attendance->calculateWorkHours();

        return $attendance->refresh();
    }

    /**
     * Start a break for the current attendance record.
     *
     * @param UuidInterface|string $userId User ID
     * @param string|null $notes Optional notes
     * @return Attendance
     * @throws AttendanceException
     */
    public function startBreak(UuidInterface|string $userId, ?string $notes = null): Attendance
    {
        if (is_string($userId)) {
            $userId = Uuid::fromString($userId);
        }

        $attendance = $this->attendanceRepository->getCurrentAttendance($userId);

        if (!$attendance) {
            throw AttendanceException::notClockedIn();
        }

        if ($attendance->isOnBreak()) {
            throw AttendanceException::alreadyOnBreak();
        }

        // Create a new break record
        $break = new AttendanceBreak([
            'attendance_id' => $attendance->id,
            'company_id' => $attendance->company_id,
            'start_time' => now(),
            'notes' => $notes
        ]);
        $break->save();

        // Update attendance notes if provided
        $updateData = [];
        if ($notes) {
            $updateData['notes'] = $attendance->notes . "\nBreak started: " . $notes;
        }

        // Only update if we have data to update
        if (!empty($updateData)) {
            return $this->attendanceRepository->updateAttendance(Uuid::fromString($attendance->id), $updateData);
        }

        return $attendance->refresh();
    }

    /**
     * End the current break for an attendance record.
     *
     * @param UuidInterface|string $userId User ID
     * @param string|null $notes Optional notes
     * @return Attendance
     * @throws AttendanceException
     */
    public function endBreak(UuidInterface|string $userId, ?string $notes = null): Attendance
    {
        if (is_string($userId)) {
            $userId = Uuid::fromString($userId);
        }

        $attendance = $this->attendanceRepository->getCurrentAttendance($userId);

        if (!$attendance) {
            throw AttendanceException::notClockedIn();
        }

        if (!$attendance->isOnBreak()) {
            throw AttendanceException::notOnBreak();
        }

        // Find and update the active break
        $activeBreak = $attendance->activeBreak();
        if ($activeBreak) {
            $activeBreak->end_time = now();
            $activeBreak->calculateDuration();
            if ($notes) {
                $activeBreak->notes = ($activeBreak->notes ? $activeBreak->notes . "\n" : '') . "End: " . $notes;
            }
            $activeBreak->save();

            // Update total break hours in attendance record
            $attendance->updateTotalBreakHours();
        }

        // Update attendance notes if provided
        $updateData = [
            'total_break_hours' => $attendance->total_break_hours,
        ];

        if ($notes) {
            $updateData['notes'] = $attendance->notes . "\nBreak ended: " . $notes;
        }

        return $this->attendanceRepository->updateAttendance(Uuid::fromString($attendance->id), $updateData);
    }

    /**
     * Get current attendance for user
     */
    public function getCurrentAttendance(UuidInterface $userId): ?Attendance
    {
        return $this->attendanceRepository->getCurrentAttendance($userId);
    }

    /**
     * Get attendance by ID
     */
    public function getAttendance(UuidInterface $attendanceId): ?Attendance
    {
        return $this->attendanceRepository->getAttendance($attendanceId);
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

        // Update the attendance record
        $attendance = $this->attendanceRepository->updateAttendance($uuid, $data);

        // Recalculate work hours if clock times were updated
        if (isset($data['clock_in_time']) || isset($data['clock_out_time'])) {
            $attendance->updateTotalBreakHours();
            $attendance->calculateWorkHours();
            $attendance->save();
        }

        return $attendance;
    }

    /**
     * Approve attendance record
     */
    public function approveAttendance(UuidInterface $attendanceId, UuidInterface $approvedBy, ?string $notes = null): Attendance
    {
        $uuid = $attendanceId;
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

        // Update the attendance record
        $attendance = $this->attendanceRepository->updateAttendance($uuid, $data);

        // Recalculate work hours after approval
        $attendance->updateTotalBreakHours();
        $attendance->calculateWorkHours();
        $attendance->save();

        return $attendance;
    }

    /**
     * Reject attendance record
     */
    public function rejectAttendance(UuidInterface $attendanceId, UuidInterface $rejectedBy, string $reason): Attendance
    {
        $uuid = $attendanceId;
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
    public function getTeamAttendance(string $supervisorId, array $filters, ?int $page = 1, ?int $perPage = 10): array
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

        // Validate clock out time
        $clockOutTime = Carbon::now();
        $clockInTime = Carbon::parse($attendance->clock_in_time);

        if ($clockOutTime->lt($clockInTime)) {
            throw AttendanceException::invalidClockOutTime();
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

        // Calculate break hours first
        if ($attendance) {
            $attendance->updateTotalBreakHours();
            // Calculate work hours after ending the shift
            $attendance->calculateWorkHours();
            $attendance->save();
        }

        return $attendance;
    }

    /**
     * Get all breaks for a specific attendance record.
     *
     * @param UuidInterface|string $attendanceId Attendance ID
     * @return array
     */
    public function getBreaks(UuidInterface|string $attendanceId): array
    {
        if (is_string($attendanceId)) {
            $attendanceId = Uuid::fromString($attendanceId);
        }

        $attendance = $this->attendanceRepository->getAttendanceById($attendanceId);

        if (!$attendance) {
            return [];
        }

        $breaks = [];
        foreach ($attendance->breaks as $break) {
            $breaks[] = [
                'id' => (string)$break->id,
                'start_time' => $break->start_time?->format('Y-m-d H:i:s'),
                'end_time' => $break->end_time?->format('Y-m-d H:i:s'),
                'duration_minutes' => $break->duration_minutes,
                'duration_formatted' => $break->getFormattedDuration(),
                'notes' => $break->notes,
                'is_active' => $break->isActive(),
            ];
        }

        return $breaks;
    }
}
