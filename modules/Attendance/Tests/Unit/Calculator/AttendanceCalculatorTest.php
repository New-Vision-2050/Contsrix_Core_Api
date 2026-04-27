<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Calculator;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Domain\Calculator\StandardEarlyDeparturePolicy;
use Modules\Attendance\Domain\Calculator\StandardLatenessPolicy;
use Modules\Attendance\Domain\Calculator\StandardOvertimePolicy;
use Modules\Attendance\Domain\Calculator\WorkHoursResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests — no DB, no Eloquent, no Laravel container.
 * Covers the full calculation matrix from the refactoring plan §12.5
 * with correct expected values per confirmed business rules.
 *
 * Business rules (confirmed with stakeholder):
 *  - late_minutes  = full minutes past scheduledStart (NOT past the grace window).
 *                    e.g. grace=10, clockIn=09:20 → late_minutes=20 (not 10).
 *  - overtime      = max(0, worked − scheduled), then capped by maxOverTimeHours.
 *                    maxOverTimeHours = 0 → no overtime allowed.
 *  - auto-clock-out stores clock_out_time = end_time (deterministic boundary, not now()).
 */
class AttendanceCalculatorTest extends TestCase
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
    // Helper
    // -------------------------------------------------------------------------

    private function input(
        string $schedStart,
        string $schedEnd,
        ?string $clockIn,
        ?string $clockOut,
        int    $breakMinutes  = 0,
        int    $gracePeriodMinutes = 0,
        float  $maxOverTimeHours   = 0.0,
        string $timezone      = 'Asia/Riyadh',
        bool   $overnightSched = false,
    ): CalculatorInput {
        $tz = $timezone;
        $sStart = CarbonImmutable::parse($schedStart, $tz);
        $sEnd   = CarbonImmutable::parse($schedEnd, $tz);

        if ($overnightSched && !$sEnd->greaterThan($sStart)) {
            $sEnd = $sEnd->addDay();
        }

        return new CalculatorInput(
            scheduledStart:    $sStart,
            scheduledEnd:      $sEnd,
            clockIn:           $clockIn  ? CarbonImmutable::parse($clockIn, $tz)  : null,
            clockOut:          $clockOut ? CarbonImmutable::parse($clockOut, $tz) : null,
            totalBreakMinutes: $breakMinutes,
            gracePeriodMinutes: $gracePeriodMinutes,
            maxOverTimeHours:  $maxOverTimeHours,
            timezone:          $tz,
        );
    }

    // -------------------------------------------------------------------------
    // No clock-in / no clock-out → zero result
    // -------------------------------------------------------------------------

    public function test_no_clock_in_returns_zeros(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    null,
            clockOut:   '2024-01-15 17:00',
        ));

        $this->assertResult($result, workHours: 0.0, breakHours: 0.0, otHours: 0.0,
            isLate: false, lateMin: 0, isEarly: false, earlyMin: 0);
    }

    public function test_clock_in_without_clock_out_computes_lateness_only_on_time(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   null,
            gracePeriodMinutes: 10,
        ));

        $this->assertResult($result, workHours: 0.0, breakHours: 0.0, otHours: 0.0,
            isLate: false, lateMin: 0, isEarly: false, earlyMin: 0);
    }

    public function test_clock_in_without_clock_out_computes_lateness_only_when_late(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:20',
            clockOut:   null,
            gracePeriodMinutes: 10,
        ));

        $this->assertResult($result, workHours: 0.0, breakHours: 0.0, otHours: 0.0,
            isLate: true, lateMin: 20, isEarly: false, earlyMin: 0);
    }

    // -------------------------------------------------------------------------
    // On time — 1 in/out
    // -------------------------------------------------------------------------

    public function test_on_time_no_late_no_ot(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 17:00',
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        // 8h worked = 8h scheduled → 0 OT
        $this->assertResult($result, workHours: 8.0, breakHours: 0.0, otHours: 0.0,
            isLate: false, lateMin: 0, isEarly: false, earlyMin: 0);
    }

    // -------------------------------------------------------------------------
    // Lateness
    // -------------------------------------------------------------------------

    public function test_late_within_grace_is_not_late(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:08',
            clockOut:   '2024-01-15 17:00',
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        $this->assertFalse($result->isLate);
        $this->assertSame(0, $result->lateMinutes);
    }

    public function test_late_exactly_at_grace_boundary_is_not_late(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:10',
            clockOut:   '2024-01-15 17:00',
            gracePeriodMinutes: 10,
        ));

        $this->assertFalse($result->isLate);
    }

    public function test_late_past_grace_records_full_minutes_from_scheduled_start(): void
    {
        // Business rule: late_minutes = full diff from scheduledStart (NOT from grace boundary).
        // Grace=10, clockIn=09:20 → late_minutes = 20 (not 10).
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:20',
            clockOut:   '2024-01-15 17:00',
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        $this->assertTrue($result->isLate);
        $this->assertSame(20, $result->lateMinutes);
        $this->assertSame(0.0, $result->overtimeHours);
        $this->assertFalse($result->isEarlyDeparture);
    }

    public function test_zero_grace_any_lateness_is_recorded(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:01',
            clockOut:   '2024-01-15 17:00',
            gracePeriodMinutes: 0,
        ));

        $this->assertTrue($result->isLate);
        $this->assertSame(1, $result->lateMinutes);
    }

    // -------------------------------------------------------------------------
    // Early departure
    // -------------------------------------------------------------------------

    public function test_early_out(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 16:30',
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        $this->assertFalse($result->isLate);
        $this->assertTrue($result->isEarlyDeparture);
        $this->assertSame(30, $result->earlyDepartureMinutes);
        $this->assertSame(0.0, $result->overtimeHours);
    }

    public function test_exact_end_time_is_not_early_departure(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 17:00',
        ));

        $this->assertFalse($result->isEarlyDeparture);
        $this->assertSame(0, $result->earlyDepartureMinutes);
    }

    // -------------------------------------------------------------------------
    // Overtime
    // -------------------------------------------------------------------------

    public function test_overtime_within_cap(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 17:45',
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        // 8h45m worked − 8h scheduled = 45min OT; cap = 60min → OT = 45min = 0.75h
        $this->assertFalse($result->isLate);
        $this->assertSame(0.75, $result->overtimeHours);
        $this->assertFalse($result->isEarlyDeparture);
    }

    public function test_overtime_capped_at_max_over_time(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 19:00',
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        // 10h worked − 8h scheduled = 2h OT; cap = 1h → OT = 1.0h
        $this->assertSame(1.0, $result->overtimeHours);
    }

    public function test_zero_max_over_time_means_no_overtime_allowed(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 19:00',
            gracePeriodMinutes: 0,
            maxOverTimeHours: 0.0,
        ));

        // No overtime allowed regardless of how long the user worked
        $this->assertSame(0.0, $result->overtimeHours);
    }

    public function test_decimal_max_over_time_hours_supported(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 19:00',
            maxOverTimeHours: 0.5, // 30 minutes
        ));

        // 2h OT uncapped; cap = 0.5h = 30 min → OT = 0.5h
        $this->assertSame(0.5, $result->overtimeHours);
    }

    // -------------------------------------------------------------------------
    // Breaks
    // -------------------------------------------------------------------------

    public function test_break_minutes_reduce_net_work_and_increase_break_hours(): void
    {
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 17:00',
            breakMinutes: 60,
            maxOverTimeHours: 1.0,
        ));

        // 8h gross − 1h break = 7h net; scheduled = 8h → no OT
        $this->assertSame(7.0, $result->totalWorkHours);
        $this->assertSame(1.0, $result->totalBreakHours);
        $this->assertSame(0.0, $result->overtimeHours);
    }

    // -------------------------------------------------------------------------
    // Overnight shift
    // -------------------------------------------------------------------------

    public function test_overnight_shift_with_break(): void
    {
        // Scheduled 22:00 d1 → 06:00 d2 (8h = 480 min)
        // One auto-break of 60 min → net = 420 min = 7h
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 22:00',
            schedEnd:   '2024-01-16 06:00',
            clockIn:    '2024-01-15 22:00',
            clockOut:   '2024-01-16 06:00',
            breakMinutes: 60,
            gracePeriodMinutes: 10,
            maxOverTimeHours: 0.0,
            overnightSched: false, // already dates given explicitly
        ));

        $this->assertSame(7.0, $result->totalWorkHours);
        $this->assertSame(1.0, $result->totalBreakHours);
        $this->assertSame(0.0, $result->overtimeHours);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->isEarlyDeparture);
    }

    public function test_overnight_shift_uses_addday_when_end_before_start(): void
    {
        // Same day strings: start=22:00, end=06:00 — end is before start on same date
        $sched_start = '2024-01-15 22:00';
        $sched_end   = '2024-01-15 06:00'; // before start → should bump to next day internally

        $tz = 'Asia/Riyadh';
        $scheduledStart = CarbonImmutable::parse($sched_start, $tz);
        $scheduledEnd   = CarbonImmutable::parse($sched_end, $tz);
        // Replicate the overnight logic as done in AutoCloseAttendanceService / calculateWorkHours
        if (!$scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $input = new CalculatorInput(
            scheduledStart:    $scheduledStart,
            scheduledEnd:      $scheduledEnd,
            clockIn:           CarbonImmutable::parse('2024-01-15 22:00', $tz),
            clockOut:          CarbonImmutable::parse('2024-01-16 06:00', $tz),
            totalBreakMinutes: 0,
            gracePeriodMinutes: 10,
            maxOverTimeHours:  0.0,
            timezone:          $tz,
        );

        $result = $this->calculator->calculate($input);

        // 8h worked = 8h scheduled = 0 OT; on time; no early departure
        $this->assertSame(8.0, $result->totalWorkHours);
        $this->assertSame(0.0, $result->overtimeHours);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->isEarlyDeparture);
    }

    // -------------------------------------------------------------------------
    // Multi clock-in/out (auto-break totals pre-summed by caller)
    // -------------------------------------------------------------------------

    public function test_three_in_out_with_two_auto_breaks(): void
    {
        // First in: 09:00; last out: 17:00; 2 breaks × 60 min = 120 min
        // Gross: 8h; net: 8h − 2h = 6h; scheduled: 8h → no OT
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 17:00',
            breakMinutes: 120,
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        $this->assertSame(6.0, $result->totalWorkHours);
        $this->assertSame(2.0, $result->totalBreakHours);
        $this->assertSame(0.0, $result->overtimeHours);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->isEarlyDeparture);
    }

    public function test_two_in_out_with_break_and_overtime(): void
    {
        // First in: 09:00; last out: 18:30; 1 break × 60 min
        // Gross: 9h30m=570min; net: 510min=8.5h; scheduled: 8h → OT=30min=0.5h; cap=1h
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 18:30',
            breakMinutes: 60,
            gracePeriodMinutes: 10,
            maxOverTimeHours: 1.0,
        ));

        $this->assertSame(8.5, $result->totalWorkHours);
        $this->assertSame(1.0, $result->totalBreakHours);
        $this->assertSame(0.5, $result->overtimeHours);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->isEarlyDeparture);
    }

    public function test_multi_in_out_overnight_with_auto_break(): void
    {
        // Scheduled: 22:00 d1 → 06:00 d2 (8h); first in: 22:00 d1; last out: 06:00 d2; break: 60min
        $result = $this->calculator->calculate($this->input(
            schedStart: '2024-01-15 22:00',
            schedEnd:   '2024-01-16 06:00',
            clockIn:    '2024-01-15 22:00',
            clockOut:   '2024-01-16 06:00',
            breakMinutes: 60,
            gracePeriodMinutes: 10,
            maxOverTimeHours: 0.0,
        ));

        $this->assertSame(7.0, $result->totalWorkHours);
        $this->assertSame(0.0, $result->overtimeHours);
        $this->assertFalse($result->isLate);
        $this->assertFalse($result->isEarlyDeparture);
    }

    // -------------------------------------------------------------------------
    // State-leak guard (Octane): running the calculator twice produces same result
    // -------------------------------------------------------------------------

    public function test_no_state_leak_between_calls(): void
    {
        $inputA = $this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:00',
            clockOut:   '2024-01-15 17:45',
            maxOverTimeHours: 1.0,
        );

        $inputB = $this->input(
            schedStart: '2024-01-15 09:00',
            schedEnd:   '2024-01-15 17:00',
            clockIn:    '2024-01-15 09:20',
            clockOut:   '2024-01-15 17:00',
            gracePeriodMinutes: 10,
        );

        $resultA1 = $this->calculator->calculate($inputA);
        $this->calculator->calculate($inputB); // run B in between
        $resultA2 = $this->calculator->calculate($inputA);

        // A's results must be identical regardless of B having been calculated
        $this->assertSame($resultA1->overtimeHours, $resultA2->overtimeHours);
        $this->assertSame($resultA1->isLate, $resultA2->isLate);
        $this->assertSame($resultA1->totalWorkHours, $resultA2->totalWorkHours);
    }

    // -------------------------------------------------------------------------
    // Helper assertion
    // -------------------------------------------------------------------------

    private function assertResult(
        WorkHoursResult $r,
        float $workHours,
        float $breakHours,
        float $otHours,
        bool  $isLate,
        int   $lateMin,
        bool  $isEarly,
        int   $earlyMin,
    ): void {
        $this->assertSame($workHours, $r->totalWorkHours,    'totalWorkHours mismatch');
        $this->assertSame($breakHours, $r->totalBreakHours,  'totalBreakHours mismatch');
        $this->assertSame($otHours, $r->overtimeHours,       'overtimeHours mismatch');
        $this->assertSame($isLate, $r->isLate,               'isLate mismatch');
        $this->assertSame($lateMin, $r->lateMinutes,         'lateMinutes mismatch');
        $this->assertSame($isEarly, $r->isEarlyDeparture,    'isEarlyDeparture mismatch');
        $this->assertSame($earlyMin, $r->earlyDepartureMinutes, 'earlyDepartureMinutes mismatch');
    }
}
