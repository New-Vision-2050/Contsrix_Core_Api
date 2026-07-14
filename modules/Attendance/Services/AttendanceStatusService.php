<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use Modules\EmployeeTask\Services\EmployeeTaskPresenceService;
use Modules\User\Models\User;

class AttendanceStatusService
{
    public function __construct(
        private EmployeeTaskPresenceService $taskPresenceService,
    ) {
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
        $usersOnTask = $this->usersOnTask($userIds, $filters);

        return $attendanceRecords
            ->mapWithKeys(function (Attendance $attendance) use ($requestedDate, $usersOnTask): array {
                $hasTask = in_array((string) $attendance->user_id, $usersOnTask, true);

                return [
                    (string) $attendance->user_id => $this->build($attendance->user, $attendance, $requestedDate, $hasTask),
                ];
            })
            ->filter();
    }

    /**
     * Resolve which of the given users have a task that makes them present
     * (متواجد) within the filtered date range.
     *
     * @param  Collection<int, string>  $userIds
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    public function usersOnTask(Collection $userIds, array $filters): array
    {
        return $this->taskPresenceService->userIdsWithTaskInRange(
            $userIds,
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? ($filters['start_date'] ?? null),
        );
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

    public function build(?User $user, ?Attendance $attendance, ?string $requestedDate = null, bool $hasActiveTask = false): array
    {
        if ($attendance !== null) {
            $presented = (new AttendanceTeamPresenter($attendance))->present();
            $presented['employee_status'] = __(
                'validation.day_status.' . ($attendance->day_status ?? 'work_day')
            );

            $isAbsent = (int) ($presented['is_absent'] ?? 0) === 1
                || ($presented['status'] ?? null) === Attendance::STATUS_ABSENT;

            // Employee marked absent but has a task on this date → present (متواجد).
            if ($isAbsent && $hasActiveTask) {
                return $this->presentViaTask($user, $presented['work_date'] ?? $requestedDate);
            }

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

        return $this->syntheticAbsent($user, $requestedDate, $hasActiveTask);
    }

    public function syntheticAbsent(?User $user, ?string $requestedDate = null, bool $hasActiveTask = false): array
    {
        if ($hasActiveTask) {
            return $this->presentViaTask($user, $requestedDate);
        }

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
     * Status payload for an employee who has an assigned/active task on the date.
     * The employee is reported as present (متواجد) instead of absent.
     *
     * @return array<string, mixed>
     */
    public function presentViaTask(?User $user, ?string $requestedDate = null): array
    {
        $label = __('validation.day_status.on_task');

        return [
            'id' => null,
            'employee_status' => $label,
            'status' => 'on_task',
            'is_absent' => 0,
            'is_late' => 0,
            'is_holiday' => 0,
            'day_status' => $label,
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
