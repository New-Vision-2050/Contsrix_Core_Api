<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\User\Models\User;

class SubEntityEmployeeAttendanceStatusService
{
    private const STATUS_REQUIRED_ATTENDANCE = 'required_attendance';

    private const STATUS_HOLIDAY = 'holiday';

    private const LABEL_REQUIRED_ATTENDANCE = 'مطلوب للحضور';

    private const LABEL_HOLIDAY = 'اجازه';

    /**
     * @param  Collection<string, User|null>  $usersByKey
     * @return Collection<string, array<string, mixed>>
     */
    public function buildRequiredHolidayStatusesForUsersByKey(Collection $usersByKey, string $workDate): Collection
    {
        $workDate = $this->normalizeWorkDate($workDate);
        $usersByKey = $usersByKey->mapWithKeys(
            fn (?User $user, string|int $key): array => [(string) $key => $user]
        );

        $attendanceRowsByUserId = $this->getDailyAttendanceRowsForUsers(
            $usersByKey
                ->filter()
                ->map(fn (User $user): string => (string) $user->id)
                ->unique()
                ->values(),
            $workDate
        )->groupBy(fn (Attendance $attendance): string => (string) $attendance->user_id);

        $holidayUserIds = $usersByKey
            ->filter(function (?User $user) use ($attendanceRowsByUserId, $workDate): bool {
                if (! $user) {
                    return false;
                }

                if ($this->activeOverrideStatus($user, $workDate) === self::STATUS_HOLIDAY) {
                    return true;
                }

                $rows = $attendanceRowsByUserId->get((string) $user->id, collect());
                $attendance = $this->requiredHolidayRepresentative($rows);

                return $attendance !== null && $this->isHolidayAttendance($attendance);
            })
            ->map(fn (User $user): string => (string) $user->id)
            ->unique()
            ->values();

        $holidayRangesByUserId = $this->holidayRangesForUsers($holidayUserIds, $workDate);

        return $usersByKey->mapWithKeys(function (?User $user, string $key) use ($attendanceRowsByUserId, $holidayRangesByUserId, $workDate): array {
            $userId = $user?->id ? (string) $user->id : null;
            $rows = $userId ? $attendanceRowsByUserId->get($userId, collect()) : collect();
            $attendance = $this->requiredHolidayRepresentative($rows);
            $override = $this->activeOverrideStatus($user, $workDate);
            $holidayRange = $override === self::STATUS_HOLIDAY
                ? $this->overrideHolidayRange($user, $workDate)
                : ($userId ? $holidayRangesByUserId->get($userId) : null);

            return [$key => $this->requiredHolidayListPayload($attendance, $workDate, $override, $holidayRange)];
        });
    }

