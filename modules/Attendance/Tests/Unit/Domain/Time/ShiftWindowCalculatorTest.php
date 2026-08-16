<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Domain\Time;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;
use Modules\Attendance\Domain\Time\ShiftWindowCalculator;
use Modules\Attendance\Domain\Time\ShiftWindowInput;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests — no DB, no container. Encodes the worked examples from
 * ATTENDANCE_RULES_V2_IMPLEMENTATION_PLAN.md §5.3 (R1 and the boundary matrix).
 */
final class ShiftWindowCalculatorTest extends TestCase
{
    private ShiftWindowCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new ShiftWindowCalculator();
    }

    private function input(
        string $scheduledStart,
        string $scheduledEnd,
        ?string $clockIn,
        int $earlyWindowMinutes = 0,
        int $extensionMinutes = 0,
        ?int $canClockInBeforeMinutes = null,
        float $maxOverTimeHours = 0.0,
        int $completedBreakMinutes = 0,
        int $alreadyWorkedMinutesInPeriod = 0,
        ?OvertimeFlags $flags = null,
        string $tz = 'Asia/Riyadh',
    ): ShiftWindowInput {
        return new ShiftWindowInput(
            scheduledStart: CarbonImmutable::parse($scheduledStart, $tz),
            scheduledEnd: CarbonImmutable::parse($scheduledEnd, $tz),
            clockIn: $clockIn ? CarbonImmutable::parse($clockIn, $tz) : null,
            earlyWindowMinutes: $earlyWindowMinutes,
            extensionMinutes: $extensionMinutes,
            canClockInBeforeMinutes: $canClockInBeforeMinutes,
            maxOverTimeHours: $maxOverTimeHours,
            completedBreakMinutes: $completedBreakMinutes,
            alreadyWorkedMinutesInPeriod: $alreadyWorkedMinutesInPeriod,
            overtimeFlags: $flags,
            timezone: $tz,
        );
    }

    // 1. R1 exact case — shift 12:00–21:00, early 360, CI 06:00 → expected 15:00
    public function test_r1_early_clock_in_counts_and_auto_closes_after_required_hours(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 06:00',
            earlyWindowMinutes: 360,
        ));

        $this->assertSame(540, $w->requiredWorkMinutes);
        $this->assertSame('2026-07-27 15:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
        $this->assertSame('2026-07-27 06:00', $w->workWindowStart->format('Y-m-d H:i'));
    }

    // 2. required minutes are ALWAYS E−S, even if a config field says otherwise (INV-29)
    public function test_required_minutes_derived_from_shift_length_only(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 09:00',
            scheduledEnd:   '2026-07-27 17:00',
            clockIn:        '2026-07-27 09:00',
        ));

        $this->assertSame(480, $w->requiredWorkMinutes);
    }

    // 3. On-time clock-in → expected == scheduled end (identical to V1 behaviour)
    public function test_on_time_clock_in_expected_equals_end(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 12:00',
        ));

        $this->assertSame('2026-07-27 21:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
    }

    // 4. Late clock-in with extension → completes hours inside the extension (R2)
    public function test_late_clock_in_completes_hours_in_extension(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 13:00',
            extensionMinutes: 120,
        ));

        $this->assertSame('2026-07-27 22:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
        $this->assertSame('2026-07-27 23:00', $w->lastClockOutAt->format('Y-m-d H:i'));
    }

    // 5. Late clock-in without extension → clamped to scheduled end
    public function test_late_clock_in_without_extension_clamped_to_end(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 13:00',
            extensionMinutes: 0,
        ));

        $this->assertSame('2026-07-27 21:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
    }

    // 6. firstClockInDeadline = S + can_clock_in_before; null when unset
    public function test_first_clock_in_deadline(): void
    {
        $with = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        null,
            canClockInBeforeMinutes: 60,
        ));
        $this->assertSame('2026-07-27 13:00', $with->firstClockInDeadline->format('Y-m-d H:i'));
        $this->assertSame('2026-07-27 13:00', $with->absentAt->format('Y-m-d H:i'));

        $without = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        null,
            canClockInBeforeMinutes: null,
        ));
        $this->assertNull($without->firstClockInDeadline);
    }

    // 7. Window edges shift when the segment flags open the outer zones (D2)
    public function test_flags_open_outer_zones(): void
    {
        $off = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        null,
            earlyWindowMinutes: 360,
            extensionMinutes: 120,
            maxOverTimeHours: 2.0,
        ));
        $this->assertSame('2026-07-27 06:00', $off->earliestClockIn->format('Y-m-d H:i'));
        $this->assertSame('2026-07-27 23:00', $off->lastClockInAt->format('Y-m-d H:i'));

        $on = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        null,
            earlyWindowMinutes: 360,
            extensionMinutes: 120,
            maxOverTimeHours: 2.0,
            flags: new OvertimeFlags(beforeEarlyClockIn: true, afterExtension: true),
        ));
        // Outer zone depth = max_over_time × 60 = 120 min each side.
        $this->assertSame('2026-07-27 04:00', $on->earliestClockIn->format('Y-m-d H:i'));
        // workWindowEnd 23:00 + 120 min outer zone = 01:00 the next day.
        $this->assertSame('2026-07-28 01:00', $on->lastClockInAt->format('Y-m-d H:i'));
    }

    // 8. Overnight shift: boundaries land on the correct calendar day
    public function test_overnight_shift_boundaries(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 22:00',
            scheduledEnd:   '2026-07-28 06:00',
            clockIn:        '2026-07-27 22:00',
        ));

        $this->assertSame('2026-07-28 06:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
        $this->assertSame(480, $w->requiredWorkMinutes);
    }

    // 9. Completed breaks push the auto clock-out later — but never past the hard window edge.
    public function test_breaks_extend_expected_clock_out_within_ceiling(): void
    {
        // No extension: anchor 12:00 + 540 req + 60 break = 22:00, clamped to lastClockOutAt 21:00.
        $clamped = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 12:00',
            completedBreakMinutes: 60,
        ));
        $this->assertSame('2026-07-27 21:00', $clamped->expectedClockOutAt->format('Y-m-d H:i'));

        // With a 2h extension the same break DOES extend the close into the window.
        $extended = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 12:00',
            extensionMinutes: 120,
            completedBreakMinutes: 60,
        ));
        $this->assertSame('2026-07-27 22:00', $extended->expectedClockOutAt->format('Y-m-d H:i'));
    }

    // 10. alreadyWorkedMinutesInPeriod reduces the remaining requirement (re-clock-in)
    public function test_already_worked_reduces_remaining(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 21:30',
            extensionMinutes: 120,
            alreadyWorkedMinutesInPeriod: 480, // 8h done → 1h remaining
        ));

        $this->assertSame('2026-07-27 22:30', $w->expectedClockOutAt->format('Y-m-d H:i'));
    }

    // 11. autoCloseTriggerAt = expected + max_over_time (INV-14 generalised)
    public function test_trigger_after_expected_by_max_overtime(): void
    {
        $w = $this->calc->compute($this->input(
            scheduledStart: '2026-07-27 12:00',
            scheduledEnd:   '2026-07-27 21:00',
            clockIn:        '2026-07-27 12:00',
            maxOverTimeHours: 1.0,
        ));

        $this->assertSame('2026-07-27 21:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
        $this->assertSame('2026-07-27 22:00', $w->autoCloseTriggerAt->format('Y-m-d H:i'));
    }

    public function test_flexible_day_clock_in_anytime_auto_closes_after_required_hours(): void
    {
        $tz = 'Asia/Riyadh';
        $w = $this->calc->compute(new ShiftWindowInput(
            scheduledStart: CarbonImmutable::parse('2026-08-13 00:00:00', $tz),
            scheduledEnd: CarbonImmutable::parse('2026-08-13 23:59:59', $tz),
            clockIn: CarbonImmutable::parse('2026-08-13 11:00:00', $tz),
            maxOverTimeHours: 1.0,
            overtimeFlags: new OvertimeFlags(afterFinishWork: true),
            timezone: $tz,
            requiredWorkMinutesOverride: 480,
            flexibleDay: true,
        ));

        $this->assertSame(480, $w->requiredWorkMinutes);
        $this->assertSame('2026-08-13 00:00', $w->earliestClockIn->format('Y-m-d H:i'));
        $this->assertNull($w->firstClockInDeadline);
        $this->assertSame('2026-08-13 19:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
        $this->assertSame('2026-08-13 19:00', $w->autoCloseTriggerAt->format('Y-m-d H:i'));
        $this->assertSame('2026-08-13 23:59', $w->absentAt->format('Y-m-d H:i'));
    }

    public function test_flexible_overtime_session_after_hours_complete(): void
    {
        $tz = 'Asia/Riyadh';
        $w = $this->calc->compute(new ShiftWindowInput(
            scheduledStart: CarbonImmutable::parse('2026-08-13 00:00:00', $tz),
            scheduledEnd: CarbonImmutable::parse('2026-08-13 23:59:59', $tz),
            clockIn: CarbonImmutable::parse('2026-08-13 20:00:00', $tz),
            maxOverTimeHours: 1.0,
            alreadyWorkedMinutesInPeriod: 480,
            overtimeFlags: new OvertimeFlags(afterFinishWork: true),
            timezone: $tz,
            requiredWorkMinutesOverride: 480,
            flexibleDay: true,
        ));

        $this->assertSame('2026-08-13 21:00', $w->expectedClockOutAt->format('Y-m-d H:i'));
        $this->assertSame('2026-08-13 23:59', $w->lastClockInAt->format('Y-m-d H:i'));
    }

    public function test_flexible_blocks_reclock_when_overtime_not_allowed(): void
    {
        $tz = 'Asia/Riyadh';
        $w = $this->calc->compute(new ShiftWindowInput(
            scheduledStart: CarbonImmutable::parse('2026-08-13 00:00:00', $tz),
            scheduledEnd: CarbonImmutable::parse('2026-08-13 23:59:59', $tz),
            clockIn: CarbonImmutable::parse('2026-08-13 20:00:00', $tz),
            alreadyWorkedMinutesInPeriod: 480,
            overtimeFlags: new OvertimeFlags(afterFinishWork: false),
            timezone: $tz,
            requiredWorkMinutesOverride: 480,
            flexibleDay: true,
        ));

        $this->assertSame('2026-08-13 00:00', $w->lastClockInAt->format('Y-m-d H:i'));
    }
}
