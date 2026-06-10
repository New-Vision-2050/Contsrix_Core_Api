<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\HoursFormatter;

class AttendancePresenter extends AbstractPresenter
{
    private Attendance $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public static function requiredRelations(): array
    {
        return [
            'user.companyUser.country',
            'user.professionalData.jobTitle',
            'user.professionalData.department',
            'user.professionalData.branch',
            'user.professionalData.management',
            'user.professionalData.attendanceConstraint',
            'company',
            'approvedBy',
            'breaks',
            'appliedAttendanceConstraint',
        ];
    }

    public function present(bool $isListing = false): array
    {

        return [

            'id' => $this->attendance->id ? (string)$this->attendance->id : null,
            'user_id' => $this->attendance->user_id ? (string)$this->attendance->user_id : null,
            'company_id' => $this->attendance->company_id ? (string)$this->attendance->company_id : null,

            // Clock times
            'clock_in_time' => $this->attendance->clock_in_time ?
                (\Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d H:i:s')) : null,

            'clock_out_time' => $this->attendance->clock_out_time ?
                (\Carbon\Carbon::parse($this->attendance->clock_out_time)->format('Y-m-d H:i:s')) : null,
            'start_time' => $this->attendance->start_time,
            'end_time' => $this->attendance->end_time,


            'timezone' => $this->attendance->timezone ?? null,

            // Calculated hours — always shipped as HH:MM strings (single source of truth:
            // HoursFormatter). The DB column is DECIMAL(8,2); keeping the raw decimal in the
            // payload led to the FE rendering values like "09:93" (a corrupt minute count) by
            // splitting the dot. Reports + history + operational endpoints all use HH:MM now.
//            'total_work_hours' => HoursFormatter::fromDecimalString($this->attendance->total_work_hours),
//            'total_break_hours' => HoursFormatter::fromDecimalString($this->attendance->total_break_hours),
//            'overtime_hours' => HoursFormatter::fromDecimalString($this->attendance->overtime_hours),


            'total_work_hours' => HoursFormatter::fromHours($this->computeLiveWorkHours()),
            'total_break_hours' => HoursFormatter::fromHours($this->computeLiveBreakHours()),
            'overtime_hours' => HoursFormatter::fromDecimalString($this->attendance->overtime_hours),

            // Status flags
            'is_late' => (int)$this->attendance->is_late,
            'is_absent' => (int)$this->attendance->is_absent,
            'is_holiday' => (int)$this->attendance->is_holiday,
            'is_early_departure' => (int)$this->attendance->is_early_departure,
            // late_minutes / early_departure_minutes are stored as raw int minutes; format to
            // HH:MM here so a value of 93 minutes appears as "01:33" instead of "00:93".
//            'late_minutes' => HoursFormatter::fromMinutes((int)$this->attendance->late_minutes),
//            'early_departure_minutes' => HoursFormatter::fromMinutes((int)$this->attendance->early_departure_minutes),


            'late_minutes' => (int)$this->attendance->late_minutes,
            'early_departure_minutes' => (int)$this->attendance->early_departure_minutes,

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
                'gender' => __('validation.' . $this->attendance->user->companyUser->gender),
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
            'work_date' => $this->attendance->clock_in_time ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d') : null,
            'is_on_break' => $this->attendance->isOnBreak(),
            'is_clocked_in' => (int)$this->attendance->isActive(),
            // Backwards-compatible aliases ("Xh Ym" style) for clients that already rely on them.
            'duration_formatted' => $this->formatDurationHm($this->computeLiveWorkHours()),
            'break_duration_formatted' => $this->formatDurationHm($this->computeLiveBreakHours()),
            'overtime_formatted' => $this->formatDurationHm((float)$this->attendance->overtime_hours),
            'day_status' => __('validation.day_status.' . $this->attendance->day_status),
            'professional_data' => $this->attendance->user?->professionalData ? [
                'id' => (string)$this->attendance->user->professionalData->id,
                'job_title' => $this->attendance->user?->professionalData?->jobTitle?->name,
                'job_code' => $this->attendance->user?->professionalData?->job_code,
                'department' => $this->attendance->user->professionalData->department?->name,
                'branch' => $this->attendance->user->professionalData->branch?->name,
                'management' => $this->attendance->user?->professionalData?->management?->name,
                'attendance_constraint' => $this->attendance->user?->professionalData?->attendanceConstraint ? [
                    'id' => (string)$this->attendance->user?->professionalData?->id,
                    'constraint_name' => $this->attendance->user?->professionalData?->attendanceConstraint?->constraint_name,
                    'constraint_type' => $this->attendance->user?->professionalData?->attendanceConstraint?->constraint_type,
                    'constraint_config' => $this->attendance->user?->professionalData?->attendanceConstraint?->constraint_config,
                ] : null,
            ] : null,
        ];
    }


    /**
     * For active sessions (clocked in, not yet clocked out) return real-time elapsed
     * work hours so the status endpoint shows meaningful progress instead of 0.00.
     * For completed sessions return the persisted value calculated at clock-out.
     */
    private function computeLiveWorkHours(): float
    {
        if ($this->attendance->clock_out_time !== null || $this->attendance->clock_in_time === null) {
            return (float) $this->attendance->total_work_hours;
        }

        $timezone      = $this->attendance->timezone ?: config('app.timezone') ?: 'UTC';
        $clockIn       = \Carbon\CarbonImmutable::parse($this->attendance->clock_in_time, $timezone);
        $now           = \Carbon\CarbonImmutable::now($timezone);
        $grossMinutes  = max(0, (int) $clockIn->diffInMinutes($now, false));

        $completedBreakMinutes = (int) $this->attendance->breaks()
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        $activeBreak = $this->attendance->activeBreak();
        $activeBreakMinutes = 0;
        if ($activeBreak && $activeBreak->start_time) {
            $activeBreakStart   = \Carbon\CarbonImmutable::parse($activeBreak->start_time, $timezone);
            $activeBreakMinutes = max(0, (int) $activeBreakStart->diffInMinutes($now, false));
        }

        $netMinutes = max(0, $grossMinutes - $completedBreakMinutes - $activeBreakMinutes);

        return round($netMinutes / 60, 2);
    }

    /**
     * For active sessions return real-time elapsed break hours (completed + active break).
     * For completed sessions return the persisted value.
     */
    private function computeLiveBreakHours(): float
    {
        if ($this->attendance->clock_out_time !== null || $this->attendance->clock_in_time === null) {
            return (float) $this->attendance->total_break_hours;
        }

        $timezone = $this->attendance->timezone ?: config('app.timezone') ?: 'UTC';

        $completedBreakMinutes = (int) $this->attendance->breaks()
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        $activeBreak = $this->attendance->activeBreak();
        $activeBreakMinutes = 0;
        if ($activeBreak && $activeBreak->start_time) {
            $now                = \Carbon\CarbonImmutable::now($timezone);
            $activeBreakStart   = \Carbon\CarbonImmutable::parse($activeBreak->start_time, $timezone);
            $activeBreakMinutes = max(0, (int) $activeBreakStart->diffInMinutes($now, false));
        }

        return round(($completedBreakMinutes + $activeBreakMinutes) / 60, 2);
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
                'start_time' => $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('Y-m-d H:i:s') : null,
                'end_time' => $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('Y-m-d H:i:s') : null,
                'duration_minutes' => $break->duration_minutes,
                'duration_formatted' => $break->getFormattedDuration(),
                'notes' => $break->notes,
                'is_active' => $break->isActive(),
            ];
        }

