<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceBreak;
use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\DTO\ClockInDTO;
use Modules\Attendance\DTO\ClockOutDTO;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Carbon\CarbonPeriod;

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
        $existingAttendance = $this->attendanceRepository->getCurrentAttendance($clockInDTO->getUserId());

        if ($existingAttendance && !$existingAttendance->clock_out_time) {
            throw AttendanceException::alreadyClockedIn();
        }

        $user = User::find(auth()->user()->id);
        $constraintService = app(AttendanceConstraintService::class);
        $constraints = $constraintService->getTodaysWorkRulesForUser($user);
        $timezone = getTimeZoneByRequest()?? config('app.timezone');
        $date = Carbon::now()->format('Y-m-d');


        $periodStartTime = data_get($constraints, 'current_work_period.start_time');
        $periodEndTime = data_get($constraints, 'current_work_period.end_time');
        $day_status = 'in_loction';


        $startDateTime = Carbon::parse($date . ' ' . $periodStartTime);
        $endDateTime = Carbon::parse($date . ' ' . $periodEndTime);

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $periodStartTime, $timezone);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $periodEndTime, timezone: $timezone);

        if ($startDateTime->gt($endDateTime)) {
            $endDateTime->addDay();
        }

        $earlyClockInRules = data_get($constraints, 'early_clock_in_rules');

        if ($earlyClockInRules && ($earlyClockInRules['prevent_early_clock_in'] ?? false)) {
            $earlyPeriod = $earlyClockInRules['early_period'] ?? 0;
            $earlyUnit = $earlyClockInRules['early_unit'] ?? 'minutes';

            $now = Carbon::now($timezone);

            $earliestAllowedTime = $startDateTime->copy()->sub($earlyPeriod, $earlyUnit);

            if ($now->lt($earliestAllowedTime)) {

                throw new \Exception("غير مسموح بتسجيل الحضور قبل {$earlyPeriod} {$earlyUnit} من بداية الفترة.");
            }
        }


        if ($startDateTime->gt($endDateTime)) {
            $endDateTime->addDay();
        }


        // if (!$periodStartTime || !$periodEndTime) {
        //     throw new \Exception('لا يوجد فترة عمل حالية current_work_period لهذا المستخدم اليوم.');
        // }

        $attendanceData = [
            'user_id' => $clockInDTO->getUserId(),
            'company_id' => $clockInDTO->getCompanyId(),
            'clock_in_time' => $clockInDTO->getClockInTime(),
            'clock_in_location' => $clockInDTO->getLocation(),
            'start_time' => $startDateTime,
            'end_time' => $endDateTime,
            'notes' => $clockInDTO->getNotes(),
            'ip_address' => $clockInDTO->getIpAddress(),
            'user_agent' => $clockInDTO->getUserAgent(),
            'status' => 'active',
            'day_status' => $day_status,
            'timezone' => getTimeZoneByRequest() ?? config('app.timezone'),
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
        $attendance = $this->attendanceRepository->getAttendanceHistory($filters, $page, $perPage);

        // Check if we have data and it's already grouped
        if (isset($attendance['data']) && !empty($attendance['data'])) {
            return $attendance;
        } else {
            // If no data and user_id is provided, create synthetic attendance
            if (empty($filters['user_id'])) {
                return $attendance;
            }

            $syntheticAttendance = new Attendance([
                'user_id' => $filters['user_id'],
                'status' => Attendance::STATUS_COMPLETED,
                'is_absent' => true,
                'id' => Uuid::uuid4(),
                // Add start_time and end_time for proper grouping
                'start_time' => Carbon::now()->format('Y-m-d H:i:s'),
                'end_time' => null
            ]);

            $user = User::with(['professionalData', 'userProfessionalData'])->find($filters['user_id']);
            $syntheticAttendance->setRelation('user', $user);
            $syntheticAttendance->setRelation('breaks', new Collection());
            $syntheticAttendance->attendance_periods = [];

            $finalAttendanceList = collect([$syntheticAttendance]);

            // Group the synthetic attendance by date
            $groupedData = $finalAttendanceList->groupBy(function ($item) {
                $startDate = $item->start_time ? date('Y-m-d H:i', strtotime($item->start_time)) : null;
                return $startDate . ' - ' . $item->end_time ;
            });

            return [
                'data' => $groupedData,
                'pagination' => null
            ];
        }
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
// In AttendanceService.php

    public function getAttendanceSummary(UuidInterface $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        // 1. Get all attendance records for the period.
        $attendances = $this->attendanceRepository->getAttendanceByDateRange($userId, $startDate, $endDate);

        // 2. Calculate the base total for percentages.
        // We will use the total number of records in the given date range as the base "100%".
        $totalRecords = $attendances->count();

        // It's more efficient to calculate these once and store them in variables.
        $totalAttendant = $attendances->whereNotNull('clock_in_time')->count();
        $totalAbsent = $attendances->where('is_absent', true)->count();
        $totalHoliday = $attendances->where('is_holiday', true)->count();
        $totalDepartures = $attendances->whereNotNull('clock_out_time')->count();
        $totalLate = $attendances->where('is_late', true)->count();
        $totalEarly = $attendances->where('is_early_departure', true)->count();

        // 3. Build the summary array.
        $summary = [
            'total_days' => $totalRecords,

            'total_attendant' => $totalAttendant,
            'total_attendant_percentage' => $this->calculatePercentage($totalAttendant, $totalRecords),

            'total_absent_days' => $totalAbsent,
            'total_absent_days_percentage' => $this->calculatePercentage($totalAbsent, $totalRecords),

            'total_holiday_days' => $totalHoliday,
            'total_holiday_days_percentage' => $this->calculatePercentage($totalHoliday, $totalRecords),

            'total_departures' => $totalDepartures,
            'total_departures_percentage' => $this->calculatePercentage($totalDepartures, $totalRecords),

            // These are percentages of the days the user was actually present.
            'late_days' => $totalLate,
            'late_days_percentage' => $this->calculatePercentage($totalLate, $totalAttendant),

            'early_departures' => $totalEarly,
            'early_departures_percentage' => $this->calculatePercentage($totalEarly, $totalDepartures),

            // --- Hour Summaries (no percentage needed here) ---
            'total_work_hours' => round($attendances->sum('total_work_hours'), 2),
            'total_overtime_hours' => round($attendances->sum('overtime_hours'), 2),
            'total_break_hours' => round($attendances->sum('total_break_hours'), 2),
            'average_work_hours' => $totalAttendant > 0 ? round($attendances->avg('total_work_hours'), 2) : 0,

            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]
        ];

        return $summary;
    }
   /**
     * Helper function to safely calculate a percentage.
     * @param int|float $part The value for which to calculate the percentage.
     * @param int|float $total The total value to compare against.
     * @return float The calculated percentage, rounded to 2 decimal places.
     */
    private function calculatePercentage(int|float $part, int|float $total): float
    {
        if ($total == 0) {
            return 0.0;
        }
        return round(($part / $total) * 100, 2);
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


    public function getTeamAttendance(string $requestingUserId, array $filters, ?int $page = 1, ?int $perPage = 10): array
    {
        $companyId = $filters['company_id'] ?? null;
        if (!$companyId) {
            throw new \InvalidArgumentException('Company ID is required for team attendance.');
        }

        $timezone = getTimeZoneByRequest() ?? config('app.timezone');
        $defaultTimezone = config('app.timezone');

        // Parse dates once and cache them
        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'], $timezone)->startOfDay()
            : Carbon::today($timezone)->startOfDay();

        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'], $timezone)->endOfDay()
            : Carbon::today($timezone)->endOfDay();

        // Determine the date range upfront
        $period = CarbonPeriod::create($startDate, $endDate);
        $allDates = collect($period->toArray());
        $dateCount = $allDates->count();
        
        // Pagination calculation
        $offset = ($page - 1) * $perPage;
        
        // Only fetch users needed for the current page
        // Optimization 1: Limit the number of users we process
        $query = User::where('company_id', $companyId)
            ->filter($filters)
            ->whereNotIn('email', config('constrix.emails'));
            
        // Get total user count for pagination
        $totalUserCount = $query->count();
        $totalItems = $totalUserCount * $dateCount;
        
        // Calculate which users we need for the current page
        $usersPerPage = max(1, ceil($perPage / $dateCount));
        $userOffset = floor($offset / $dateCount);
        
        // Only fetch users needed for this page
        $allRelevantUsers = $query->with(['professionalData'])
            ->skip($userOffset)
            ->take($usersPerPage + 1) // Fetch one extra to ensure we have enough
            ->get();

        $allRelevantUserIds = $allRelevantUsers->pluck('id')->toArray();

        // Optimization 2: Only select fields we need
        $realAttendanceRecords = Attendance::query()
            ->filter($filters)
            ->select(['id', 'user_id', 'company_id', 'status', 'is_late', 'is_absent', 
                     'is_holiday', 'day_status', 'clock_in_time', 'clock_out_time', 
                     'start_time', 'overtime_hours'])
            ->whereIn('user_id', $allRelevantUserIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get();

        // Optimization 3: More efficient data structuring
        $attendanceByUserAndDate = [];
        foreach ($realAttendanceRecords as $record) {
            $userId = (string)$record->user_id;
            $date = $record->clock_in_time->copy()->setTimezone($timezone)->format('Y-m-d');
            $attendanceByUserAndDate[$userId][$date][] = $record;
        }

        $finalAttendanceList = [];
        $constraintService = app(AttendanceConstraintService::class);
        
        // Optimization 4: Only process what we need for the current page
        $processedItems = 0;
        $targetItems = min($perPage, $totalItems - $offset);
        
        foreach ($allRelevantUsers as $user) {
            if ($processedItems >= $targetItems) break;
            
            $userId = (string)$user->id;
            
            // Optimization 5: Pre-fetch constraints for this user once
            $userConstraints = null;
            
            foreach ($allDates as $date) {
                if ($processedItems >= $targetItems) break;
                
                $dateStr = $date->format('Y-m-d');
                $userRecords = $attendanceByUserAndDate[$userId][$dateStr] ?? [];

                if (!empty($userRecords)) {
                    $primaryRecord = $userRecords[0];

                    $attendancePeriods = [];
                    $isLate = false;

                    foreach ($userRecords as $record) {
                        $attendancePeriods[] = [
                            'id' => (string)$record->id,
                            'clock_in_time' => $record->clock_in_time?->setTimezone($timezone)->format('Y-m-d H:i:s'),
                            'clock_out_time' => $record->clock_out_time?->setTimezone($timezone)->format('Y-m-d H:i:s'),
                            'status' => $record->status,
                            'is_late' => (int)$record->is_late,
                            'day_status' => $record->day_status,
                        ];
                        
                        if ($record->is_late) $isLate = true;
                    }

                    $primaryRecord->is_late = $isLate;
                    $primaryRecord->status = Attendance::STATUS_COMPLETED;
                    $primaryRecord->is_absent = false;
                    $primaryRecord->work_date = $dateStr;
                    $primaryRecord->attendance_periods = $attendancePeriods;
                    $primaryRecord->setRelation('user', $user);

                    $finalAttendanceList[] = $primaryRecord;
                } else {
                    // Optimization 6: Only fetch constraints when needed
                    if ($userConstraints === null) {
                        $userConstraints = $constraintService->getTodaysWorkRulesForUser($user);
                    }
                    
                    $isHoliday = isset($userConstraints['is_holiday']) ? $userConstraints['is_holiday'] : null;
                    
                    $syntheticAttendance = new Attendance([
                        'user_id' => $user->id,
                        'company_id' => $companyId,
                        'status' => Attendance::STATUS_COMPLETED,
                        'is_absent' => $isHoliday === false ? true : false,
                        'is_holiday' => $isHoliday,
                        'day_status' => $isHoliday === true ? 'holiday' : 'work_day',
                        'id' => Uuid::uuid4(),
                        'is_late' => false,
                        'clock_in_time' => $date->copy()->toDateTimeImmutable(),
                        'timezone' => $defaultTimezone,
                        'work_date' => $dateStr,
                    ]);

                    $syntheticAttendance->setRelation('user', $user);
                    $syntheticAttendance->setRelation('breaks', new Collection());
                    $syntheticAttendance->attendance_periods = [];

                    $finalAttendanceList[] = $syntheticAttendance;
                }
                
                $processedItems++;
            }
        }

        // Optimization 7: Simplified sorting
        $finalAttendanceList = collect($finalAttendanceList)->sortBy(function ($record) {
            return $record->work_date . '_' . ($record->user->name ?? '');
        })->values();

        // Calculate pagination values
        $lastPage = max((int) ceil($totalItems / $perPage), 1);
        $currentPage = $page;
        $from = $totalItems ? $offset + 1 : 0;
        $to = min($offset + $perPage, $totalItems);

        return [
            'data' => $finalAttendanceList->all(),
            'pagination' => [
                'total' => $totalItems,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ];
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

        $attendance = $this->attendanceRepository->getAttendance($attendanceId);

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

        public function getAttendanceForUserOnDate(User $user, Carbon $date): ?Attendance
    {
        // Use the repository to find a single record matching the criteria.
        // This is more efficient than getting a collection and checking if it's empty.
        return $this->attendanceRepository->findOneBy([
            ['user_id', '=', $user->id],
            // Check for records created between the start and end of the given day.
            ['created_at', '>=', $date->copy()->startOfDay()],
            ['created_at', '<=', $date->copy()->endOfDay()],
        ]);
    }
    public function getPresentUserIdsOnDate(array $userIds, Carbon $date, ?array $period = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $query = $this->attendanceRepository->getQuery()
            ->whereIn('user_id', $userIds);

        if ($period && isset($period['start_time']) && isset($period['end_time'])) {
            // Check for records within the specific period on the given date
            $startTime = Carbon::parse($date->toDateString() . ' ' . $period['start_time']);
            $endTime = Carbon::parse($date->toDateString() . ' ' . $period['end_time']);
            $query->whereBetween('start_time', [$startTime, $endTime]);
        } else {
            // Fallback to checking the entire day if no period is specified
            $query->whereDate('start_time', $date);
        }

        return $query->pluck('user_id')->all();
    }
    public function createAbsenceRecord(User $user, Carbon $dateOfAbsence, string $reason): Attendance
    {
        // Prepare the data for the new absence record.
        $attendanceData = [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'status' => Attendance::STATUS_COMPLETED, // An absence is a "completed" state for the day.
            'is_absent' => true,
            'absence_reason' => $reason,
            'clock_in_time' => $dateOfAbsence->copy()->startOfDay(),
            'timezone' => $user->company->timezone ?? config('app.timezone'),
        ];

        // Use the repository to create the record in the database.
        return $this->attendanceRepository->create($attendanceData);
    }

    /**
     * Create a waiting attendance record for a user who is expected to attend but hasn't clocked in yet.
     *
     * @param User $user The user to create the waiting record for
     * @param Carbon $date The date for the attendance record
     * @param string|null $notes Optional notes
     * @return Attendance The created attendance record
     */
    public function createWaitingRecord(User $user, Carbon $date, ?string $notes = null,$startTime,$endTime): Attendance
    {
        $startTimeCarbon = Carbon::parse($startTime);
        $endTimeCarbon = Carbon::parse($endTime);

        $startDate = $startTimeCarbon->copy()->startOfDay();
        $endDateLimit = $startDate->copy()->addDay();

        // Prepare the data for the new waiting record
        $attendanceData = [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'status' => Attendance::STATUS_WAITING,
            'is_absent' => false,
            'notes' => $notes ?? 'Attendance record created in waiting status',
            'timezone' => $user->company->timezone ?? config('app.timezone'),
            'clock_in_time' => null,
            'clock_out_time' => null,
            'total_work_hours' => 0.0,
            'total_break_hours' => 0.0,
            'overtime_hours' => 0.0,
          'start_time' => $startTimeCarbon->toDateTimeString(),
        'end_time' => $endTimeCarbon->toDateTimeString(),
        ];

        // Use the repository to create the record in the database
        return $this->attendanceRepository->create($attendanceData);
    }

    /**
     * Get all users with waiting attendance status for a specific date
     *
     * @param Carbon $date The date to check
     * @param string|null $companyId Optional company ID to filter by
     * @return array Array of user IDs with waiting status
     */
    public function getWaitingUserIdsOnDate(Carbon $date, ?string $companyId = null): array
    {
        $query = $this->attendanceRepository->getQuery()
            ->where('status', Attendance::STATUS_WAITING)
            ->whereDate('clock_in_time', $date);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->pluck('user_id')->all();
    }
    public function updateAttendanceStatus(Attendance $attendance, string $status, bool $absent = false): Attendance
    {
        $attendance->status = $status;
        $attendance->is_absent = $absent;
        $attendance->save();
        return $attendance;
    }
     /**
     * Handles the entire clock-in process.
     * This method is now decoupled from the Illuminate\Http\Request object.
     *
     * @param ClockInDTO $clockInDTO The validated data for the clock-in.
     * @param array $rawRequestData All data from the original request for validation context.
     * @return Attendance The successfully created Attendance record.
     * @throws AttendanceException If a blocking violation is found.
     */
    // public function handleClockInProcess(ClockInDTO $clockInDTO, array $rawRequestData): Attendance
    // {
    //     $user = Auth::user()->load('company');

    //     // --- PRE-VALIDATION (DRY RUN) ---

    //     // Use the mock service with the DTO and raw data.
    //     $mockAttendance = $this->mockAttendanceService->createFromDTO($clockInDTO, $user, $rawRequestData);

    //     // Perform the validation in "dry run" mode.
    //     $violations = $this->constraintService->validateAttendance($mockAttendance, $rawRequestData, true);

    //     // Check for blocking violations.
    //     $blockingViolations = array_filter($violations, fn($v) => $v['blocks_attendance'] ?? false);

    //     if (!empty($blockingViolations)) {
    //         throw AttendanceException::clockInBlocked($blockingViolations);
    //     }

    //     // --- EXECUTION ---

    //     $attendance = $this->clockIn($clockInDTO);

    //     // --- POST-VALIDATION AND RECORDING ---

    //     // Validate again with the real record to log violations.
    //     $this->constraintService->validateAttendance($attendance, $rawRequestData);

    //     return $attendance;
    // }
}
