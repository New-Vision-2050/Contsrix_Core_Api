<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class AttendancePresenter extends AbstractPresenter
{
    public function __construct(private Attendance $attendance)
    {
        parent::__construct($attendance);
    }

    public function getData(): array
    {
        return [
            'id' => $this->attendance->id,
            'user_id' => $this->attendance->user_id,
            'company_id' => $this->attendance->company_id,
            
            // Clock times
            'clock_in_time' => $this->attendance->clock_in_time?->format('Y-m-d H:i:s'),
            'clock_out_time' => $this->attendance->clock_out_time?->format('Y-m-d H:i:s'),
            'break_start_time' => $this->attendance->break_start_time?->format('Y-m-d H:i:s'),
            'break_end_time' => $this->attendance->break_end_time?->format('Y-m-d H:i:s'),
            
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
            'approved_by' => $this->attendance->approved_by,
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
                'id' => $this->attendance->user->id,
                'name' => $this->attendance->user->name,
                'email' => $this->attendance->user->email,
            ] : null,
            
            'company' => $this->attendance->company ? [
                'id' => $this->attendance->company->id,
                'name' => $this->attendance->company->name,
            ] : null,
            
            'approved_by_user' => $this->attendance->approvedBy ? [
                'id' => $this->attendance->approvedBy->id,
                'name' => $this->attendance->approvedBy->name,
            ] : null,
            
            // Computed properties
            'work_date' => $this->attendance->clock_in_time?->format('Y-m-d'),
            'is_on_break' => $this->attendance->break_start_time && !$this->attendance->break_end_time,
            'is_clocked_in' => $this->attendance->clock_in_time && !$this->attendance->clock_out_time,
            'duration_formatted' => $this->formatDuration($this->attendance->total_work_hours),
            'break_duration_formatted' => $this->formatDuration($this->attendance->total_break_hours),
            'overtime_formatted' => $this->formatDuration($this->attendance->overtime_hours),
        ];
    }

    /**
     * Format duration in hours to human readable format
     */
    private function formatDuration(?float $hours): string
    {
        if (!$hours || $hours <= 0) {
            return '0h 0m';
        }

        $totalMinutes = (int) ($hours * 60);
        $hoursFormatted = intval($totalMinutes / 60);
        $minutesFormatted = $totalMinutes % 60;

        return "{$hoursFormatted}h {$minutesFormatted}m";
    }

    /**
     * Get summary data for dashboard
     */
    public function getSummaryData(): array
    {
        return [
            'id' => $this->attendance->id,
            'work_date' => $this->attendance->clock_in_time?->format('Y-m-d'),
            'clock_in_time' => $this->attendance->clock_in_time?->format('H:i'),
            'clock_out_time' => $this->attendance->clock_out_time?->format('H:i'),
            'total_work_hours' => (float) $this->attendance->total_work_hours,
            'overtime_hours' => (float) $this->attendance->overtime_hours,
            'status' => $this->attendance->status,
            'is_late' => $this->attendance->is_late,
            'is_early_departure' => $this->attendance->is_early_departure,
            'duration_formatted' => $this->formatDuration($this->attendance->total_work_hours),
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
