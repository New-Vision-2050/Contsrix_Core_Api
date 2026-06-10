<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
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
use Modules\Attendance\Jobs\AutoCloseAttendanceJob;
use Modules\Attendance\Jobs\ProcessClockInAttendanceData;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use Modules\Attendance\Services\AttendanceNotificationService;

class AttendanceService
{
    public function __construct(
        private AttendanceRepository $attendanceRepository,
        private AttendanceCalculator $calculator,
        private ?AttendanceNotificationService $notificationService = null,
    ) {}

    /**
     * Clock in the authenticated employee for their current work period.
     *
     * Behaviour (preserved from earlier inline implementation):
     *  1. Reject if the user already has an active, un-closed attendance.
     *  2. Resolve today's work-period start/end in the branch timezone.
     *  3. Enforce the "prevent early clock-in" rule if configured.
     *  4. Upgrade a pre-existing "waiting" row for the period if present;
     *     otherwise create a new attendance row.
     *  5. When the shift extends into the next day, schedule the next-day
     *     transition job.
     *  6. Always schedule auto-clock-out at the next shift's start time.
     */
    public function clockIn(ClockInDTO $clockInDTO): Attendance
    {
        $this->ensureUserHasNoActiveClockIn($clockInDTO->getUserId());

        $user = User::find(auth()->user()->id);
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $currentDate = Carbon::now($timezone)->format('Y-m-d');

        $constraintService = app(AttendanceConstraintService::class);
        $constraints = $constraintService->getTodaysWorkRulesForUser($user, $currentDate);

        [$startDateTime, $endDateTime] = $this->resolveWorkPeriodBounds($constraints, $currentDate, $timezone);

        $this->enforceEarlyClockInRule($clockInDTO, $startDateTime, $constraints, $timezone);

        $attendanceData = $this->buildClockInAttendanceData(
            $clockInDTO,
            $constraints,
            $startDateTime,
            $endDateTime,
            $timezone
        );
        $attendance = $this->persistClockInAttendance($clockInDTO->getUserId(), $startDateTime, $attendanceData);

        $extendsNextDay = $constraints['current_work_period']['extends_to_next_day'] ?? false;
        if ($extendsNextDay) {
            ProcessClockInAttendanceData::dispatch(
                (string) $attendance->id,
                (string) $attendance->company_id,
            )->delay($endDateTime);
        }

        $this->scheduleAutoClockOutWhenNextShiftStarts($attendance, $constraints, $endDateTime);
        $this->scheduleAutoCloseAtMaxOvertime($attendance, $endDateTime, (float) ($attendanceData['max_over_time'] ?? 0.0));

        if ($attendance->is_late && $this->notificationService) {
            try {
                $this->notificationService->notifyLateArrival($attendance);
            } catch (\Throwable) {}
        }

        return $attendance;
    }

    /**
     * Dispatch AutoCloseAttendanceJob at end_time + max_over_time_hours * 60 min (the deadline).
     * The recorded clock_out_time is the shift's scheduled end_time (NOT the deadline) so an
     * employee who never clocks out is capped at scheduled hours with zero overtime — the same
     * behaviour as the AutoCloseStaleShiftsCommand fallback.
     */
    private function scheduleAutoCloseAtMaxOvertime(
        Attendance $attendance,
        Carbon $endDateTime,
        float $maxOverTimeHours,
    ): void {
        $deadline = $endDateTime->copy()->addMinutes((int) round($maxOverTimeHours * 60));

        if (!$deadline->isFuture()) {
            return;
        }

        AutoCloseAttendanceJob::dispatch(
            (string) $attendance->id,
            (string) $attendance->company_id,
            $endDateTime->toIso8601String(),
        )->delay($deadline);
    }

    private function ensureUserHasNoActiveClockIn( $userId): void
    {
        $existing = $this->attendanceRepository->getCurrentAttendance($userId);
        if ($existing && !$existing->clock_out_time && $existing->clock_in_time) {
            throw AttendanceException::alreadyClockedIn();
        }
    }