        return $breaks;
    }

    /**
     * Format hours as "Xh Ym" — backwards-compatible alias (use HoursFormatter::fromHours()
     * for new code; this is kept only for the duration_formatted / overtime_formatted keys).
     * Internally delegates to HoursFormatter to guarantee consistent normalisation
     * (no "9h 60m" or "9h 93m" oddities).
     */
    private function formatDurationHm(float $hours): string
    {
        $hm = HoursFormatter::fromHours($hours);
        [$h, $m] = explode(':', $hm);
        $h = (int)$h;
        $m = (int)$m;

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
            'work_date' => $this->attendance->clock_in_time ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d') : null,
            'clock_in_time' => $this->attendance->clock_in_time ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('H:i') : null,
            'clock_out_time' => $this->attendance->clock_out_time ? \Carbon\Carbon::parse($this->attendance->clock_out_time)->format('H:i') : null,
            // Summary endpoints — HH:MM for parity with the rest of the report APIs.
            'total_work_hours' => HoursFormatter::fromDecimalString($this->attendance->total_work_hours),
            'overtime_hours' => HoursFormatter::fromDecimalString($this->attendance->overtime_hours),
            'status' => $this->attendance->status,
            'is_late' => $this->attendance->is_late,
            'is_early_departure' => $this->attendance->is_early_departure,
            'duration_formatted' => $this->formatDurationHm((float)$this->attendance->total_work_hours),
        ];
    }

    /**
     * Get minimal data for reports
     */
    public function getReportData(): array
    {
        return [
            'user_name' => $this->attendance->user?->name,
            'work_date' => $this->attendance->clock_in_time ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d') : null,
            'clock_in_time' => $this->attendance->clock_in_time ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('H:i:s') : null,
            'clock_out_time' => $this->attendance->clock_out_time ? \Carbon\Carbon::parse($this->attendance->clock_out_time)->format('H:i:s') : null,
            // Report endpoints — HH:MM strings only (decimal-hour values were misrendered as
            // "09:93" by the mobile FE; see HoursFormatter docblock for context).
            'total_work_hours' => HoursFormatter::fromDecimalString($this->attendance->total_work_hours),
            'overtime_hours' => HoursFormatter::fromDecimalString($this->attendance->overtime_hours),
            'break_hours' => HoursFormatter::fromDecimalString($this->attendance->total_break_hours),
            'is_late' => $this->attendance->is_late ? 'Yes' : 'No',
            'late_minutes' => HoursFormatter::fromMinutes((int)$this->attendance->late_minutes),
            'is_early_departure' => $this->attendance->is_early_departure ? 'Yes' : 'No',
            'early_departure_minutes' => HoursFormatter::fromMinutes((int)$this->attendance->early_departure_minutes),
            'status' => ucfirst($this->attendance->status),
            'created_at' => $this->attendance->created_at?->format('Y-m-d h:i:s A'),
        ];
    }

}
