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
     * @param  Collection<int, string>  $userIds
     * @param  array<string, mixed>  $filters
     * @return Collection<string, array<string, mixed>>
     */
    public function buildForUsers(Collection $userIds, array $filters): Collection
    {
        $attendanceRecords = $this->getAttendanceRecordsForUsers($userIds, $filters);
        $requestedDate = $filters['start_date'] ?? null;

        return $attendanceRecords
            ->mapWithKeys(function (Attendance $attendance) use ($requestedDate): array {
                return [
                    (string) $attendance->user_id => $this->build($attendance->user, $attendance, $requestedDate),
                ];
            })
            ->filter();
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

        return Attendance::query()
            ->whereIn('id', $repIds)
            ->with(AttendanceTeamPresenter::requiredRelations())
            ->select($this->baseAttendanceSelectColumns())
            ->orderBy('start_time')
            ->get();
    }

    public function build(?User $user, ?Attendance $attendance, ?string $requestedDate = null): array
    {
        if ($attendance !== null) {
            $presented = (new AttendanceTeamPresenter($attendance))->present();
            $presented['employee_status'] = __(
                'validation.day_status.' . ($attendance->day_status ?? 'work_day')
            );

            return [
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
