<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Calculator;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Domain\Calculator\StandardEarlyDeparturePolicy;
use Modules\Attendance\Domain\Calculator\StandardLatenessPolicy;
use Modules\Attendance\Domain\Calculator\StandardOvertimePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the three root-cause bugs that triggered this refactor.
 *
 * D1 — TZ double-conversion: start_time / end_time are stored in branch TZ.
 *       Old accessors re-parsed them as UTC → times shifted by the UTC offset.
 *       Fix: removed getStartTimeAttribute / getEndTimeAttribute; all code reads
 *       the raw string from DB and parses with explicit timezone.
 *       Regression: calculator receives a time that's already in branch TZ — the
 *       work-hours result must NOT be offset by the branch UTC offset.
 *
 * D2 — Hard-coded 8 h overtime baseline: old calculateWorkHours() computed
 *       overtime = max(0, worked − 8), regardless of shift length.
 *       Fix: StandardOvertimePolicy uses scheduledEnd − scheduledStart.
 *       Regression: 6-hour shift, employee works 7 hours → overtime = 1 h (not −1 h).
 *
 * C1 — No row lock on auto-close: concurrent callers each read status=active and
 *       both wrote clock_out_time.  Fix: SELECT … FOR UPDATE in AutoCloseAttendanceService.
 *       (Concurrency behaviour cannot be verified by a pure unit test; see the
 *       AutoCloseAttendanceServiceTest feature test for that scenario.)
 */