    /**
     * Build scheduled start/end datetimes for the current work period in the branch
     * timezone. Overnight shift support: if end <= start, bump end to the next day.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveWorkPeriodBounds(array $constraints, string $currentDate, string $timezone): array
    {
        $periodStart = data_get($constraints, 'current_work_period.start_time');
        $periodEnd = data_get($constraints, 'current_work_period.end_time');

        $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $currentDate . ' ' . $periodStart, $timezone);
        $endDateTime = Carbon::createFromFormat('Y-m-d H:i', $currentDate . ' ' . $periodEnd, $timezone);
        if ($startDateTime->gt($endDateTime)) {
            $endDateTime->addDay();
        }
        return [$startDateTime, $endDateTime];
    }

    /**
     * Reject a clock-in that arrives before the configured early-clock-in window
     * (when the constraint has `prevent_early_clock_in` enabled).
     */
    private function enforceEarlyClockInRule(
        ClockInDTO $dto,
        Carbon $scheduledStart,
        array $constraints,
        string $timezone
    ): void {
        $rules = data_get($constraints, 'early_clock_in_rules');
        if (!$rules || !($rules['prevent_early_clock_in'] ?? false)) {
            return;
        }

        $earlyPeriod = (int) ($rules['early_period'] ?? 0);
        $earlyUnit = $rules['early_unit'] ?? 'minutes';
        $clockInMoment = Carbon::parse($dto->getClockInTime(), $timezone);
        $earliestAllowed = $scheduledStart->copy()->sub($earlyPeriod, $earlyUnit);

        if ($clockInMoment->lt($earliestAllowed)) {
            throw new \Exception("غير مسموح بتسجيل الحضور قبل {$earlyPeriod} {$earlyUnit} من بداية الفترة.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClockInAttendanceData(
        ClockInDTO $dto,
        array $constraints,
        Carbon $startDateTime,
        Carbon $endDateTime,
        string $timezone
    ): array {
        return [
            'user_id' => $dto->getUserId(),
            'company_id' => $dto->getCompanyId(),
            'clock_in_time' => $dto->getClockInTime(),
            'clock_in_location' => $dto->getLocation(),
            'start_time' => $startDateTime->format('Y-m-d H:i:s'),
            'end_time' => $endDateTime->format('Y-m-d H:i:s'),
            'notes' => $dto->getNotes(),
            'ip_address' => $dto->getIpAddress(),
            'user_agent' => $dto->getUserAgent(),
            'status' => Attendance::STATUS_ACTIVE,
            'is_absent' => 0,
            'is_late' => 0,
            'is_holiday' => 0,
            'day_status' => 'in_location',
            'timezone' => $timezone,
            'max_over_time' => $constraints['max_over_time'] ?? null,
            'business_date' => $startDateTime->toDateString(),
        ];
    }

    /**
     * Activate the pre-created "waiting" attendance row for this user/period if
     * one exists (see CreateWaitingAttendanceCommand), otherwise create a new row.
     */
    private function persistClockInAttendance(
         $userId,
        Carbon $startDateTime,
        array $attendanceData
    ): Attendance {
        $startTimeStr = $startDateTime->format('Y-m-d H:i:s');

        $waiting = Attendance::query()
            ->where('user_id', $userId)
            ->where('start_time', $startTimeStr)
            ->whereNull('clock_in_time')
            ->first();

        if ($waiting) {
            $waiting->update($attendanceData);
            return $waiting->refresh();
        }

        return $this->attendanceRepository->create($attendanceData);
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
            // Pass ISO 8601 with TZ offset so the queue worker doesn't reparse this as UTC
            // and shift the wall clock by the branch offset.
            $nextStart->toIso8601String(),
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
     * Clock out the authenticated employee.
     *
     * Behaviour (preserved):
     *  1. Reject if the user has no active attendance.
     *  2. Reject if the attendance already has a clock_out_time.
     *  3. Persist clock_out_time (normalised to branch timezone), clock_out_location,
     *     appended notes, and mark the row completed + day_status=clocked_out.
     *  4. Re-run the calculator so total_work_hours / overtime_hours / early_departure
     *     are recomputed from the final clock-in/clock-out pair.
     */
    public function clockOut(ClockOutDTO $clockOutDTO): Attendance
    {
        $attendance = $this->attendanceRepository->getCurrentAttendance($clockOutDTO->getUserId());
        if (!$attendance) {
            throw AttendanceException::notClockedIn();
        }
        if ($attendance->clock_out_time) {
            throw AttendanceException::alreadyClockedOut();
        }

        $this->attendanceRepository->update(
            $attendance->id,
            $this->buildClockOutUpdatePayload($attendance, $clockOutDTO)
        );
        $attendance->refresh();

        // Use the domain calculator instead of the legacy model method.
        // Reads timezone from the attendance row — correct in all contexts (request, job, command).
        $input  = $this->buildCalculatorInput($attendance);
        $result = $this->calculator->calculate($input);

        $attendance->update([
            'total_work_hours'        => $result->totalWorkHours,
            'total_break_hours'       => $result->totalBreakHours,
            'overtime_hours'          => $result->overtimeHours,
            'is_late'                 => $result->isLate,
            'late_minutes'            => $result->lateMinutes,
            'is_early_departure'      => $result->isEarlyDeparture,
            'early_departure_minutes' => $result->earlyDepartureMinutes,
        ]);

        $attendance->refresh();

        if ($result->isEarlyDeparture && $this->notificationService) {
            try {
                $this->notificationService->notifyEarlyDeparture($attendance);
            } catch (\Throwable) {}
        }

        return $attendance;
    }

    private function buildCalculatorInput(Attendance $attendance): CalculatorInput
    {
        $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

        // start_time/end_time/clock_in_time/clock_out_time are stored as wall-clock strings
        // already in the branch timezone (see Attendance model comment about no datetime cast).
        // Pass $timezone as the second arg so Carbon labels them with that TZ instead of
        // defaulting to UTC and converting (which shifts every value by the branch offset).
        $scheduledStart = CarbonImmutable::parse($attendance->start_time, $timezone);
        $scheduledEnd   = CarbonImmutable::parse($attendance->end_time, $timezone);

        if (! $scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $clockIn  = $attendance->clock_in_time
            ? CarbonImmutable::parse($attendance->clock_in_time, $timezone)
            : null;
        $clockOut = $attendance->clock_out_time
            ? CarbonImmutable::parse($attendance->clock_out_time, $timezone)
            : null;

        $totalBreakMinutes = (int) $attendance->breaks()
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        $snapshot       = $attendance->appliedAttendanceConstraint?->constraint_snapshot ?? [];
        $latenessRules  = $snapshot['lateness_rules'] ?? [];
        $graceValue     = (int) ($latenessRules['lateness_period'] ?? $latenessRules['grace_period_minutes'] ?? 0);
        $graceUnit      = (string) ($latenessRules['lateness_unit'] ?? 'minute');
        $graceMinutes   = match (strtolower($graceUnit)) {
            'hour' => $graceValue * 60,
            'day'  => $graceValue * 1440,
            default => $graceValue,
        };

        return new CalculatorInput(
            scheduledStart:     $scheduledStart,
            scheduledEnd:       $scheduledEnd,
            clockIn:            $clockIn,
            clockOut:           $clockOut,
            totalBreakMinutes:  $totalBreakMinutes,
            gracePeriodMinutes: max(0, $graceMinutes),
            maxOverTimeHours:   (float) ($attendance->max_over_time ?? 0.0),
            timezone:           $timezone,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClockOutUpdatePayload(Attendance $attendance, ClockOutDTO $dto): array
    {
        return [
            'clock_out_time' => Carbon::parse($dto->getClockOutTime())->setTimezone(getTimeZoneBranchByRequest()),
            'clock_out_location' => $dto->getLocation(),
            'notes' => $attendance->notes . ($dto->getNotes() ? "\n" . $dto->getNotes() : ''),
            'status' => Attendance::STATUS_COMPLETED,
            'day_status' => 'clocked_out',
        ];
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

        // Close the active break with its end time and (optionally) a note.
        $activeBreak = $attendance->activeBreak();
        if ($activeBreak) {
            $activeBreak->end_time = now();
            $activeBreak->calculateDuration();
            if ($notes) {
                $activeBreak->notes = ($activeBreak->notes ? $activeBreak->notes . "\n" : '') . "End: " . $notes;
            }
            $activeBreak->save();
        }

        // Persist the new total_break_hours (+ optional notes) in a single attendance update.
        // Previously this flow issued 3 saves (break.save + updateTotalBreakHours save + updateAttendance save).
        $updateData = ['total_break_hours' => $attendance->calculateTotalBreakHours()];
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
        $attendance->refresh();
        $result = $this->calculator->calculate($this->buildCalculatorInput($attendance));
        $attendance->update([
            'total_work_hours'        => $result->totalWorkHours,
            'total_break_hours'       => $result->totalBreakHours,
            'overtime_hours'          => $result->overtimeHours,
            'is_late'                 => $result->isLate,
            'late_minutes'            => $result->lateMinutes,
            'is_early_departure'      => $result->isEarlyDeparture,
            'early_departure_minutes' => $result->earlyDepartureMinutes,
        ]);
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

    public function getTeamAttendance(array $filters, ?int $page = 1, ?int $perPage = 10, $userId = null): LengthAwarePaginator
    {
        $page    = max(1, (int) ($page ?? 1));
        $perPage = max(1, (int) ($perPage ?? 10));

        $normalizedUserId = $userId instanceof UuidInterface ? $userId->toString() : $userId;
        $filterInput      = $filters;
        $startDate        = $filterInput['start_date'] ?? null;
        $endDate          = $filterInput['end_date'] ?? null;
        unset($filterInput['start_date'], $filterInput['end_date']);
        $calendarTz = $this->attendanceFilterCalendarTimezone();

        // Build the shared filtered base query (WHERE conditions only, no SELECT/GROUP).
        $base = Attendance::query()
            ->filter($filterInput)
            ->when($normalizedUserId, static fn($q) => $q->where('user_id', $normalizedUserId))
            ->when(
                $startDate !== null && $startDate !== '',
                fn($q) => $q->where(
                    'start_time', '>=',
                    Carbon::parse((string) $startDate, $calendarTz)->startOfDay()->utc()
                )
            )
            ->when(
                $endDate !== null && $endDate !== '',
                fn($q) => $q->where(
                    'start_time', '<',
                    Carbon::parse((string) $endDate, $calendarTz)->addDay()->startOfDay()->utc()
                )
            )
            ->whereNotNull('business_date');

        // Count distinct (user_id, business_date) groups without loading records.
        $countRow = $base->clone()
            ->selectRaw('COUNT(DISTINCT CONCAT(user_id, CHAR(0), business_date)) AS grp_count')
            ->first();
        $total = (int) ($countRow->grp_count ?? 0);

        if ($total === 0) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        // Pick one representative attendance ID per (user_id, business_date) for this page.
        // Prefer a row that already has clock_in_time; otherwise take the lexicographically
        // smallest UUID (deterministic tiebreaker).
        $repIds = $base->clone()
            ->selectRaw("COALESCE(MIN(CASE WHEN clock_in_time IS NOT NULL THEN id END), MIN(id)) AS rep_id")
            ->groupBy('user_id', 'business_date')
            ->orderByRaw('MIN(start_time) ASC')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->pluck('rep_id');

        $records = Attendance::query()
            ->whereIn('id', $repIds)
            ->with(AttendanceTeamPresenter::requiredRelations())
            ->select($this->baseAttendanceSelectColumns())
            ->orderBy('start_time')
            ->get();

        return new LengthAwarePaginator($records, $total, $perPage, $page);
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
            'status' => Attendance::STATUS_ABSENT, // An absence is a "completed" state for the day.
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
