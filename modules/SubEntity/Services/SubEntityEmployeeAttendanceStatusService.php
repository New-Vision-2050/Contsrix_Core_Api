<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
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

        $userIds = $usersByKey
            ->filter()
            ->map(fn (User $user): string => (string) $user->id)
            ->unique()
            ->values();

        $attendanceRowsByUserId = $this->getDailyAttendanceRowsForUsers($userIds, $workDate)
            ->groupBy(fn (Attendance $attendance): string => (string) $attendance->user_id);

        $holidayUserIds = $attendanceRowsByUserId
            ->filter(function (Collection $rows): bool {
                $attendance = $this->requiredHolidayRepresentative($rows);

                return $attendance !== null && $this->isHolidayAttendance($attendance);
            })
            ->keys()
            ->values();

        $holidayRangesByUserId = $this->holidayRangesForUsers($holidayUserIds, $workDate);

        return $usersByKey->mapWithKeys(function (?User $user, string $key) use ($attendanceRowsByUserId, $holidayRangesByUserId, $workDate): array {
            $userId = $user?->id ? (string) $user->id : null;
            $rows = $userId ? $attendanceRowsByUserId->get($userId, collect()) : collect();
            $attendance = $this->requiredHolidayRepresentative($rows);

            return [
                $key => $this->requiredHolidayPayload(
                    $attendance,
                    $workDate,
                    $userId ? $holidayRangesByUserId->get($userId) : null
                ),
            ];
        });
    }

    public function setDailyRequiredHolidayStatus(User $user, string $workDate, string $status): array
    {
        $workDate = $this->normalizeWorkDate($workDate);

        if (! in_array($status, [self::STATUS_HOLIDAY, self::STATUS_REQUIRED_ATTENDANCE], true)) {
            throw new \InvalidArgumentException('Invalid daily attendance status.');
        }

        return DB::transaction(function () use ($user, $workDate, $status): array {
            $rows = $this->getDailyAttendanceRowsForUser((string) $user->id, $workDate);

            if ($rows->isEmpty()) {
                $rows = collect([$this->createDailyRequiredHolidayAttendance($user, $workDate, $status)]);
            }

            $rows->each(fn (Attendance $attendance) => $this->applyRequiredHolidayStatus($attendance, $status, $workDate));

            $rows = $this->getDailyAttendanceRowsForUser((string) $user->id, $workDate);
            $attendance = $this->requiredHolidayRepresentative($rows);
            $holidayRange = $attendance !== null && $this->isHolidayAttendance($attendance)
                ? $this->holidayRangesForUsers(collect([(string) $user->id]), $workDate)->get((string) $user->id)
                : null;

            return $this->requiredHolidayPayload($attendance, $workDate, $holidayRange);
        });
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

    /**
     * @param  array{date_from: string, date_to: string}|null  $holidayRange
     */
    private function requiredHolidayPayload(?Attendance $attendance, string $workDate, ?array $holidayRange = null): array
    {
        $isHoliday = $attendance !== null && $this->isHolidayAttendance($attendance);

        return [
            'attendance_id' => $attendance?->id ? (string) $attendance->id : null,
            'attendance_work_date' => $workDate,
            'attendance_status_code' => $isHoliday ? self::STATUS_HOLIDAY : self::STATUS_REQUIRED_ATTENDANCE,
            'attendance_status_label' => $isHoliday ? self::LABEL_HOLIDAY : self::LABEL_REQUIRED_ATTENDANCE,
            'attendance_date_from' => $isHoliday ? ($holidayRange['date_from'] ?? $workDate) : null,
            'attendance_date_to' => $isHoliday ? ($holidayRange['date_to'] ?? $workDate) : null,
        ];
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<string, array{date_from: string, date_to: string}>
     */
    private function holidayRangesForUsers(Collection $userIds, string $workDate): Collection
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
            ->where(function ($query) {
                $query->where('is_holiday', 1)
                    ->orWhere('status', Attendance::STATUS_HOLIDAY)
                    ->orWhere('day_status', self::STATUS_HOLIDAY);
            })
            ->where(function ($query) {
                $query->whereNotNull('business_date')
                    ->orWhereNotNull('start_time');
            })
            ->get(['user_id', 'business_date', 'start_time'])
            ->groupBy(fn (Attendance $attendance): string => (string) $attendance->user_id)
            ->map(function (Collection $rows) use ($workDate): ?array {
                $dateSet = $rows
                    ->map(fn (Attendance $attendance): string => $this->attendanceWorkDate($attendance))
                    ->unique()
                    ->flip();

                if (! $dateSet->has($workDate)) {
                    return null;
                }

                $dateFrom = $workDate;
                while ($dateSet->has(Carbon::parse($dateFrom, $this->attendanceCalendarTimezone())->subDay()->toDateString())) {
                    $dateFrom = Carbon::parse($dateFrom, $this->attendanceCalendarTimezone())->subDay()->toDateString();
                }

                $dateTo = $workDate;
                while ($dateSet->has(Carbon::parse($dateTo, $this->attendanceCalendarTimezone())->addDay()->toDateString())) {
                    $dateTo = Carbon::parse($dateTo, $this->attendanceCalendarTimezone())->addDay()->toDateString();
                }

                return [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ];
            })
            ->filter();
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
                ? 'Manual sub-entity status set to holiday.'
                : 'Manual sub-entity status set to required attendance.',
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

    private function attendanceWorkDate(Attendance $attendance): string
    {
        if ($attendance->business_date !== null) {
            return Carbon::parse($attendance->business_date, $this->attendanceCalendarTimezone())->toDateString();
        }

        return Carbon::parse($attendance->start_time, $this->attendanceCalendarTimezone())->toDateString();
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