final class CalculatorRegressionTest extends TestCase
{
    private AttendanceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AttendanceCalculator(
            new StandardLatenessPolicy(),
            new StandardOvertimePolicy(),
            new StandardEarlyDeparturePolicy(),
        );
    }

    // -------------------------------------------------------------------------
    // D1 — TZ double-conversion
    // -------------------------------------------------------------------------

    /**
     * Branch timezone Asia/Riyadh = UTC+3.
     * Shift 09:00–17:00 branch TZ; employee clocks out at 17:00.
     * Old bug: after double-conversion, times were 3 h off → work_hours = 5 h or 11 h.
     * Expected: exactly 8 h net work, 0 overtime.
     */
    public function test_d1_timezone_stored_as_branch_tz_no_double_conversion(): void
    {
        $tz = 'Asia/Riyadh'; // UTC+3

        // Times are ALREADY in branch TZ (stored raw from DB, no conversion needed).
        $scheduledStart = CarbonImmutable::parse('2024-01-15 09:00:00', $tz);
        $scheduledEnd   = CarbonImmutable::parse('2024-01-15 17:00:00', $tz);
        $clockIn        = CarbonImmutable::parse('2024-01-15 09:00:00', $tz);
        $clockOut       = CarbonImmutable::parse('2024-01-15 17:00:00', $tz);

        $input = new CalculatorInput(
            scheduledStart:     $scheduledStart,
            scheduledEnd:       $scheduledEnd,
            clockIn:            $clockIn,
            clockOut:           $clockOut,
            totalBreakMinutes:  0,
            gracePeriodMinutes: 0,
            maxOverTimeHours:   0.0,
            timezone:           $tz,
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(8.0, $result->totalWorkHours, 'D1 regression: work hours must be 8h, not shifted by UTC offset');
        $this->assertSame(0.0, $result->overtimeHours);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->isEarlyDeparture);
    }

    /**
     * Different UTC offset (Asia/Kolkata = UTC+5:30) must produce the same result —
     * the calculator is TZ-agnostic once all times are in the same zone.
     */
    public function test_d1_same_calculation_regardless_of_utc_offset(): void
    {
        $tz = 'Asia/Kolkata'; // UTC+5:30

        $scheduledStart = CarbonImmutable::parse('2024-01-15 09:00:00', $tz);
        $scheduledEnd   = CarbonImmutable::parse('2024-01-15 17:00:00', $tz);
        $clockIn        = CarbonImmutable::parse('2024-01-15 09:00:00', $tz);
        $clockOut       = CarbonImmutable::parse('2024-01-15 17:00:00', $tz);

        $input = new CalculatorInput(
            scheduledStart:     $scheduledStart,
            scheduledEnd:       $scheduledEnd,
            clockIn:            $clockIn,
            clockOut:           $clockOut,
            totalBreakMinutes:  0,
            gracePeriodMinutes: 0,
            maxOverTimeHours:   0.0,
            timezone:           $tz,
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(8.0, $result->totalWorkHours, 'D1: result must be 8h regardless of UTC offset');
    }

    // -------------------------------------------------------------------------
    // D2 — Hard-coded 8-hour overtime baseline
    // -------------------------------------------------------------------------

    /**
     * 6-hour shift (09:00–15:00), employee works 7 hours (clocks out 16:00).
     * Old bug: overtime = max(0, 7 − 8) = 0 h.  (Hard-coded 8h baseline)
     * Fixed:   overtime = max(0, 7 − 6) = 1 h.
     */
    public function test_d2_overtime_uses_scheduled_shift_length_not_hardcoded_8h(): void
    {
        $tz = 'Asia/Riyadh';

        $input = new CalculatorInput(
            scheduledStart:     CarbonImmutable::parse('2024-01-15 09:00:00', $tz),
            scheduledEnd:       CarbonImmutable::parse('2024-01-15 15:00:00', $tz),
            clockIn:            CarbonImmutable::parse('2024-01-15 09:00:00', $tz),
            clockOut:           CarbonImmutable::parse('2024-01-15 16:00:00', $tz),
            totalBreakMinutes:  0,
            gracePeriodMinutes: 0,
            maxOverTimeHours:   2.0, // cap at 2h, so 1h overtime is within cap
            timezone:           $tz,
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(7.0, $result->totalWorkHours, 'D2: total work hours must be 7h (not capped to 6h)');
        $this->assertSame(1.0, $result->overtimeHours, 'D2: overtime must be 1h (shift=6h, worked=7h) — NOT 0h from hard-coded 8h baseline');
    }

    /**
     * 10-hour shift (07:00–17:00), employee works exactly 10 hours.
     * No overtime because worked == scheduled.
     * Old bug (if hard-coded 8h): would have reported 2h overtime erroneously.
     */
    public function test_d2_no_overtime_when_worked_equals_scheduled_shift(): void
    {
        $tz = 'Asia/Riyadh';

        $input = new CalculatorInput(
            scheduledStart:     CarbonImmutable::parse('2024-01-15 07:00:00', $tz),
            scheduledEnd:       CarbonImmutable::parse('2024-01-15 17:00:00', $tz),
            clockIn:            CarbonImmutable::parse('2024-01-15 07:00:00', $tz),
            clockOut:           CarbonImmutable::parse('2024-01-15 17:00:00', $tz),
            totalBreakMinutes:  0,
            gracePeriodMinutes: 0,
            maxOverTimeHours:   2.0,
            timezone:           $tz,
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(10.0, $result->totalWorkHours);
        $this->assertSame(0.0, $result->overtimeHours, 'D2: no overtime when worked == scheduled (old bug: 2h extra from 8h baseline)');
    }

    /**
     * 4-hour shift, employee leaves after 3 hours (1h early departure).
     * Old bug (hard-coded 8h): overtime would be negative → early departure calculation incorrect.
     */
    public function test_d2_short_shift_early_departure_no_negative_overtime(): void
    {
        $tz = 'Asia/Riyadh';

        $input = new CalculatorInput(
            scheduledStart:     CarbonImmutable::parse('2024-01-15 09:00:00', $tz),
            scheduledEnd:       CarbonImmutable::parse('2024-01-15 13:00:00', $tz),
            clockIn:            CarbonImmutable::parse('2024-01-15 09:00:00', $tz),
            clockOut:           CarbonImmutable::parse('2024-01-15 12:00:00', $tz),
            totalBreakMinutes:  0,
            gracePeriodMinutes: 0,
            maxOverTimeHours:   0.0,
            timezone:           $tz,
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(3.0, $result->totalWorkHours);
        $this->assertSame(0.0, $result->overtimeHours, 'Overtime must never be negative');
        $this->assertTrue($result->isEarlyDeparture, 'Must be flagged as early departure');
        $this->assertSame(60, $result->earlyDepartureMinutes);
    }

    // -------------------------------------------------------------------------
    // max_over_time is HOURS (decimal), not minutes
    // -------------------------------------------------------------------------

    /**
     * max_over_time = 0.5 → cap is 30 minutes.
     * Employee works 2 h overtime → capped at 30 min.
     */
    public function test_max_over_time_is_hours_decimal_not_minutes(): void
    {
        $tz = 'Asia/Riyadh';

        $input = new CalculatorInput(
            scheduledStart:     CarbonImmutable::parse('2024-01-15 09:00:00', $tz),
            scheduledEnd:       CarbonImmutable::parse('2024-01-15 17:00:00', $tz),
            clockIn:            CarbonImmutable::parse('2024-01-15 09:00:00', $tz),
            clockOut:           CarbonImmutable::parse('2024-01-15 19:00:00', $tz), // +2h over
            totalBreakMinutes:  0,
            gracePeriodMinutes: 0,
            maxOverTimeHours:   0.5, // 30 minutes cap
            timezone:           $tz,
        );

        $result = $this->calculator->calculate($input);

        $this->assertSame(0.5, $result->overtimeHours, 'max_over_time=0.5 means 30-min cap, not 0.5-min cap');
    }
}
