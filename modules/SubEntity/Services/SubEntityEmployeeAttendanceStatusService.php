<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
    public function buildRequiredHolidayStatusesForUsersByKey(Collection $usersByKey, string $dateFrom, ?string $dateTo = null): Collection
    {
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $workDates = $this->workDates($dateFrom, $dateTo);
        $usersByKey = $usersByKey->mapWithKeys(
            fn (?User $user, string|int $key): array => [(string) $key => $user]
        );

        $attendanceRowsByUserIdAndDate = $this->getDailyAttendanceRowsForUsers(
            $usersByKey
                ->filter()
                ->map(fn (User $user): string => (string) $user->id)
                ->unique()
                ->values(),
            $dateFrom,
            $dateTo
        )
            ->groupBy(fn (Attendance $attendance): string => (string) $attendance->user_id)
            ->map(fn (Collection $rows): Collection => $rows->groupBy(
                fn (Attendance $attendance): string => $this->attendanceWorkDate($attendance)
            ));

        return $usersByKey->mapWithKeys(function (?User $user, string $key) use ($attendanceRowsByUserIdAndDate, $workDates, $dateFrom, $dateTo): array {
            $rowsByDate = $user?->id ? $attendanceRowsByUserIdAndDate->get((string) $user->id, collect()) : collect();
            $statuses = collect($workDates)
                ->map(function (string $workDate) use ($rowsByDate): array {
                    $rows = $rowsByDate->get($workDate, collect());

                    return $this->requiredHolidayPayload(
                        $this->requiredHolidayRepresentative($rows),
                        $workDate
                    );
                })
                ->values()
                ->all();

            return [$key => $this->rangePayload($statuses, $dateFrom, $dateTo)];
        });
    }

    public function setDailyRequiredHolidayStatus(User $user, string $dateFrom, ?string $dateTo, string $status): array
    {
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);

        if (! in_array($status, [self::STATUS_HOLIDAY, self::STATUS_REQUIRED_ATTENDANCE], true)) {
            throw new \InvalidArgumentException('Invalid daily attendance status.');
        }

        return DB::transaction(function () use ($user, $dateFrom, $dateTo, $status): array {
            $statuses = collect($this->workDates($dateFrom, $dateTo))
                ->map(function (string $workDate) use ($user, $status): array {
                    $rows = $this->getDailyAttendanceRowsForUser((string) $user->id, $workDate);

                    if ($rows->isEmpty()) {
                        $rows = collect([$this->createDailyRequiredHolidayAttendance($user, $workDate, $status)]);
                    }

                    $rows->each(fn (Attendance $attendance) => $this->applyRequiredHolidayStatus($attendance, $status, $workDate));

                    $rows = $this->getDailyAttendanceRowsForUser((string) $user->id, $workDate);

                    return $this->requiredHolidayPayload(
                        $this->requiredHolidayRepresentative($rows),
                        $workDate
                    );
                })
                ->values()
                ->all();

            return $this->rangePayload($statuses, $dateFrom, $dateTo);
        });
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, Attendance>
     */
    private function getDailyAttendanceRowsForUsers(Collection $userIds, string $dateFrom, ?string $dateTo = null): Collection
    {
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);
        $ids = $userIds
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Attendance::query()
            ->whereIn('user_id', $ids->all())
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('business_date', [$dateFrom, $dateTo])
                    ->orWhere(function ($query) use ($dateFrom, $dateTo) {
                        $query->whereDate('start_time', '>=', $dateFrom)
                            ->whereDate('start_time', '<=', $dateTo);
                    });
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

    private function requiredHolidayPayload(?Attendance $attendance, string $workDate): array
    {
        $isHoliday = $attendance !== null && $this->isHolidayAttendance($attendance);

        return [
            'attendance_id' => $attendance?->id ? (string) $attendance->id : null,
            'attendance_work_date' => $workDate,
            'attendance_status_code' => $isHoliday ? self::STATUS_HOLIDAY : self::STATUS_REQUIRED_ATTENDANCE,
            'attendance_status_label' => $isHoliday ? self::LABEL_HOLIDAY : self::LABEL_REQUIRED_ATTENDANCE,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $statuses
     */
    private function rangePayload(array $statuses, string $dateFrom, string $dateTo): array
    {
        $firstStatus = $statuses[0] ?? $this->requiredHolidayPayload(null, $dateFrom);

        return [
            ...$firstStatus,
            'attendance_date_from' => $dateFrom,
            'attendance_date_to' => $dateTo,
            'attendance_statuses' => $statuses,
        ];
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

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizeDateRange(string $dateFrom, ?string $dateTo = null): array
    {
        $from = Carbon::parse($dateFrom, $this->attendanceCalendarTimezone())->toDateString();
        $to = Carbon::parse($dateTo ?: $from, $this->attendanceCalendarTimezone())->toDateString();

        if ($to < $from) {
            throw new \InvalidArgumentException('date_to must be after or equal to date_from.');
        }

        return [$from, $to];
    }

    /**
     * @return list<string>
     */
    private function workDates(string $dateFrom, string $dateTo): array
    {
        return collect(CarbonPeriod::create($dateFrom, $dateTo))
            ->map(fn (Carbon $date): string => $date->toDateString())
            ->values()
            ->all();
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
