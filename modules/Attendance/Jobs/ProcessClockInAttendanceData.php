<?php

declare(strict_types=1);

namespace Modules\Attendance\Jobs;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;

class ProcessClockInAttendanceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $attendanceId,
        public readonly string $companyId,
    ) {}

    public function handle(AttendanceCalculator $calculator): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $attendance = Attendance::where('id', $this->attendanceId)->first();

            if (! $attendance) {
                Log::error("ProcessClockInAttendanceData: attendance {$this->attendanceId} not found.");
                return;
            }

            // Only act when the shift is still open (no manual clock-out happened between dispatch and now).
            if ($attendance->clock_out_time !== null) {
                return;
            }

            $user           = User::find($attendance->user_id);
            $trackingPoints = $attendance->location_tracking ?? [];
            $lastLocation   = ! empty($trackingPoints) ? end($trackingPoints) : $attendance->clock_in_location;

            $clockOutTime = Carbon::now();

            $attendance->update([
                'clock_out_time'     => $clockOutTime,
                'day_status'         => 'clocked_out',
                'status'             => Attendance::STATUS_COMPLETED,
                'clock_out_location' => $lastLocation,
            ]);
            $attendance->refresh();

            // Use the domain calculator — reads timezone from the row, not from the HTTP request.
            $input  = $this->buildCalculatorInput($attendance);
            $result = $calculator->calculate($input);

            $attendance->update([
                'total_work_hours'        => $result->totalWorkHours,
                'total_break_hours'       => $result->totalBreakHours,
                'overtime_hours'          => $result->overtimeHours,
                'is_late'                 => $result->isLate,
                'late_minutes'            => $result->lateMinutes,
                'is_early_departure'      => $result->isEarlyDeparture,
                'early_departure_minutes' => $result->earlyDepartureMinutes,
            ]);

            if (! $user) {
                return;
            }

            $constraintService = app(\Modules\Attendance\Services\AttendanceConstraintService::class);
            $constraints = $constraintService->getTodaysWorkRulesForUser($user);

            if (! isset($constraints['first_next_period'])) {
                Log::warning("ProcessClockInAttendanceData: no next period for user {$user->id}");
                return;
            }

            $nextPeriod    = $constraints['first_next_period'];
            $nextStartTime = $nextPeriod['date'] . ' ' . $nextPeriod['start_time'] . ':00';
            $nextEndTime   = $nextPeriod['date'] . ' ' . $nextPeriod['end_time'] . ':00';

            $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

            $nextAttendance = Attendance::where('user_id', $user->id)
                ->where('start_time', $nextStartTime)
                ->first();

            if ($nextAttendance && $nextAttendance->clock_in_time === null) {
                $nextAttendance->update([
                    'clock_in_time'     => Carbon::now(),
                    'day_status'        => 'in_location',
                    'status'            => Attendance::STATUS_ACTIVE,
                    'clock_in_location' => $attendance->clock_in_location,
                    'is_absent'         => 0,
                    'is_holiday'        => 0,
                    'end_time'          => $nextEndTime,
                    'timezone'          => $timezone,
                    'business_date'     => Carbon::parse($nextStartTime, $timezone)->toDateString(),
                ]);
            } elseif (! $nextAttendance) {
                Attendance::create([
                    'user_id'           => $user->id,
                    'company_id'        => $attendance->company_id,
                    'timezone'          => $timezone,
                    'start_time'        => $nextStartTime,
                    'end_time'          => $nextEndTime,
                    'clock_in_time'     => Carbon::now(),
                    'clock_out_time'    => null,
                    'status'            => Attendance::STATUS_ACTIVE,
                    'day_status'        => 'in_location',
                    'clock_in_location' => $attendance->clock_in_location,
                    'business_date'     => Carbon::parse($nextStartTime, $timezone)->toDateString(),
                ]);
            }
        } finally {
            tenancy()->end();
        }
    }

    private function buildCalculatorInput(Attendance $attendance): CalculatorInput
    {
        $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

        // Wall-clock strings stored in branch TZ; label them with that TZ instead of
        // parsing as UTC and converting (which would shift values by the branch offset).
        $scheduledStart = CarbonImmutable::parse($attendance->start_time, $timezone);
        $scheduledEnd   = CarbonImmutable::parse($attendance->end_time, $timezone);

        if (! $scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $clockIn  = $attendance->clock_in_time
            ? CarbonImmutable::parse($attendance->clock_in_time, $timezone)
            : null;
        $clockOut = $attendance->clock_out_time
            ? CarbonImmutable::parse($attendance->clock_out_time, $timezone)
            : null;

        $totalBreakMinutes = (int) $attendance->breaks()
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        $snapshot      = $attendance->appliedAttendanceConstraint?->constraint_snapshot ?? [];
        $latenessRules = $snapshot['lateness_rules'] ?? [];
        $graceValue    = (int) ($latenessRules['lateness_period'] ?? $latenessRules['grace_period_minutes'] ?? 0);
        $graceUnit     = (string) ($latenessRules['lateness_unit'] ?? 'minute');
        $graceMinutes  = match (strtolower($graceUnit)) {
            'hour' => $graceValue * 60,
            'day'  => $graceValue * 1440,
            default => $graceValue,
        };

        return new CalculatorInput(
            scheduledStart:     $scheduledStart,
            scheduledEnd:       $scheduledEnd,
            clockIn:            $clockIn,
            clockOut:           $clockOut,
            totalBreakMinutes:  $totalBreakMinutes,
            gracePeriodMinutes: max(0, $graceMinutes),
            maxOverTimeHours:   (float) ($attendance->max_over_time ?? 0.0),
            timezone:           $timezone,
        );
    }
}
