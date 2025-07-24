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

    public function present(bool $isListing = false): array
    {
        return [

            'id' => $this->attendance->id ? (string)$this->attendance->id : null,

            'user' => $this->attendance->user ? [
                'id' => $this->attendance->user->id ? (string)$this->attendance->user->id : null,
                'name' => $this->attendance->user->name,
            ] : null,

            'status' => $this->attendance->status,
            'is_late' => (int) $this->attendance->is_late,
            'is_absent' => (int) $this->attendance->is_absent,
            'work_date' => $this->attendance->created_at?->format('Y-m-d')??$this->attendance->clock_in_time->format('Y-m-d'),

            'day_status' => '',
            'professional_data' => $this->attendance->user?->professionalData ? [
                'id' => (string) $this->attendance->user->professionalData->id,
                'job_title' => $this->attendance->user->professionalData->jobTitle?->name,
                'job_code' => $this->attendance->user->professionalData->job_code,
                'department' => $this->attendance->user->professionalData->department?->name,
                'branch' => $this->attendance->user->professionalData->branch?->name,
                'management' => $this->attendance->user->professionalData->management?->name,
                'attendance_constraint' => $this->attendance->user->professionalData->attendanceConstraint?->constraint_name
            ] : null,
        ];
    }
}
