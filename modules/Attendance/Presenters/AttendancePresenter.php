<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class AttendancePresenter extends AbstractPresenter
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
            'user_id' => $this->attendance->user_id ? (string)$this->attendance->user_id : null,
            'company_id' => $this->attendance->company_id ? (string)$this->attendance->company_id : null,

            // Clock times
            'clock_in_time' => $this->attendance->clock_in_time?->setTimezone(
                new \DateTimeZone($this->attendance->timezone ?? config('app.timezone'))
            )->format('Y-m-d H:i:s'),

            'clock_out_time' => $this->attendance->clock_out_time?->setTimezone(
                new \DateTimeZone($this->attendance->timezone ?? config('app.timezone'))
            )->format('Y-m-d H:i:s'),

            'timezone' => $this->attendance->timezone ?? null,

            // Calculated hours
            'total_work_hours' => (float) $this->attendance->total_work_hours,
            'total_break_hours' => (float) $this->attendance->total_break_hours,
            'overtime_hours' => (float) $this->attendance->overtime_hours,

            // Status flags
            'is_late' => (int) $this->attendance->is_late,
            'is_early_departure' => (int) $this->attendance->is_early_departure,
            'late_minutes' => $this->attendance->late_minutes,
            'early_departure_minutes' => $this->attendance->early_departure_minutes,

            // Status and approval
            'status' => $this->attendance->status,
            'approved_by' => $this->attendance?->approved_by ? $this->attendance?->approved_by : null,
            'approved_at' => $this->attendance->approved_at?->format('Y-m-d H:i:s'),

            // Location data
            'clock_in_location' => $this->attendance->clock_in_location,
            'clock_out_location' => $this->attendance->clock_out_location,

            // Additional info
            'notes' => $this->attendance->notes,
            'ip_address' => $this->attendance->ip_address,

            // Timestamps
            'created_at' => $this->attendance->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->attendance->updated_at?->format('Y-m-d H:i:s'),

            // Relationships
            'user' => $this->attendance->user ? [
                'id' => $this->attendance->user->id ? (string)$this->attendance->user->id : null,
                'name' => $this->attendance->user->name,
                'email' => $this->attendance->user->email,
                'birthdate' => $this->attendance->user?->companyUser?->birthdate_gregorian ?? null,
                'country' => $this->attendance->user->companyUser?->country?->name ?: null,
                'gender' => $this->attendance->user->companyUser->gender,
                'phone' => $this->attendance->user->companyUser->phone,
            ] : null,

            'company' => $this->attendance->company ? [
                'id' => $this->attendance->company->id ? (string)$this->attendance->company->id : null,
                'name' => $this->attendance->company->name,
            ] : null,

            'approved_by_user' => $this->attendance?->approvedBy ? [
                'id' => $this->attendance->approvedBy->id ? (string)$this->attendance->approvedBy->id : null,
                'name' => $this->attendance->approvedBy->name,
            ] : null,

            // Breaks data
            'breaks' => $this->formatBreaks(),

            // Computed properties
            'work_date' => $this->attendance->clock_in_time?->format('Y-m-d'),
            'is_on_break' => $this->attendance->isOnBreak(),
            'is_clocked_in' =>  (int) $this->attendance->isActive(),
            'duration_formatted' => $this->formatDuration((float) $this->attendance->total_work_hours),
            'break_duration_formatted' => $this->formatDuration((float) $this->attendance->total_break_hours),
            'overtime_formatted' => $this->formatDuration((float) $this->attendance->overtime_hours),
            // Use the result of the helper method to determine the day status.
            'day_status' => $this->getDayStatus($this->attendance->user->professionalData?->attendanceConstraint),
            'professional_data' => $this->attendance->user?->professionalData ? [
                'id' => (string) $this->attendance->user->professionalData->id,
                'job_title' => $this->attendance->user->professionalData->jobTitle?->name,
                'job_code' => $this->attendance->user->professionalData->job_code,
                'department' => $this->attendance->user->professionalData->department?->name,
                'branch' => $this->attendance->user->professionalData->branch?->name,
                'management' => $this->attendance->user->professionalData->management?->name,
                'attendance_constraint'=> $this->attendance->user->professionalData->attendanceConstraint ?[
                    'id'=> (string) $this->attendance->user->professionalData->id,
                    'constraint_name'=> $this->attendance->user->professionalData->attendanceConstraint?->constraint_name,
                    'constraint_type'=> $this->attendance->user->professionalData->attendanceConstraint?->constraint_type,
                    'constraint_config'=> $this->attendance->user->professionalData?->attendanceConstraint->constraint_config,
                ] : null,
            ] : null,
        ];
    }
 

    /**
     * Determines if the attendance day was a Work Day, Weekend, or Holiday.
     *
     * @param array $appliedConstraints The formatted array of constraints.
     * @return array An object containing the status and reason.
     */
    private function getDayStatus($appliedConstraints): array
    {
        $defaultStatus = ['status' => 'Undefined', 'reason' => 'No applicable time schedule found.'];

        if (!$this->attendance->clock_in_time) {
            return $defaultStatus;
        }
        $attendanceDate = $this->attendance->clock_in_time;

        // Find the first constraint that has time rules.
        $timeConstraint = collect($appliedConstraints)->first(function ($constraint) {
            return isset($constraint['config']['time_rules']);
        });
        if (!$timeConstraint) {
            return $defaultStatus;
        }

        $timeRules = $timeConstraint['config']['time_rules'];

        // 1. Check for specific holidays.
        foreach (($timeRules['holidays'] ?? []) as $holiday) {
            if (isset($holiday['date']) && $attendanceDate->isSameDay($holiday['date'])) {
                return ['status' => 'Holiday', 'reason' => $holiday['name']];
            }
        }

        // 2. Check the weekly schedule.
        $dayOfWeek = strtolower($attendanceDate->format('l'));
        $dayConfig = $timeRules['weekly_schedule'][$dayOfWeek] ?? null;


        if ($dayConfig) {
            return $dayConfig['enabled']
                ? ['status' => 'Work Day', 'reason' => 'Scheduled working day.']
                : ['status' => 'Weekend / Off-day', 'reason' => 'Scheduled day off.'];
        }

        return ['status' => 'Undefined', 'reason' => 'Day not defined in schedule.'];
    }
    /**
     * Format breaks data for the response
     */
    private function formatBreaks(): array
    {
        $breaks = [];

        foreach ($this->attendance->breaks as $break) {
            $breaks[] = [
                'id' => (string)$break->id,
                'start_time' => $break->start_time?->format('Y-m-d H:i:s'),
                'end_time' => $break->end_time?->format('Y-m-d H:i:s'),
                'duration_minutes' => $break->duration_minutes,
                'duration_formatted' => $break->getFormattedDuration(),
                'notes' => $break->notes,
                'is_active' => $break->isActive(),
            ];
        }

        return $breaks;
    }

    /**
     * Format hours as "Xh Ym"
     */
    private function formatDuration(float $hours): string
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);

        if ($h > 0) {
            return "{$h}h {$m}m";
        }

        return "{$m}m";
    }

    /**
     * Get summary data for dashboard
     */
    public function getSummaryData(): array
    {
        return [
            'id' => $this->attendance->id ? (string)$this->attendance->id : null,
            'work_date' => $this->attendance->clock_in_time?->format('Y-m-d'),
            'clock_in_time' => $this->attendance->clock_in_time?->format('H:i'),
            'clock_out_time' => $this->attendance->clock_out_time?->format('H:i'),
            'total_work_hours' => (float) $this->attendance->total_work_hours,
            'overtime_hours' => (float) $this->attendance->overtime_hours,
            'status' => $this->attendance->status,
            'is_late' => $this->attendance->is_late,
            'is_early_departure' => $this->attendance->is_early_departure,
            'duration_formatted' => $this->formatDuration((float) $this->attendance->total_work_hours),
        ];
    }

    /**
     * Get minimal data for reports
     */
    public function getReportData(): array
    {
        return [
            'user_name' => $this->attendance->user?->name,
            'work_date' => $this->attendance->clock_in_time?->format('Y-m-d'),
            'clock_in_time' => $this->attendance->clock_in_time?->format('H:i:s'),
            'clock_out_time' => $this->attendance->clock_out_time?->format('H:i:s'),
            'total_work_hours' => (float) $this->attendance->total_work_hours,
            'overtime_hours' => (float) $this->attendance->overtime_hours,
            'break_hours' => (float) $this->attendance->total_break_hours,
            'is_late' => $this->attendance->is_late ? 'Yes' : 'No',
            'late_minutes' => $this->attendance->late_minutes,
            'is_early_departure' => $this->attendance->is_early_departure ? 'Yes' : 'No',
            'early_departure_minutes' => $this->attendance->early_departure_minutes,
            'status' => ucfirst($this->attendance->status),
        ];
    }

}
