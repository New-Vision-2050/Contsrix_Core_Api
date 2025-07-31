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
        // Get all tracking points, or an empty array if none.
        $trackingPoints = $this->attendance->location_tracking ?? [];

        // Find the most recent tracking point.
        $latestPoint = !empty($trackingPoints) ? end($trackingPoints) : null;
        return [

            'id' => $this->attendance->id ? (string)$this->attendance->id : null,

            'user' => $this->attendance->user ? [
                'id' => $this->attendance->user->id ? (string)$this->attendance->user->id : null,
                'name' => $this->attendance->user->name,
                'company_id'=> $this->attendance->user->company_id,
                'company_name' => $this->attendance->user->company->name,
            ] : null,

            'status' => $this->attendance->status,
            'is_late' => (int) $this->attendance->is_late,
            'is_absent' => (int) $this->attendance->is_absent,
            'start_time' => $this->attendance->start_time ,
            'work_date' => $this->attendance->start_time
                ? \Carbon\Carbon::parse($this->attendance->start_time)->format('Y-m-d')
                : (\Carbon\Carbon::parse($this->attendance->clock_in_time))->format('Y-m-d'),
            'day_status' => __('validation.day_status.'.$this->attendance->day_status??'work_day') ?? '',
            'clock_in_time' => $this->attendance->clock_in_time ? $this->attendance->clock_in_time->format('Y-m-d H:i:s') : null,
            'latest_location' => $latestPoint ? [
                'latitude'  => (float) $latestPoint['latitude'],
                'longitude' => (float) $latestPoint['longitude'],
                'timestamp' => $latestPoint['timestamp'],
                'accuracy'  => (float) $latestPoint['accuracy'],
            ] : ($this->attendance->clock_in_location ? [
                'latitude'  => $this->attendance->clock_in_location['latitude'],
                'longitude' => $this->attendance->clock_in_location['longitude'],
                'timestamp' => $this->attendance->clock_in_time->format('Y-m-d H:i:s'),
                'accuracy'  => 10,
            ] : null),
            'attendance_constraint_id' => $this->attendance->user?->userProfessionalData?->attendanceConstraint?->id,

            'professional_data' => $this->attendance->user?->userProfessionalData ? [
                'id' => (string) $this->attendance->user->userProfessionalData->id,
                'job_title' => $this->attendance->user->userProfessionalData->jobTitle?->name,
                'job_code' => $this->attendance->user->userProfessionalData->job_code,
                'department' => $this->attendance->user->userProfessionalData->department?->name,
                'branch' => $this->attendance->user->userProfessionalData->branch?->name,
                'management' => $this->attendance->user->userProfessionalData->management?->name,
                'attendance_constraint' => $this->attendance->user->userProfessionalData->attendanceConstraint?->constraint_name,
                'user_id' => (string) $this->attendance->user->userProfessionalData->user_id,
            ] : null,
        ];
    }
}
