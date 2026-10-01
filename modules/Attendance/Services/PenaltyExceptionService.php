<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendancePenaltyException;
use Modules\Attendance\Support\PenaltyDayException;
use Modules\User\Models\User;

/**
 * Penalty day-exception (استثناء اليوم).
 *
 * When the rule-based auto clock-out closes a shift with a penalty
 * (shift_end_method = auto_extension_penalty), the employee gets a monthly budget
 * of exceptions (default 3, config attendance.penalty_exceptions_monthly_limit).
 * Spending one waives that day's penalty: clock_out_time is restored to the
 * expected clock-out and every calculated field is recomputed, so the day pays
 * in full. Accepting the penalty instead just records the decision.
 *
 * One decision per penalized day (unique attendance_id on the exceptions table).
 */
class PenaltyExceptionService
{
    public function __construct(
        private readonly AttendanceCalculator $calculator,
    ) {}

    /**
     * GET payload: this month's quota plus every penalized day still awaiting a
     * decision (the app shows the "penalty applied" message from this list the
     * next day) and the decisions already taken.
     *
     * @return array<string, mixed>
     */
    public function monthlyStatus(User $user): array
    {
        $now   = Carbon::now();
        $limit = PenaltyDayException::monthlyLimit();

        $decisions = AttendancePenaltyException::query()
            ->where('user_id', (string) $user->id)
            ->whereYear('decided_at', $now->year)
            ->whereMonth('decided_at', $now->month)
            ->orderByDesc('decided_at')
            ->get();

        $used = $decisions->where('action', AttendancePenaltyException::ACTION_WAIVED)->count();
        $remaining = PenaltyDayException::remaining($limit, $used);

        $decidedIds = AttendancePenaltyException::query()
            ->where('user_id', (string) $user->id)
            ->pluck('attendance_id')
            ->all();

        $pending = Attendance::query()
            ->where('user_id', (string) $user->id)
            ->where('shift_end_method', PenaltyDayException::PENALTY_METHOD)
            ->whereYear('business_date', $now->year)
            ->whereMonth('business_date', $now->month)
            ->when($decidedIds !== [], fn ($q) => $q->whereNotIn('id', $decidedIds))
            ->orderBy('business_date')
            ->get(['id', 'business_date', 'clock_out_time', 'expected_clock_out_time']);

        $messageDay = $this->messageDay($user, $decidedIds);

        return [
            'month'     => $now->format('Y-m'),
            'limit'     => $limit,
            'used'      => $used,
            'remaining' => $remaining,
            // show_exception_message: see PenaltyDayException::shouldShowMessage —
            // first clock-in of today + last worked day penalized and undecided.
            // message_day is the day the message is about (null when hidden).
            // can_use_exception: false once the monthly quota is exhausted, so the
            // app disables the "use exception" button and only offers "accept".
            'show_exception_message' => $messageDay !== null,
            'message_day'            => $messageDay,
            'can_use_exception'      => $remaining > 0,
            'pending_days' => $pending->map(fn (Attendance $a) => [
                'attendance_id'          => (string) $a->id,
                // business_date is date-cast on the model — format it, don't (string) it.
                'business_date'          => $a->business_date instanceof \DateTimeInterface
                    ? $a->business_date->format('Y-m-d')
                    : (string) $a->business_date,
                'clock_out_time'         => (string) ($a->clock_out_time ?? ''),
                'expected_clock_out_time' => (string) ($a->expected_clock_out_time ?? ''),
                'penalty_minutes'        => $this->penaltyMinutesOf($a),
            ])->values()->all(),
            'decisions' => $decisions->map(fn (AttendancePenaltyException $e) => [
                'attendance_id' => (string) $e->attendance_id,
                'business_date' => (string) $e->business_date,
                'action'        => (string) $e->action,
                'penalty_minutes' => (int) $e->penalty_minutes,
                'decided_at'    => $e->decided_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * Record the employee's decision on a penalized day.
     *
     * @param string $action use_exception | accept_penalty
     * @return array<string, mixed> decision + refreshed quota
     *
     * @throws AttendanceException 422 with a readable message on any rule violation
     */
    public function decide(User $user, string $attendanceId, string $action): array
    {
        if (! in_array($action, ['use_exception', 'accept_penalty'], true)) {
            throw new AttendanceException('Invalid action — expected use_exception or accept_penalty.', 422);
        }

        return DB::transaction(function () use ($user, $attendanceId, $action) {
            // Lock the row so a double-tap cannot decide the same day twice.
            $attendance = Attendance::query()
                ->lockForUpdate()
                ->find($attendanceId);

            if (! $attendance || (string) $attendance->user_id !== (string) $user->id) {
                throw new AttendanceException('Attendance record not found.', 404);
            }

            if (PenaltyDayException::isWaived($attendance->shift_end_method)
                || AttendancePenaltyException::query()->where('attendance_id', $attendance->id)->exists()
            ) {
                throw new AttendanceException('This day already has a penalty decision.', 422);
            }

            if (! PenaltyDayException::isPenalized($attendance->shift_end_method)) {
                throw new AttendanceException('This day has no applied penalty to decide on.', 422);
            }

            $now = Carbon::now();
            if (! PenaltyDayException::inDecisionWindow($attendance->business_date, $now)) {
                throw new AttendanceException('The decision window for this penalty has closed (current month only).', 422);
            }

            $limit = PenaltyDayException::monthlyLimit();
            $used  = AttendancePenaltyException::query()
                ->where('user_id', (string) $user->id)
                ->where('action', AttendancePenaltyException::ACTION_WAIVED)
                ->whereYear('decided_at', $now->year)
                ->whereMonth('decided_at', $now->month)
                ->count();

            if ($action === 'use_exception' && PenaltyDayException::remaining($limit, $used) <= 0) {
                throw new AttendanceException('No remaining day exceptions this month.', 422);
            }

            $penaltyMinutes = $this->penaltyMinutesOf($attendance);
            $originalClockOut = $attendance->clock_out_time instanceof \DateTimeInterface
                ? $attendance->clock_out_time->format('Y-m-d H:i:s')
                : (string) $attendance->clock_out_time;

            $restoredClockOut = null;
            if ($action === 'use_exception') {
                $restoredClockOut = $this->waivePenalty($attendance);
                $used++;
            }

            $decision = AttendancePenaltyException::create([
                'user_id'         => (string) $user->id,
                'company_id'      => (string) $attendance->company_id,
                'attendance_id'   => (string) $attendance->id,
                'business_date'   => $attendance->business_date instanceof \DateTimeInterface
                    ? $attendance->business_date->format('Y-m-d')
                    : (string) $attendance->business_date,
                'action'          => $action === 'use_exception'
                    ? AttendancePenaltyException::ACTION_WAIVED
                    : AttendancePenaltyException::ACTION_ACCEPTED,
                'penalty_minutes' => $penaltyMinutes,
                'original_clock_out_time' => $originalClockOut !== '' ? $originalClockOut : null,
                'restored_clock_out_time' => $restoredClockOut,
                'decided_at'      => $now,
            ]);

            $remaining = PenaltyDayException::remaining($limit, $used);

            $messageDay = $this->messageDay(
                $user,
                AttendancePenaltyException::query()
                    ->where('user_id', (string) $user->id)
                    ->pluck('attendance_id')
                    ->all()
            );

            return [
                'decision' => [
                    'id'            => (string) $decision->id,
                    'attendance_id' => (string) $decision->attendance_id,
                    'business_date' => (string) $decision->business_date,
                    'action'        => (string) $decision->action,
                    'penalty_minutes' => $penaltyMinutes,
                    'original_clock_out_time' => $decision->original_clock_out_time,
                    'restored_clock_out_time' => $decision->restored_clock_out_time,
                    'decided_at'    => $decision->decided_at?->toIso8601String(),
                ],
                'limit'     => $limit,
                'used'      => $used,
                'remaining' => $remaining,
                'can_use_exception'      => $remaining > 0,
                'show_exception_message' => $messageDay !== null,
                'message_day'            => $messageDay,
            ];
        });
    }

    /**
     * The penalized day the استثناء اليوم message should be about, or null when
     * the message is hidden (see PenaltyDayException::shouldShowMessage).
     *
     * "Today" is the branch business date, taken from the employee's most recent
     * row timezone. "Last attendance" is the most recent worked row (has a
     * clock-in and clock-out) before today — absent rows are skipped.
     *
     * @param list<string> $decidedIds attendance ids that already have a decision
     * @return array<string, mixed>|null
     */
    private function messageDay(User $user, array $decidedIds): ?array
    {
        $userId = (string) $user->id;

        $timezone = Attendance::query()
            ->where('user_id', $userId)
            ->whereNotNull('timezone')
            ->orderByDesc('business_date')
            ->value('timezone') ?: config('app.timezone') ?: 'Asia/Riyadh';

        $now   = Carbon::now($timezone);
        $today = $now->toDateString();

        $todayClockIns = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('business_date', $today)
            ->whereNotNull('clock_in_time')
            ->count();

        $last = Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('business_date', '<', $today)
            ->whereNotNull('clock_in_time')
            ->whereNotNull('clock_out_time')
            // The last day as a whole, not the last row: a flexible day has several
            // sessions, and the penalized auto-close is usually the first one — a
            // later session the employee closed himself must not hide it.
            ->orderByDesc('business_date')
            ->orderByRaw("CASE WHEN shift_end_method = ? THEN 0 ELSE 1 END", [PenaltyDayException::PENALTY_METHOD])
            ->orderByDesc('clock_in_time')
            ->first(['id', 'business_date', 'clock_out_time', 'expected_clock_out_time', 'shift_end_method']);

        if (! $last) {
            return null;
        }

        $show = PenaltyDayException::shouldShowMessage(
            $todayClockIns,
            $last->shift_end_method,
            in_array((string) $last->id, array_map('strval', $decidedIds), true),
            PenaltyDayException::inDecisionWindow($last->business_date, $now),
        );

        if (! $show) {
            return null;
        }

        return [
            'attendance_id'           => (string) $last->id,
            'business_date'           => $last->business_date instanceof \DateTimeInterface
                ? $last->business_date->format('Y-m-d')
                : (string) $last->business_date,
            'clock_out_time'          => (string) ($last->clock_out_time ?? ''),
            'expected_clock_out_time' => (string) ($last->expected_clock_out_time ?? ''),
            'penalty_minutes'         => $this->penaltyMinutesOf($last),
        ];
    }

    /**
     * Remove the penalty from the row: restore clock_out_time to the expected
     * clock-out and recompute every calculated field against the restored time.
     * Returns the restored wall-clock string.
     */
    private function waivePenalty(Attendance $attendance): string
    {
        $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

        $expectedRaw = $attendance->expected_clock_out_time;
        $restored = $expectedRaw instanceof \DateTimeInterface
            ? CarbonImmutable::parse($expectedRaw->format('Y-m-d H:i:s'), $timezone)
            : CarbonImmutable::parse((string) $expectedRaw, $timezone);

        $input  = $this->buildCalculatorInput($attendance, $restored);
        $result = $this->calculator->calculate($input);

        $noteLine = '[Auto] Penalty waived by day exception at ' . Carbon::now($timezone)->toIso8601String();

        $attendance->update([
            'clock_out_time'          => $restored->format('Y-m-d H:i:s'),
            'shift_end_method'        => PenaltyDayException::WAIVED_METHOD,
            'total_work_hours'        => $result->totalWorkHours,
            'total_break_hours'       => $result->totalBreakHours,
            'overtime_hours'          => $result->overtimeHours,
            'is_late'                 => $result->isLate,
            'late_minutes'            => $result->lateMinutes,
            'is_early_departure'      => $result->isEarlyDeparture,
            'early_departure_minutes' => $result->earlyDepartureMinutes,
            'pre_shift_hours'         => $result->preShiftHours,
            'in_shift_hours'          => $result->inShiftHours,
            'post_shift_hours'        => $result->postShiftHours,
            'outside_window_hours'    => $result->outsideWindowHours,
            'notes'                   => trim(($attendance->notes ?? '') . "\n" . $noteLine),
        ]);

        return $restored->format('Y-m-d H:i:s');
    }

    /**
     * Minutes the penalty deducted: expected clock-out minus the stored one.
     */
    private function penaltyMinutesOf(Attendance $attendance): int
    {
        $expected = $attendance->expected_clock_out_time;
        $stored   = $attendance->clock_out_time;

        if (empty($expected) || empty($stored)) {
            return 0;
        }

        $expectedTs = strtotime((string) $expected);
        $storedTs   = strtotime((string) $stored);

        if ($expectedTs === false || $storedTs === false) {
            return 0;
        }

        return max(0, (int) round(($expectedTs - $storedTs) / 60));
    }

    /**
     * Mirrors AutoCloseAttendanceService::buildCalculatorInput — same wall-clock
     * parsing rules (branch TZ as second argument, overnight bump, break intervals).
     */
    private function buildCalculatorInput(Attendance $attendance, CarbonImmutable $clockOut): CalculatorInput
    {
        $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

        $scheduledStart = CarbonImmutable::parse((string) $attendance->start_time, $timezone);
        $scheduledEnd   = CarbonImmutable::parse((string) $attendance->end_time, $timezone);

        if (! $scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $clockIn = $attendance->clock_in_time
            ? CarbonImmutable::parse((string) $attendance->clock_in_time, $timezone)
            : null;

        $breaks = $attendance->breaks()->whereNotNull('end_time')->get(['start_time', 'end_time', 'duration_minutes']);
        $totalBreakMinutes = 0;
        $breakIntervals = [];
        foreach ($breaks as $break) {
            $totalBreakMinutes += (int) ($break->duration_minutes ?? 0);
            if ($break->start_time && $break->end_time) {
                $breakIntervals[] = [
                    'start' => CarbonImmutable::parse((string) $break->start_time, $timezone),
                    'end'   => CarbonImmutable::parse((string) $break->end_time, $timezone),
                ];
            }
        }

        return new CalculatorInput(
            scheduledStart:    $scheduledStart,
            scheduledEnd:      $scheduledEnd,
            clockIn:           $clockIn,
            clockOut:          $clockOut,
            totalBreakMinutes: $totalBreakMinutes,
            maxOverTimeHours:  (float) ($attendance->max_over_time ?? 0.0),
            timezone:          $timezone,
            breakIntervals:    $breakIntervals,
            earlyWindowMinutes: (int) ($attendance->early_clock_in_minutes ?? 0),
            extensionMinutes:  (int) ($attendance->extension_minutes ?? 0),
            overtimeFlags:     \Modules\Attendance\Domain\Calculator\OvertimeFlags::fromArray($attendance->overtime_flags),
            excludeOvertimeFromWorkHours: (bool) config('attendance.exclude_overtime_from_work_hours', true),
        );
    }
}
