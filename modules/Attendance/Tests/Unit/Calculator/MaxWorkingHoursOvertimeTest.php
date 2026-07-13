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
 * Overtime behaviour under the max_working_hours model:
 *  - Regular target is max_working_hours (NOT the scheduled window length).
 *  - Overtime is work beyond that target, but ONLY within the shift window
 *    [scheduledStart, scheduledEnd] (no overtime outside the shift).
 *  - A re-clock-in overtime session subtracts the regular hours already consumed
 *    by earlier rows via priorPeriodNetMinutes.
 */
final class MaxWorkingHoursOvertimeTest extends TestCase
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

    private function input(
        string $clockOut,
        float $maxWorkingHours,
        float $maxOverTimeHours = 2.0,
        string $clockIn = '2024-01-15 09:00',
        int $priorPeriodNetMinutes = 0,
    ): CalculatorInput {
        $tz = 'Asia/Riyadh';

        return new CalculatorInput(
            scheduledStart:        CarbonImmutable::parse('2024-01-15 09:00', $tz),
            scheduledEnd:          CarbonImmutable::parse('2024-01-15 17:00', $tz), // 8h window
            clockIn:               CarbonImmutable::parse($clockIn, $tz),
            clockOut:              CarbonImmutable::parse($clockOut, $tz),
            totalBreakMinutes:     0,
            gracePeriodMinutes:    0,
            maxOverTimeHours:      $maxOverTimeHours,
            timezone:              $tz,
            maxWorkingHours:       $maxWorkingHours,
            priorPeriodNetMinutes: $priorPeriodNetMinutes,
        );
    }

    public function test_no_overtime_before_reaching_max_working_hours(): void
    {
        // W=6, worked exactly 6h → no overtime.
        $result = $this->calculator->calculate($this->input(clockOut: '2024-01-15 15:00', maxWorkingHours: 6.0));

        $this->assertSame(6.0, $result->totalWorkHours);
        $this->assertSame(0.0, $result->overtimeHours);
    }

    public function test_overtime_beyond_max_working_hours_within_window(): void
    {
        // W=6, worked 7h (still inside the 8h window) → 1h overtime.
        $result = $this->calculator->calculate($this->input(clockOut: '2024-01-15 16:00', maxWorkingHours: 6.0));

        $this->assertSame(7.0, $result->totalWorkHours);
        $this->assertSame(1.0, $result->overtimeHours);
    }

    public function test_overtime_is_clamped_to_shift_window_end(): void
    {
        // W=6, clock out 18:00 (1h past the 17:00 window). Overtime only counts within the
        // window: effective net = 8h → overtime = min(8-6, cap 2) = 2h. total_work_hours is the
        // real net (9h), unaffected by the overtime window clamp.
        $result = $this->calculator->calculate($this->input(clockOut: '2024-01-15 18:00', maxWorkingHours: 6.0));

        $this->assertSame(9.0, $result->totalWorkHours);
        $this->assertSame(2.0, $result->overtimeHours);
    }

    public function test_overtime_capped_by_max_over_time(): void
    {
        // W=6, worked to window end (8h) → 2h beyond target but cap = 1h.
        $result = $this->calculator->calculate($this->input(
            clockOut: '2024-01-15 17:00',
            maxWorkingHours: 6.0,
            maxOverTimeHours: 1.0,
        ));

        $this->assertSame(1.0, $result->overtimeHours);
    }

    public function test_overtime_session_after_regular_quota_consumed(): void
    {
        // Prior rows already worked the full 6h regular quota (360 min). This re-clock-in from
        // 15:00–16:00 is entirely overtime: threshold = max(0, 360-360) = 0 → 1h overtime.
        $result = $this->calculator->calculate($this->input(
            clockOut: '2024-01-15 16:00',
            maxWorkingHours: 6.0,
            clockIn: '2024-01-15 15:00',
            priorPeriodNetMinutes: 360,
        ));

        $this->assertSame(1.0, $result->totalWorkHours);
        $this->assertSame(1.0, $result->overtimeHours);
    }
}