    /**
     * Sets the employee's attendance requirement status.
     *
     * Holiday with date_from/date_to stays active only inside that inclusive range.
     * After date_to the override expires and the employee is treated as مطلوب للحضور again.
     * Holiday without date_to remains open-ended until manually changed (legacy behaviour).
     *
     * @param  array{date_from?: string|null, date_to?: string|null}  $dates
     */
    public function setRequiredHolidayStatus(User $user, string $status, array $dates = []): array
    {
        if (! in_array($status, [self::STATUS_HOLIDAY, self::STATUS_REQUIRED_ATTENDANCE], true)) {
            throw new \InvalidArgumentException('Invalid daily attendance status.');
        }

        return DB::transaction(function () use ($user, $status, $dates): array {
            $today = $this->normalizeWorkDate(Carbon::now($this->attendanceCalendarTimezone())->toDateString());
            $dateFrom = isset($dates['date_from']) && is_string($dates['date_from']) && $dates['date_from'] !== ''
                ? $this->normalizeWorkDate($dates['date_from'])
                : $today;
            $dateTo = isset($dates['date_to']) && is_string($dates['date_to']) && $dates['date_to'] !== ''
                ? $this->normalizeWorkDate($dates['date_to'])
                : null;

            if ($dateTo !== null && $dateTo < $dateFrom) {
                throw new \InvalidArgumentException('date_to must be on or after date_from.');
            }

            // required_attendance clears any holiday window; until is only meaningful for holiday.
            $until = $status === self::STATUS_HOLIDAY ? $dateTo : null;

            $user->forceFill([
                'manual_attendance_status' => $status,
                'manual_attendance_status_since' => $dateFrom,
                'manual_attendance_status_until' => $until,
            ])->save();

            $syncFrom = $dateFrom;
            $syncTo = $until ?? $dateFrom;

            // When clearing back to required attendance without an explicit range,
            // sync the current work day so today's row reflects مطلوب للحضور immediately.
            if ($status === self::STATUS_REQUIRED_ATTENDANCE && ($dates['date_from'] ?? null) === null) {
                $syncFrom = $today;
                $syncTo = $today;
            }

            $this->syncRequiredHolidayAttendanceRange($user, $status, $syncFrom, $syncTo);

            $responseDate = $this->activeOverrideStatus($user->fresh(), $today) !== null
                ? $today
                : $syncFrom;

            if ($responseDate < $syncFrom || $responseDate > $syncTo) {
                $responseDate = $syncFrom;
            }

            $rows = $this->getDailyAttendanceRowsForUser((string) $user->id, $responseDate);
            $holidayRange = $status === self::STATUS_HOLIDAY
                ? ['date_from' => $dateFrom, 'date_to' => $until]
                : null;

            return $this->requiredHolidayListPayload(
                $this->requiredHolidayRepresentative($rows),
                $responseDate,
                $status,
                $holidayRange
            );
        });
    }

    /**
     * Returns the persistent manual override status for the user if it is active
     * on the requested date (inclusive since/until window), otherwise null.
     */
    private function activeOverrideStatus(?User $user, string $workDate): ?string
    {
        return ManualAttendanceStatus::activeOn($user, $workDate);
    }

