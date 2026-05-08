<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class AttendanceTeamPresenter extends AbstractPresenter
{
    private Attendance $attendance;
    public function __construct(Attendance $attendance)
    {
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
        return [
            'id'      => $this->attendance->id ? (string) $this->attendance->id : null,
            'user_id' => $this->attendance->user_id ? (string) $this->attendance->user_id : null,

            'user' => $this->attendance->user ? [
                'id'           => $this->attendance->user->id ? (string) $this->attendance->user->id : null,
                'name'         => $this->attendance->user->name,
                'email'        => $this->attendance->user->email,
                'company_id'   => $this->attendance->user->company_id,
                'company_name' => $this->attendance->user->company?->name,
            ] : null,

            'status'     => $this->attendance->status,
            'is_late'    => (int) $this->attendance->is_late,
            'is_absent'  => (int) $this->attendance->is_absent,
            'is_holiday' => (int) $this->attendance->is_holiday,
            'start_time' => $this->attendance->start_time,
            'work_date'  => $this->attendance->start_time
                ? \Carbon\Carbon::parse($this->attendance->start_time)->format('Y-m-d')
                : ($this->attendance->clock_in_time
                    ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d')
                    : null),
            'day_status'     => __('validation.day_status.' . ($this->attendance->day_status ?? 'work_day')) ?? '',
            'clock_in_time'  => $this->attendance->clock_in_time,

            'attendance_constraint_id' => $this->attendance->user?->userProfessionalData?->attendanceConstraint?->id,
            'attendance_constraint'    => $this->attendance->appliedAttendanceConstraint
                && is_array($this->attendance->appliedAttendanceConstraint->constraint_snapshot)
                    ? [
                        'id'              => (string) ($this->attendance->appliedAttendanceConstraint->constraint_snapshot['id'] ?? ''),
                        'constraint_name' => $this->attendance->appliedAttendanceConstraint->constraint_snapshot['constraint_name'] ?? '',
                    ]
                    : null,

            'professional_data' => $this->attendance->user?->userProfessionalData ? [
                'id'                  => (string) $this->attendance->user->userProfessionalData->id,
                'job_title'           => $this->attendance->user->userProfessionalData->jobTitle?->name,
                'job_code'            => $this->attendance->user->userProfessionalData->job_code,
                'department'          => $this->attendance->user->userProfessionalData->department?->name,
                'branch'              => $this->attendance->user->userProfessionalData->branch?->name,
                'management'          => $this->attendance->user->userProfessionalData->management?->name,
                'attendance_constraint' => $this->attendance->user->userProfessionalData->attendanceConstraint
                    ? [
                        'id'              => (string) $this->attendance->user->userProfessionalData->attendanceConstraint->id,
                        'constraint_name' => $this->attendance->user->userProfessionalData->attendanceConstraint->constraint_name,
                    ]
                    : null,
                'user_id' => (string) $this->attendance->user->userProfessionalData->user_id,
            ] : null,
        ];
    }
}
