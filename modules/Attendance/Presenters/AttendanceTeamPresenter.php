<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class AttendanceTeamPresenter extends AbstractPresenter
{
    private Attendance $attendance;

    public function __construct(
        Attendance $attendance,
        private readonly bool $hasActiveTask = false,
    ) {
        $this->attendance = $attendance;
    }

    public static function requiredRelations(): array
    {
        return [
            'user.company',
            'user.userProfessionalData.jobTitle',
            'user.userProfessionalData.department',
            'user.userProfessionalData.branch',
            'user.userProfessionalData.management',
            'user.userProfessionalData.attendanceConstraint',
            'appliedAttendanceConstraint',
        ];
    }

    public function present(bool $isListing = false): array
    {
        $isAbsent = (int) $this->attendance->is_absent === 1
            || ($this->attendance->status ?? null) === Attendance::STATUS_ABSENT;

        // Same rule as calendar / AttendanceStatusService: absent + task → متواجد.
        if ($isAbsent && $this->hasActiveTask) {
            $label = $this->onTaskLabel();

            return [
                'id'      => $this->attendance->id ? (string) $this->attendance->id : null,
                'user_id' => $this->attendance->user_id ? (string) $this->attendance->user_id : null,
                'user' => $this->presentUser(),
                'status'     => 'on_task',
                'is_late'    => 0,
                'is_absent'  => 0,
                'is_holiday' => 0,
                'start_time' => $this->attendance->start_time,
                'work_date'  => $this->resolveWorkDate(),
                'day_status' => $label,
                'clock_in_time' => null,
                'attendance_constraint_id' => $this->attendance->user?->userProfessionalData?->attendanceConstraint?->id,
                'attendance_constraint'    => $this->presentAppliedConstraint(),
                'professional_data' => $this->presentProfessionalData(),
            ];
        }

        return [
            'id'      => $this->attendance->id ? (string) $this->attendance->id : null,
            'user_id' => $this->attendance->user_id ? (string) $this->attendance->user_id : null,
            'user' => $this->presentUser(),
            'status'     => $this->attendance->status,
            'is_late'    => (int) $this->attendance->is_late,
            'is_absent'  => (int) $this->attendance->is_absent,
            'is_holiday' => (int) $this->attendance->is_holiday,
            'start_time' => $this->attendance->start_time,
            'work_date'  => $this->resolveWorkDate(),
            'day_status'     => $this->translateDayStatus($this->attendance->day_status ?? 'work_day'),
            'clock_in_time'  => $this->attendance->clock_in_time,
            'attendance_constraint_id' => $this->attendance->user?->userProfessionalData?->attendanceConstraint?->id,
            'attendance_constraint'    => $this->presentAppliedConstraint(),
            'professional_data' => $this->presentProfessionalData(),
        ];
    }

    private function onTaskLabel(): string
    {
        try {
            $label = __('validation.day_status.on_task');
            if (is_string($label) && $label !== '' && ! str_starts_with($label, 'validation.')) {
                return $label;
            }
        } catch (\Throwable) {
        }

        return 'متواجد';
    }

    private function translateDayStatus(string $dayStatus): string
    {
        try {
            $label = __('validation.day_status.'.$dayStatus);

            return is_string($label) ? $label : '';
        } catch (\Throwable) {
            return $dayStatus;
        }
    }

    private function resolveWorkDate(): ?string
    {
        if ($this->attendance->business_date) {
            $d = $this->attendance->business_date;

            return $d instanceof \Carbon\Carbon ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
        }

        if ($this->attendance->start_time) {
            return \Carbon\Carbon::parse($this->attendance->start_time)->format('Y-m-d');
        }

        if ($this->attendance->clock_in_time) {
            return \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d');
        }

        return null;
    }

    private function presentUser(): ?array
    {
        if (! $this->attendance->user) {
            return null;
        }

        return [
            'id'           => $this->attendance->user->id ? (string) $this->attendance->user->id : null,
            'name'         => $this->attendance->user->name,
            'email'        => $this->attendance->user->email,
            'company_id'   => $this->attendance->user->company_id,
            'company_name' => $this->attendance->user->company?->name,
        ];
    }

    private function presentAppliedConstraint(): ?array
    {
        if (! $this->attendance->appliedAttendanceConstraint
            || ! is_array($this->attendance->appliedAttendanceConstraint->constraint_snapshot)) {
            return null;
        }

        return [
            'id'              => (string) ($this->attendance->appliedAttendanceConstraint->constraint_snapshot['id'] ?? ''),
            'constraint_name' => $this->attendance->appliedAttendanceConstraint->constraint_snapshot['constraint_name'] ?? '',
        ];
    }

    private function presentProfessionalData(): ?array
    {
        $pd = $this->attendance->user?->userProfessionalData;
        if (! $pd) {
            return null;
        }

        return [
            'id'                  => (string) $pd->id,
            'job_title'           => $pd->jobTitle?->name,
            'job_code'            => $pd->job_code,
            'department'          => $pd->department?->name,
            'branch'              => $pd->branch?->name,
            'management'          => $pd->management?->name,
            'attendance_constraint' => $pd->attendanceConstraint
                ? [
                    'id'              => (string) $pd->attendanceConstraint->id,
                    'constraint_name' => $pd->attendanceConstraint->constraint_name,
                ]
                : null,
            'user_id' => (string) $pd->user_id,
        ];
    }
}
