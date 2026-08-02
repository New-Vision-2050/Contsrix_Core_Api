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

        $attendanceRowsByUserId = $this->getDailyAttendanceRowsForUsers(
            $usersByKey
                ->filter()
                ->map(fn (User $user): string => (string) $user->id)
                ->unique()
                ->values(),
            $workDate
        )->groupBy(fn (Attendance $attendance): string => (string) $attendance->user_id);

        return $usersByKey->mapWithKeys(function (?User $user, string $key) use ($attendanceRowsByUserId, $workDate): array {
            $rows = $user?->id ? $attendanceRowsByUserId->get((string) $user->id, collect()) : collect();
            $attendance = $this->requiredHolidayRepresentative($rows);

            return [$key => $this->requiredHolidayPayload($attendance, $workDate)];
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

            return $this->requiredHolidayPayload(
                $this->requiredHolidayRepresentative($rows),
                $workDate
            );
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
