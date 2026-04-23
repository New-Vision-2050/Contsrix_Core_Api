<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
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
use Modules\Attendance\Jobs\AutoClockOutAtNextShiftStartJob;
use Modules\Attendance\Jobs\ProcessClockInAttendanceData;

class AttendanceService
{
    public function __construct(
        private AttendanceRepository $attendanceRepository,
    ) {}

    /**
     * Clock in employee
     */
    public function clockIn(ClockInDTO $clockInDTO)
    {
        $existingAttendance = $this->attendanceRepository->getCurrentAttendance($clockInDTO->getUserId());
       
        if ($existingAttendance && !$existingAttendance->clock_out_time && $existingAttendance->clock_in_time) {
            throw AttendanceException::alreadyClockedIn();
        }
   
        $user = User::find(auth()->user()->id);
        $constraintService = app(AttendanceConstraintService::class);
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $currentDate = Carbon::now($timezone)->format('Y-m-d');
        $constraints = $constraintService->getTodaysWorkRulesForUser($user, $currentDate);
        $extendsNextDay = $constraints['current_work_period']['extends_to_next_day'] ?? false;


        $periodStartTime = data_get($constraints, 'current_work_period.start_time');
        $periodEndTime = data_get($constraints, 'current_work_period.end_time');

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $currentDate . ' ' . $periodStartTime, $timezone);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $currentDate . ' ' . $periodEndTime, $timezone);
        if ($startDateTime->gt($endDateTime)) {
            $endDateTime->addDay();
        }

        $earlyClockInRules = data_get($constraints, 'early_clock_in_rules');
        if ($earlyClockInRules && ($earlyClockInRules['prevent_early_clock_in'] ?? false)) {
            $earlyPeriod = (int) ($earlyClockInRules['early_period'] ?? 0);
            $earlyUnit = $earlyClockInRules['early_unit'] ?? 'minutes';
            $clockInMoment = Carbon::parse($clockInDTO->getClockInTime(), $timezone);
            $earliestAllowedTime = $startDateTime->copy()->sub($earlyPeriod, $earlyUnit);
            if ($clockInMoment->lt($earliestAllowedTime)) {
                throw new \Exception("غير مسموح بتسجيل الحضور قبل {$earlyPeriod} {$earlyUnit} من بداية الفترة.");
            }
        }

        $attendanceData = [
            'user_id' => $clockInDTO->getUserId(),
            'company_id' => $clockInDTO->getCompanyId(),
            'clock_in_time' => $clockInDTO->getClockInTime(),
            'clock_in_location' => $clockInDTO->getLocation(),
            'start_time' => $startDateTime->format('Y-m-d H:i:s'),
            'end_time' => $endDateTime->format('Y-m-d H:i:s'),
            'notes' => $clockInDTO->getNotes(),
            'ip_address' => $clockInDTO->getIpAddress(),
            'user_agent' => $clockInDTO->getUserAgent(),
            'status' => 'active',
            'is_absent' => 0,
            'is_late' => 0,
            'is_holiday' => 0,
            'day_status' => 'in_location',
            'timezone' => $timezone,
            'max_over_time' => $constraints['max_over_time'] ?? null,
        ];

        $startTimeStr = $startDateTime->format('Y-m-d H:i:s');
        $attendance = Attendance::query()
            ->where('user_id', $clockInDTO->getUserId())
            ->where('start_time', $startTimeStr)
            ->whereNull('clock_in_time')
            ->first();

        if ($attendance) {
            $attendance->update($attendanceData);
            $attendance = $attendance->refresh();
        } else {
            $attendance = $this->attendanceRepository->create($attendanceData);
        }

        if ($extendsNextDay) {
            ProcessClockInAttendanceData::dispatch($attendance->id)->delay($endDateTime);
        }

        $this->scheduleAutoClockOutWhenNextShiftStarts($attendance, $constraints, $endDateTime);

        return $attendance;
    }

    /**
     * Queue auto clock-out at the start of the next scheduled period (same merged day list from constraints)
     * if the user is still clocked into this shift.
     */
    private function scheduleAutoClockOutWhenNextShiftStarts(
        Attendance $attendance,
        array $constraints,
        Carbon $currentPeriodEnd
    ): void {
        $nextStart = $this->resolveNextShiftStartAfterPeriodEnd($constraints, $currentPeriodEnd);
        if ($nextStart === null || !$nextStart->isFuture()) {
            return;
        }

        AutoClockOutAtNextShiftStartJob::dispatch(
            (string) $attendance->id,
            (string) $attendance->company_id,
            $nextStart->copy()->format('Y-m-d H:i:s'),
        )->delay($nextStart);
    }

    /**
     * @param  array<string, mixed>  $constraints  Output of {@see AttendanceConstraintService::getTodaysWorkRulesForUser}
     */
    private function resolveNextShiftStartAfterPeriodEnd(array $constraints, Carbon $currentPeriodEnd): ?Carbon
    {
        $tz = $currentPeriodEnd->getTimezone();

        foreach ($constraints['all_work_periods'] ?? [] as $period) {
            $pStart = $period['period_start_time_carbon'] ?? null;
            if (!$pStart instanceof Carbon) {
                $date = $period['date'] ?? $currentPeriodEnd->format('Y-m-d');
                $startTime = $period['start_time'] ?? null;
                if (!is_string($startTime) || $startTime === '') {
                    continue;
                }
                $timeHi = strlen($startTime) > 5 ? substr($startTime, 0, 5) : $startTime;
                $pStart = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $timeHi, $tz);
            } else {
                $pStart = $pStart->copy()->setTimezone($tz);
            }

            if ($pStart->greaterThan($currentPeriodEnd)) {
                return $pStart;
            }
        }

        return null;
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


        // Update attendance record
        $updateData = [
            'clock_out_time' => Carbon::parse($clockOutDTO->getClockOutTime())->setTimezone(getTimeZoneBranchByRequest()),
            'clock_out_location' => $clockOutDTO->getLocation(),
            'notes' => $attendance->notes . ($clockOutDTO->getNotes() ? "\n" . $clockOutDTO->getNotes() : ''),
            'status' => 'completed',
            'day_status' => 'clocked_out'
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
        $userId = $this->toUuid($userId);

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
        $userId = $this->toUuid($userId);

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
    public function getCurrentAttendance(UuidInterface $userId, bool $withUser = true): ?Attendance
    {
        return $this->attendanceRepository->getCurrentAttendance($userId, $withUser);
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
                'start_time' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
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
    public function getAttendanceSummary(UuidInterface $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : now()->endOfMonth();

        // 1. Get all attendance records for the period.
        $records  = $this->attendanceRepository->getAttendanceByDateRange($userId, $startDate, $endDate);

        $attendances = $this->processAttendancePeriods($records);
        // 2. Calculate the base total for percentages.
        // We will use the total number of records in the given date range as the base "100%".
        $totalRecords = $attendances->count();

        // It's more efficient to calculate these once and store them in variables.
        $totalAttendant = $attendances->whereNotNull('clock_in_time')->count();
        $totalAbsent = $attendances->whereIn('is_absent',[true,1])->count();
        $totalHoliday = $attendances->whereIn('is_holiday',[true,1])->count();
        $totalDepartures = $attendances->whereNotNull('clock_out_time')->count();
        $totalLate = $attendances->whereIn('is_late',[true,1])->count();
        $totalEarly = $attendances->whereIn('is_early_departure',[true,1])->count();

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

    private function toUuid(UuidInterface|string $id): UuidInterface
    {
        return $id instanceof UuidInterface ? $id : Uuid::fromString($id);
    }

    /**
     * @return list<string>
     */
    private function baseAttendanceSelectColumns(): array
    {
        return [
            'id', 'user_id', 'company_id', 'status', 'is_late', 'is_absent',
            'is_holiday', 'day_status', 'clock_in_time', 'clock_out_time',
            'start_time', 'overtime_hours', 'clock_in_location', 'location_tracking',
        ];
    }

    /**
     * Calendar-day interpretation for attendance list/export date filters (matches {@see AttendanceFilter}).
     */
    private function attendanceFilterCalendarTimezone(): string
    {
        if (function_exists('getTimeZoneBranchByRequest')) {
            $tz = getTimeZoneBranchByRequest();
            if (is_string($tz) && $tz !== '') {
                return $tz;
            }
        }

        return (string) config('app.timezone');
    }

    private function fetchAttendanceRecordsForExport(
        array $filters,
        array $with,
        UuidInterface|string|null $userId = null
    ): Collection
    {
        $normalizedUserId = $userId instanceof UuidInterface ? $userId->toString() : $userId;

        // Do not pass start_date/end_date into ->filter(): AttendanceFilter historically used
        // whereDate('start_time', …) → DATE(start_time), which breaks indexes and can exhaust sort memory.
        $filterInput = $filters;
        $startDate = $filterInput['start_date'] ?? null;
        $endDate = $filterInput['end_date'] ?? null;
        unset($filterInput['start_date'], $filterInput['end_date']);

        $calendarTz = $this->attendanceFilterCalendarTimezone();

        return Attendance::query()
            ->filter($filterInput)
            ->when($normalizedUserId, static function ($query) use ($normalizedUserId) {
                $query->where('user_id', $normalizedUserId);
            })
            ->when($startDate !== null && $startDate !== '', function ($query) use ($startDate, $calendarTz) {
                $query->where(
                    'start_time',
                    '>=',
                    Carbon::parse((string) $startDate, $calendarTz)->startOfDay()->utc()
                );
            })
            ->when($endDate !== null && $endDate !== '', function ($query) use ($endDate, $calendarTz) {
                $query->where(
                    'start_time',
                    '<',
                    Carbon::parse((string) $endDate, $calendarTz)->addDay()->startOfDay()->utc()
                );
            })
            ->select($this->baseAttendanceSelectColumns())
            ->with($with)
            ->orderBy('start_time')
            ->get();
    }

    private function recalculateWorkHoursAndSave(Attendance $attendance): void
    {
        $attendance->updateTotalBreakHours();
        $attendance->calculateWorkHours();
        $attendance->save();
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
            $this->recalculateWorkHoursAndSave($attendance);
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
        $this->recalculateWorkHoursAndSave($attendance);

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
    public function getAttendanceForExport(array $filters): Collection
    {
        $realAttendanceRecords = $this->fetchAttendanceRecordsForExport(
            $filters,
            ['user', 'user.userProfessionalData', 'user.company']
        );

        return $this->processAttendancePeriods($realAttendanceRecords);
    }

    public function getTeamAttendance(array $filters, ?int $page = 1, ?int $perPage = 10, $userId = null)
    {
        $realAttendanceRecords = $this->fetchAttendanceRecordsForExport(
            $filters,
            ['user', 'user.userProfessionalData'],
            $userId
        );

        $processedRecords = $this->processAttendancePeriods($realAttendanceRecords);


        return $this->attendanceRepository->paginatedAttendance(
            $processedRecords,
            $page,
            $perPage
        );
    }

    /**
     * All users currently clocked in (active status, no clock-out). Ignores date range and workflow status from filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getOpenAttendances(array $filters, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $filterInput = $filters;
        unset(
            $filterInput['start_date'],
            $filterInput['end_date'],
            $filterInput['attendance_status'],
            $filterInput['status'],
        );

        return Attendance::query()
            ->active()
            ->whereNotNull('clock_in_time')
            ->filter($filterInput)
            ->with([
                'user.company',
                'user.userProfessionalData.jobTitle',
                'user.userProfessionalData.department',
                'user.userProfessionalData.branch',
                'user.userProfessionalData.management',
                'user.userProfessionalData.attendanceConstraint',
            ])
            ->orderByDesc('clock_in_time')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function processAttendancePeriods(Collection $realAttendanceRecords): Collection
    {
        $processedRecords = collect();

        $realAttendanceRecords
            ->groupBy('user_id')
            ->each(function ($userDailyRecords, $userId) use (&$processedRecords) {
                $userDailyRecords
                    ->groupBy(function ($item) {
                        return Carbon::parse($item->start_time)->toDateString();
                    })
                    ->each(function ($dailyRecordsForUserAndDate, $date) use (&$processedRecords) {
                        $representativeRecord = $dailyRecordsForUserAndDate->first(function ($record) {
                            return $record->clock_in_time !== null;
                        });

                        if (!$representativeRecord) {
                            $representativeRecord = $dailyRecordsForUserAndDate->first();
                        }

                        if ($representativeRecord) {
                            $processedRecords->push($representativeRecord);
                        }
                    });
            });

        return $processedRecords->sortBy('start_time')->values();
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

        // Set clock out time to current time in UTC for database storage
        $timestamp = Carbon::now($attendance->timezone);
        $updateData = [
            'clock_out_time' => $timestamp->format('Y-m-d H:i:s'),
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
            $this->recalculateWorkHoursAndSave($attendance);
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
        $attendanceId = $this->toUuid($attendanceId);

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

        // Get timezone and convert to UTC for database query
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $dateInTz = $date->copy()->setTimezone($timezone);
        
        if ($period && isset($period['start_time']) && isset($period['end_time'])) {
            // Check for records within the specific period on the given date
            $startTime = Carbon::parse($dateInTz->toDateString() . ' ' . $period['start_time'], $timezone)->setTimezone('UTC');
            $endTime = Carbon::parse($dateInTz->toDateString() . ' ' . $period['end_time'], $timezone)->setTimezone('UTC');
            $query->whereBetween('start_time', [$startTime, $endTime]);
        } else {
            // Fallback to checking the entire day if no period is specified
            $dayStartUtc = $dateInTz->copy()->startOfDay()->setTimezone('UTC');
            $dayEndUtc = $dateInTz->copy()->endOfDay()->setTimezone('UTC');
            $query->whereBetween('start_time', [$dayStartUtc, $dayEndUtc]);
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
        // Convert date range to UTC for database query
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $dateInTz = $date->copy()->setTimezone($timezone);
        $dayStartUtc = $dateInTz->copy()->startOfDay()->setTimezone('UTC');
        $dayEndUtc = $dateInTz->copy()->endOfDay()->setTimezone('UTC');
        
        $query = $this->attendanceRepository->getQuery()
            ->where('status', Attendance::STATUS_WAITING)
            ->whereBetween('clock_in_time', [$dayStartUtc, $dayEndUtc]);

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
