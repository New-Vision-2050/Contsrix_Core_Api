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
            'clock_in_time' => $this->attendance->clock_in_time?->format('Y-m-d H:i:s'),
            'clock_out_time' => $this->attendance->clock_out_time?->format('Y-m-d H:i:s'),

            // Calculated hours
            'total_work_hours' => (float) $this->attendance->total_work_hours,
            'total_break_hours' => (float) $this->attendance->total_break_hours,
            'overtime_hours' => (float) $this->attendance->overtime_hours,

            // Status flags
            'is_late' => $this->attendance->is_late,
            'is_early_departure' => $this->attendance->is_early_departure,
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
        ];
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
