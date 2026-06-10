<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\HoursFormatter;

class AttendanceUserPresenter extends AbstractPresenter
{
    private Attendance $attendance;
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public static function requiredRelations(): array
    {
        return [
            'user',
            'professionalData.branch',
            'professionalData.management',
            'appliedAttendanceConstraint',
        ];
    }

    public function present(bool $isListing = false): array
    {
        // Determine work date (Y-m-d) from start_time or clock_in_time.
        $workDate = $this->attendance->start_time
            ? \Carbon\Carbon::parse($this->attendance->start_time)->format('Y-m-d')
            : ($this->attendance->clock_in_time
                ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('Y-m-d')
                : null);

        // Calculate day name and number of periods for that day from constraint config.
        $dayName = null;
        $officialIn = null;
        $officialOut = null;
        $dayPeriodsCount = null;

        if ($workDate && $this->attendance->appliedAttendanceConstraint && is_array($this->attendance->appliedAttendanceConstraint->constraint_snapshot)) {
            $workCarbon = \Carbon\Carbon::parse($workDate);
            $dayKey = strtolower($workCarbon->format('l')); // sunday, monday, ...

            $snapshot = $this->attendance->appliedAttendanceConstraint->constraint_snapshot;
            $constraintConfig = $snapshot['constraint_config'] ?? [];
            $timeRules = $constraintConfig['time_rules'] ?? [];
            $weeklySchedule = $timeRules['weekly_schedule'] ?? [];
            $daySchedule = $weeklySchedule[$dayKey] ?? null;

            $dayName = $dayKey;
            if (is_array($daySchedule) && isset($daySchedule['periods']) && is_array($daySchedule['periods'])) {
                $dayPeriodsCount = count($daySchedule['periods']);
                $firstPeriod = $daySchedule['periods'][0] ?? null;
                $lastPeriod = $daySchedule['periods'][$dayPeriodsCount - 1] ?? null;
                $officialIn = $firstPeriod['start_time'] ?? null;
                $officialOut = $lastPeriod['end_time'] ?? null;
            } else {
                $dayPeriodsCount = 0;
            }
        }

        $professionalData = $this->attendance->professionalData;

        // ── Load ALL attendance sessions for this user on this work date ──
        $attendanceSessions = [];
        if ($workDate && $this->attendance->user_id) {
            $siblingAttendances = Attendance::query()
                ->where('user_id', $this->attendance->user_id)
                ->where('company_id', tenant('id'))
                ->whereDate('business_date', $workDate)
                ->whereNotNull('clock_in_time')
                ->orderBy('clock_in_time')
                ->get(['clock_in_time', 'clock_out_time']);

            foreach ($siblingAttendances as $sa) {
                $attendanceSessions[] = [
                    'clock_in_time'  => $sa->clock_in_time
                        ? \Carbon\Carbon::parse($sa->clock_in_time)->format('H:i:s')
                        : null,
                    'clock_out_time' => $sa->clock_out_time
                        ? \Carbon\Carbon::parse($sa->clock_out_time)->format('H:i:s')
                        : null,
                ];
            }
        }

        // ── Load ALL task sessions for this user on this date ──
        $taskSessions = [];
        if ($workDate && $this->attendance->user_id) {
            $taskRows = \Illuminate\Support\Facades\DB::table('employee_task_requests')
                ->where('user_id', $this->attendance->user_id)
                ->where('company_id', tenant('id'))
                ->whereDate('task_date', $workDate)
                ->whereIn('status', ['completed', 'in_progress', 'paused'])
                ->orderByRaw('COALESCE(time_from, task_date) ASC')
                ->get(['time_from', 'time_to', 'title']);

            foreach ($taskRows as $tr) {
                $taskSessions[] = [
                    'task_time_in'  => $tr->time_from
                        ? substr((string) $tr->time_from, 11, 5)
                        : null,
                    'task_time_out' => $tr->time_to
                        ? substr((string) $tr->time_to, 11, 5)
                        : null,
                    'title'         => (string) ($tr->title ?? ''),
                ];
            }
        }

        return [
            'id' => $this->attendance->id ? (string)$this->attendance->id : null,
            'user_name' => $this->attendance->user?->name,
            'work_date' => $workDate,
            'day_name' => $dayName,
            'day_status' => __('validation.day_status.' . ($this->attendance->day_status ?? 'work_day')),
            'day_periods_count' => $dayPeriodsCount,

            // Branch & Management (from professional data)
            'branch' => $professionalData?->branch?->name,
            'management' => $professionalData?->management?->name,

            // Official times from constraint
            'official_in_time' => $officialIn,
            'official_out_time' => $officialOut,

            // Multiple sessions per day
            'attendance_sessions' => $attendanceSessions,
            'task_sessions' => $taskSessions,

            // Current record's single session (convenience / backward compat)
            'clock_in_time' => $this->attendance->clock_in_time
                ? \Carbon\Carbon::parse($this->attendance->clock_in_time)->format('H:i:s')
                : null,
            'clock_out_time' => $this->attendance->clock_out_time
                ? \Carbon\Carbon::parse($this->attendance->clock_out_time)->format('H:i:s')
                : null,

            // Hours (report format HH:MM)
            'total_work_hours' => HoursFormatter::fromDecimalString($this->attendance->total_work_hours),
            'overtime_hours' => HoursFormatter::fromDecimalString($this->attendance->overtime_hours),
            'break_hours' => HoursFormatter::fromDecimalString($this->attendance->total_break_hours),

            // Late / Early departure
            'is_late' => $this->attendance->is_late ? 'Yes' : 'No',
            'late_minutes' => HoursFormatter::fromMinutes((int)$this->attendance->late_minutes),
            'is_early_departure' => $this->attendance->is_early_departure ? 'Yes' : 'No',
            'early_departure_minutes' => HoursFormatter::fromMinutes((int)$this->attendance->early_departure_minutes),

            // Status flags
            'status' => ucfirst($this->attendance->status),
            'is_absent' => $this->attendance->is_absent ? 'Yes' : 'No',
            'is_holiday' => $this->attendance->is_holiday ? 'Yes' : 'No',

            // Timestamps
            'created_at' => $this->attendance->created_at?->format('Y-m-d h:i:s A'),
            'updated_at' => $this->attendance->updated_at?->format('Y-m-d h:i:s A'),
        ];
    }
}
