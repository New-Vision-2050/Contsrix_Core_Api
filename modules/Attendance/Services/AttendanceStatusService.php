<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use Modules\User\Models\User;

class AttendanceStatusService
{
    /**
     * @param  iterable<int, Attendance>  $attendances
     * @return list<array<string, mixed>>
     */
    public function presentTeamAttendances(iterable $attendances): array
    {
        return collect($attendances)
            ->values()
            ->map(static fn (Attendance $attendance): array => (new AttendanceTeamPresenter($attendance))->present())
            ->all();
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @param  array<string, mixed>  $filters
     * @return Collection<string, array<string, mixed>>
     */
    public function buildForUsers(Collection $userIds, array $filters): Collection
    {
        $attendanceRecords = $this->getAttendanceRecordsForUsers($userIds, $filters);
        $requestedDate = $filters['start_date'] ?? null;

        return $attendanceRecords
            ->mapWithKeys(fn (Attendance $attendance): array => [
                (string) $attendance->user_id => $this->build($attendance->user, $attendance, $requestedDate),
            ])
            ->filter();
    }

    /**
     * Build list-ready attendance statuses while keeping the status rules inside
     * the attendance module.
     *
     * @param  Collection<string, User|null>  $usersByKey
     * @return Collection<string, array<string, mixed>>
     */
    public function buildDailyListForUsersByKey(Collection $usersByKey, string $attendanceDate): Collection
    {
        $usersByKey = $usersByKey->mapWithKeys(
            fn (?User $user, string|int $key): array => [(string) $key => $user]
        );

        $userIds = $usersByKey
            ->filter()
            ->map(fn (User $user): string => (string) $user->id)
            ->unique()
            ->values();

        $filters = [
            'start_date' => $attendanceDate,
            'end_date' => $attendanceDate,
        ];

        $attendanceByUserId = $this->buildForUsers($userIds, $filters);

        return $usersByKey->mapWithKeys(function (?User $user, string $key) use ($attendanceByUserId, $attendanceDate): array {
            $userId = $user?->id ? (string) $user->id : null;

            $attendance = $userId !== null && $attendanceByUserId->has($userId)
                ? $attendanceByUserId->get($userId)
                : $this->syntheticAbsent($user, $attendanceDate);

            return [$key => $this->formatForList($attendance)];
        });
    }

    public function formatForList(array $attendance): array
    {
        [$code, $label] = $this->listDisplay($attendance);

        return [
            'id' => $attendance['id'] ?? null,
            'code' => $code,
            'label' => $label,
            'employee_status' => $attendance['employee_status'] ?? null,
            'status' => $attendance['status'] ?? null,
            'is_absent' => (int) ($attendance['is_absent'] ?? 0),
            'is_late' => (int) ($attendance['is_late'] ?? 0),
            'is_holiday' => (int) ($attendance['is_holiday'] ?? 0),
            'day_status' => $attendance['day_status'] ?? null,
            'attendance_constraint_id' => $attendance['attendance_constraint_id'] ?? null,
            'attendance_constraint' => $attendance['attendance_constraint'] ?? null,
            'work_date' => $attendance['work_date'] ?? null,
            'clock_in_time' => $attendance['clock_in_time'] ?? null,
        ];
    }

    private function listDisplay(array $attendance): array
    {
        $status = $attendance['status'] ?? null;
        $isAbsent = (int) ($attendance['is_absent'] ?? 0) === 1;
        $isHoliday = (int) ($attendance['is_holiday'] ?? 0) === 1;
        $clockInTime = $attendance['clock_in_time'] ?? null;

        if ($isHoliday || $status === Attendance::STATUS_HOLIDAY) {
            return ['holiday', 'اجازه'];
        }

        if ($status === Attendance::STATUS_WAITING || ($status !== Attendance::STATUS_ABSENT && $clockInTime === null && ! $isAbsent)) {
            return ['required_attendance', 'مطلوب للحضور'];
        }

        if ($isAbsent || $status === Attendance::STATUS_ABSENT) {
            return ['absent', 'غائب'];
        }

        return ['present', 'حاضر'];
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Attendance>
     */
    private function getAttendanceRecordsForUsers(Collection $userIds, array $filters): Collection
    {
        $ids = $userIds
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $filterInput = $filters;
        $startDate = $filterInput['start_date'] ?? null;
        $endDate = $filterInput['end_date'] ?? null;
        unset($filterInput['start_date'], $filterInput['end_date']);

        $calendarTz = $this->attendanceFilterCalendarTimezone();

        $base = Attendance::query()
            ->filter($filterInput)
            ->whereIn('user_id', $ids->all())
            ->when(
                $startDate !== null && $startDate !== '',
                fn ($query) => $query->where(
                    'start_time',
                    '>=',
                    Carbon::parse((string) $startDate, $calendarTz)->startOfDay()->utc()
                )
            )
            ->when(
                $endDate !== null && $endDate !== '',
                fn ($query) => $query->where(
                    'start_time',
                    '<',
                    Carbon::parse((string) $endDate, $calendarTz)->addDay()->startOfDay()->utc()
                )
            )
            ->whereNotNull('business_date');

        $repIds = $base->clone()
            ->selectRaw("COALESCE(MIN(CASE WHEN clock_in_time IS NOT NULL THEN id END), MIN(id)) AS rep_id")
            ->groupBy('user_id', 'business_date')
            ->orderByRaw('MIN(start_time) ASC')
            ->pluck('rep_id');

        if ($repIds->isEmpty()) {
            return collect();
        }

        $columns = $this->baseAttendanceSelectColumns();
        $relations = AttendanceTeamPresenter::requiredRelations();

        return $repIds
            ->chunk(100)
            ->flatMap(function (Collection $chunk) use ($columns, $relations): Collection {
                return Attendance::query()
                    ->whereIn('id', $chunk->all())
                    ->with($relations)
                    ->select($columns)
                    ->get();
            })
            ->sortBy('start_time')
            ->values();
    }

    public function build(?User $user, ?Attendance $attendance, ?string $requestedDate = null): array
    {
        if ($attendance !== null) {
            $presented = (new AttendanceTeamPresenter($attendance))->present();
            $presented['employee_status'] = __(
                'validation.day_status.' . ($attendance->day_status ?? 'work_day')
            );

            return [
                'id' => $presented['id'] ?? null,
                'employee_status' => $presented['employee_status'],
                'status' => $presented['status'] ?? null,
                'is_absent' => (int) ($presented['is_absent'] ?? 0),
                'is_late' => (int) ($presented['is_late'] ?? 0),
                'is_holiday' => (int) ($presented['is_holiday'] ?? 0),
                'day_status' => $presented['day_status'] ?? '',
                ...$this->attendanceConstraintFields($user),
                'work_date' => $presented['work_date'] ?? $requestedDate,
                'clock_in_time' => $presented['clock_in_time'] ?? null,
            ];
        }

        return $this->syntheticAbsent($user, $requestedDate);
    }

    public function syntheticAbsent(?User $user, ?string $requestedDate = null): array
    {
        return [
            'id' => null,
            'employee_status' => 'مطلوب للحضور',
            'status' => Attendance::STATUS_ABSENT,
            'is_absent' => 1,
            'is_late' => 0,
            'is_holiday' => 0,
            'day_status' => 'غائب',
            ...$this->attendanceConstraintFields($user),
            'work_date' => $requestedDate,
            'clock_in_time' => null,
        ];
    }

    /**
     * @return array{attendance_constraint_id: ?string, attendance_constraint: ?array}
     */
    private function attendanceConstraintFields(?User $user): array
    {
        $constraint = $user?->userProfessionalData?->attendanceConstraint;

        return [
            'attendance_constraint_id' => $constraint?->id ? (string) $constraint->id : null,
            'attendance_constraint' => $constraint ? [
                'id' => (string) $constraint->id,
                'constraint_name' => $constraint->constraint_name,
            ] : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function baseAttendanceSelectColumns(): array
    {
        return [
            'id',
            'user_id',
            'company_id',
            'status',
            'is_late',
            'is_absent',
            'is_holiday',
            'day_status',
            'clock_in_time',
            'clock_out_time',
            'start_time',
            'business_date',
            'overtime_hours',
            'clock_in_location',
            'location_tracking',
        ];
    }

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
}