    private function syncRequiredHolidayAttendanceRange(
        User $user,
        string $status,
        string $dateFrom,
        string $dateTo
    ): void {
        $cursor = Carbon::parse($dateFrom, $this->attendanceCalendarTimezone())->startOfDay();
        $end = Carbon::parse($dateTo, $this->attendanceCalendarTimezone())->startOfDay();

        while ($cursor->lte($end)) {
            $workDate = $cursor->toDateString();
            $rows = $this->getDailyAttendanceRowsForUser((string) $user->id, $workDate);

            if ($rows->isEmpty()) {
                $rows = collect([$this->createDailyRequiredHolidayAttendance($user, $workDate, $status)]);
            }

            $rows->each(fn (Attendance $attendance) => $this->applyRequiredHolidayStatus($attendance, $status, $workDate));
            $cursor->addDay();
        }
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, Attendance>
     */
    private function getDailyAttendanceRowsForUsers(Collection $userIds, string $workDate): Collection
    {
        $ids = $userIds
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Attendance::query()
            ->whereIn('user_id', $ids->all())
            ->where(function ($query) use ($workDate) {
                $query->whereDate('business_date', $workDate)
                    ->orWhereDate('start_time', $workDate);
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @return Collection<int, Attendance>
     */
    private function getDailyAttendanceRowsForUser(string $userId, string $workDate): Collection
    {
        return $this->getDailyAttendanceRowsForUsers(collect([$userId]), $workDate);
    }

    /**
     * @param  Collection<int, Attendance>  $rows
     */
    private function requiredHolidayRepresentative(Collection $rows): ?Attendance
    {
        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->first(fn (Attendance $attendance): bool => $this->isHolidayAttendance($attendance))
            ?? $rows->first();
    }

    private function requiredHolidayPayload(?Attendance $attendance, string $workDate, ?string $override = null): array
    {
        $isHoliday = $this->isHolidayStatus($attendance, $override);

        return [
            'attendance_id' => $attendance?->id ? (string) $attendance->id : null,
            'attendance_work_date' => $workDate,
            'attendance_status_code' => $isHoliday ? self::STATUS_HOLIDAY : self::STATUS_REQUIRED_ATTENDANCE,
            'attendance_status_label' => $isHoliday ? self::LABEL_HOLIDAY : self::LABEL_REQUIRED_ATTENDANCE,
        ];
    }

    /**
     * @param  array{date_from: string, date_to: string|null}|null  $holidayRange
     * @return array<string, mixed>
     */
    private function requiredHolidayListPayload(
        ?Attendance $attendance,
        string $workDate,
        ?string $override = null,
        ?array $holidayRange = null
    ): array {
        $isHoliday = $this->isHolidayStatus($attendance, $override);

        return array_merge($this->requiredHolidayPayload($attendance, $workDate, $override), [
            'attendance_date_from' => $isHoliday ? ($holidayRange['date_from'] ?? $workDate) : null,
            'attendance_date_to' => $isHoliday
                ? ($holidayRange !== null && array_key_exists('date_to', $holidayRange) ? $holidayRange['date_to'] : $workDate)
                : null,
        ]);
    }

    private function isHolidayStatus(?Attendance $attendance, ?string $override = null): bool
    {
        return $override !== null
            ? $override === self::STATUS_HOLIDAY
            : ($attendance !== null && $this->isHolidayAttendance($attendance));
    }

    /**
     * @return array{date_from: string, date_to: string|null}|null
     */
    private function overrideHolidayRange(?User $user, string $workDate): ?array
    {
        if ($this->activeOverrideStatus($user, $workDate) !== self::STATUS_HOLIDAY) {
            return null;
        }

        $since = $user?->manual_attendance_status_since;
        $dateFrom = $since === null
            ? $workDate
            : Carbon::parse((string) $since, $this->attendanceCalendarTimezone())->toDateString();

        if ($dateFrom > $workDate) {
            $dateFrom = $workDate;
        }

        $until = $user?->manual_attendance_status_until;
        $dateTo = $until === null
            ? null
            : Carbon::parse((string) $until, $this->attendanceCalendarTimezone())->toDateString();

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<string, array{date_from: string, date_to: string}>
     */
    private function holidayRangesForUsers(Collection $userIds, string $workDate): Collection
    {
        $workDate = $this->normalizeWorkDate($workDate);
        $ids = $userIds
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $rowsByUserId = Attendance::query()
            ->whereIn('user_id', $ids->all())
            ->where(function ($query) {
                $query->where('is_holiday', 1)
                    ->orWhere('status', Attendance::STATUS_HOLIDAY)
                    ->orWhere('day_status', self::STATUS_HOLIDAY);
            })
            ->get()
            ->groupBy(fn (Attendance $attendance): string => (string) $attendance->user_id);

        return $rowsByUserId->map(function (Collection $rows) use ($workDate): ?array {
            $holidayDates = $rows
                ->map(fn (Attendance $attendance): ?string => $this->attendanceWorkDate($attendance))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            if (! $holidayDates->contains($workDate)) {
                return null;
            }

            $dateSet = $holidayDates->flip();
            $dateFrom = $workDate;
            $dateTo = $workDate;

            while ($dateSet->has(Carbon::parse($dateFrom, $this->attendanceCalendarTimezone())->subDay()->toDateString())) {
                $dateFrom = Carbon::parse($dateFrom, $this->attendanceCalendarTimezone())->subDay()->toDateString();
            }

            while ($dateSet->has(Carbon::parse($dateTo, $this->attendanceCalendarTimezone())->addDay()->toDateString())) {
                $dateTo = Carbon::parse($dateTo, $this->attendanceCalendarTimezone())->addDay()->toDateString();
            }

            return [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ];
        })->filter();
    }

    private function isHolidayAttendance(Attendance $attendance): bool
    {
        return (int) ($attendance->is_holiday ?? 0) === 1
            || ($attendance->status ?? null) === Attendance::STATUS_HOLIDAY
            || ($attendance->day_status ?? null) === self::STATUS_HOLIDAY;
    }

    private function createDailyRequiredHolidayAttendance(User $user, string $workDate, string $status): Attendance
    {
        $isHoliday = $status === self::STATUS_HOLIDAY;
        $startOfDay = Carbon::parse($workDate, $this->attendanceCalendarTimezone())->startOfDay();

        return Attendance::query()->create([
            'user_id' => $user->id,
            'company_id' => $user->company_id ?? tenant('id') ?? auth()->user()?->company_id,
            'clock_in_time' => null,
            'clock_out_time' => null,
            'total_work_hours' => 0,
            'total_break_hours' => 0,
            'overtime_hours' => 0,
            'is_late' => 0,
            'is_absent' => 0,
            'is_holiday' => $isHoliday ? 1 : 0,
            'late_minutes' => 0,
            'start_time' => $startOfDay->toDateTimeString(),
            'status' => $isHoliday ? Attendance::STATUS_HOLIDAY : Attendance::STATUS_WAITING,
            'day_status' => $isHoliday ? self::STATUS_HOLIDAY : 'work_day',
            'timezone' => $this->attendanceCalendarTimezone(),
            'business_date' => $workDate,
            'notes' => $isHoliday
                ? ManualAttendanceStatus::HOLIDAY_ROW_NOTE
                : ManualAttendanceStatus::REQUIRED_ROW_NOTE,
        ]);
    }

    private function applyRequiredHolidayStatus(Attendance $attendance, string $status, string $workDate): void
    {
        $isHoliday = $status === self::STATUS_HOLIDAY;
        $hasClockTimes = $attendance->clock_in_time !== null || $attendance->clock_out_time !== null;

        $data = [
            'is_holiday' => $isHoliday ? 1 : 0,
            'is_absent' => 0,
            'day_status' => $isHoliday ? self::STATUS_HOLIDAY : 'work_day',
            'business_date' => $workDate,
            // Stamped on rows this endpoint rewrote as well as ones it created, so readers
            // can tell an override holiday from a weekend or public holiday and stop
            // honouring it once the date leaves the override window.
            'notes' => $isHoliday
                ? ManualAttendanceStatus::HOLIDAY_ROW_NOTE
                : ManualAttendanceStatus::REQUIRED_ROW_NOTE,
        ];

        if (! $hasClockTimes) {
            $data['status'] = $isHoliday ? Attendance::STATUS_HOLIDAY : Attendance::STATUS_WAITING;
        } elseif (in_array($attendance->status, [Attendance::STATUS_HOLIDAY, Attendance::STATUS_WAITING, Attendance::STATUS_ABSENT], true)) {
            $data['status'] = $this->clockedLifecycleStatus($attendance);
        }

        $attendance->fill($data);
        $attendance->save();
    }

    private function clockedLifecycleStatus(Attendance $attendance): string
    {
        if ($attendance->clock_out_time !== null) {
            return Attendance::STATUS_COMPLETED;
        }

        if ($attendance->clock_in_time !== null) {
            return Attendance::STATUS_ACTIVE;
        }

        return Attendance::STATUS_WAITING;
    }

    private function normalizeWorkDate(string $workDate): string
    {
        return Carbon::parse($workDate, $this->attendanceCalendarTimezone())->toDateString();
    }

    private function attendanceWorkDate(Attendance $attendance): ?string
    {
        if ($attendance->business_date !== null) {
            return Carbon::parse($attendance->business_date, $this->attendanceCalendarTimezone())->toDateString();
        }

        if ($attendance->start_time !== null) {
            return Carbon::parse($attendance->start_time, $this->attendanceCalendarTimezone())->toDateString();
        }

        return null;
    }

    private function attendanceCalendarTimezone(): string
    {
        if (function_exists('getTimeZoneBranchByRequest')) {
            $timezone = getTimeZoneBranchByRequest();
            if (is_string($timezone) && $timezone !== '') {
                return $timezone;
            }
        }

        return (string) config('app.timezone');
    }
}
