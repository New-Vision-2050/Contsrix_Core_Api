<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Calculator;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;
use Modules\Attendance\Domain\Calculator\SegmentedOvertimePolicy;
use Modules\Attendance\Domain\Calculator\StandardEarlyDeparturePolicy;
use Modules\Attendance\Domain\Calculator\StandardLatenessPolicy;
use Modules\Attendance\Domain\Calculator\StandardOvertimePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests — no DB, no container. Encodes the §9.4 worked-example table from
 * ATTENDANCE_RULES_V2_IMPLEMENTATION_PLAN.md verbatim.
 *
 * Canonical shift for every row: 12:00 → 21:00 (req = 540 min).
 * early_clock_in_minutes = 360 → work window opens 06:00.
 * extension_hours_shift = 2   → work window closes 23:00.
 * max_over_time = 8. No breaks.
 */
final class SegmentedOvertimePolicyTest extends TestCase
{
    private const S  = '2026-07-27 12:00';
    private const E  = '2026-07-27 21:00';

    private AttendanceCalculator $segmented;
    private AttendanceCalculator $standard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->segmented = new AttendanceCalculator(
            new StandardLatenessPolicy(),
            new SegmentedOvertimePolicy(),
            new StandardEarlyDeparturePolicy(),
        );
        $this->standard = new AttendanceCalculator(
            new StandardLatenessPolicy(),
            new StandardOvertimePolicy(),
            new StandardEarlyDeparturePolicy(),
        );
    }

    private function input(
        string $clockIn,
        string $clockOut,
        ?OvertimeFlags $flags,
        int $earlyWindowMinutes = 360,
        int $extensionMinutes = 120,
        float $maxOverTimeHours = 8.0,
        string $tz = 'Asia/Riyadh',
    ): CalculatorInput {
        return new CalculatorInput(
            scheduledStart: CarbonImmutable::parse(self::S, $tz),
            scheduledEnd: CarbonImmutable::parse(self::E, $tz),
            clockIn: CarbonImmutable::parse($clockIn, $tz),
            clockOut: CarbonImmutable::parse($clockOut, $tz),
            totalBreakMinutes: 0,
            maxOverTimeHours: $maxOverTimeHours,
            timezone: $tz,
            earlyWindowMinutes: $earlyWindowMinutes,
            extensionMinutes: $extensionMinutes,
            overtimeFlags: $flags,
            excludeOvertimeFromWorkHours: true,
        );
    }

    private function row(string $ci, string $co, ?OvertimeFlags $flags): array
    {
        $r = $this->segmented->calculate($this->input($ci, $co, $flags));

        return [
            'work'     => $r->totalWorkHours,
            'ot'       => $r->overtimeHours,
            'pre'      => $r->preShiftHours,
            'in'       => $r->inShiftHours,
            'post'     => $r->postShiftHours,
            'outside'  => $r->outsideWindowHours,
            'gross'    => round($r->totalWorkHours + $r->overtimeHours, 2),
        ];
    }

    // §9.4 row 1 — R1: early window is ordinary time; 6h before + 3h inside = 9h, no OT.
    public function test_row1_early_window_is_ordinary_time(): void
    {
        $r = $this->row('2026-07-27 06:00', '2026-07-27 15:00', new OvertimeFlags());

        $this->assertSame(9.0, $r['work']);
        $this->assertSame(0.0, $r['ot']);
        $this->assertSame(6.0, $r['pre']);
        $this->assertSame(3.0, $r['in']);
        $this->assertSame(9.0, $r['gross']);
    }

    // §9.4 row 2 — on time, exact shift. Unchanged from V1.
    public function test_row2_exact_shift(): void
    {
        $r = $this->row('2026-07-27 12:00', '2026-07-27 21:00', new OvertimeFlags());

        $this->assertSame(9.0, $r['work']);
        $this->assertSame(0.0, $r['ot']);
        $this->assertSame(9.0, $r['in']);
    }

    // §9.4 row 3 — flag on, 2h before the window is OT, 9 ordinary hours.
    public function test_row3_outer_pre_is_overtime(): void
    {
        $r = $this->row('2026-07-27 04:00', '2026-07-27 15:00', new OvertimeFlags(beforeEarlyClockIn: true));

        $this->assertSame(9.0, $r['work']);
        $this->assertSame(2.0, $r['ot']);
        $this->assertSame(2.0, $r['outside']);
        $this->assertSame(11.0, $r['gross']);
    }

    // §9.4 row 4 — R5/R6: OT credited even though required hours never finished; work reads 6.
    public function test_row4_overtime_before_finishing_required_hours(): void
    {
        $r = $this->row('2026-07-27 04:00', '2026-07-27 12:00', new OvertimeFlags(beforeEarlyClockIn: true));

        $this->assertSame(6.0, $r['work'], 'Working hours write 6, excluding overtime (R6)');
        $this->assertSame(2.0, $r['ot'],  'OT credited despite required hours unfinished (R5)');
        $this->assertSame(8.0, $r['gross']);
    }

    // §9.4 row 5 — flag on, 1h past the window is OT on top of 10 ordinary.
    public function test_row5_outer_post_is_overtime(): void
    {
        $r = $this->row('2026-07-27 13:00', '2026-07-28 00:00', new OvertimeFlags(afterExtension: true));

        $this->assertSame(10.0, $r['work']);
        $this->assertSame(1.0, $r['ot']);
        $this->assertSame(1.0, $r['outside']);
        $this->assertSame(11.0, $r['gross']);
    }

    // §9.4 row 6 — afterFinishWork: surplus over the 9 required hours is OT.
    public function test_row6_surplus_over_required_is_overtime(): void
    {
        $r = $this->row('2026-07-27 12:00', '2026-07-27 23:00', new OvertimeFlags(afterFinishWork: true));

        $this->assertSame(9.0, $r['work']);
        $this->assertSame(2.0, $r['ot']);
        $this->assertSame(11.0, $r['gross']);
    }

    // Composition: an early clock-in completes the required hours EARLY (anchor = actual
    // clock-in), so with afterFinishWork everything after that completion point is surplus OT.
    // This is the correct R1+A5 interaction; the plan's §9.4 row 7 was itself the miscalc.
    public function test_flags_compose_early_completion_then_surplus(): void
    {
        // CI 04:00, CO 22:00: 2h outerPre OT; ordinary starts 06:00 (window), 9h required
        // complete at 15:00; everything 15:00→22:00 is surplus = 7h OT. Cap 8 not hit (9<8? no).
        $r = $this->row(
            '2026-07-27 04:00',
            '2026-07-27 22:00',
            new OvertimeFlags(beforeEarlyClockIn: true, afterFinishWork: true),
        );
        $this->assertSame(8.0, $r['ot'],  'capped at max_over_time=8');
        $this->assertSame(10.0, $r['work'], 'gross 18h − 8h OT');
        $this->assertSame(18.0, $r['gross']);

        // Without the cap it would be 2h outerPre + 7h surplus = 9h OT, work = 9h exactly.
        $uncapped = $this->segmented->calculate($this->input(
            '2026-07-27 04:00',
            '2026-07-27 22:00',
            new OvertimeFlags(beforeEarlyClockIn: true, afterFinishWork: true),
            maxOverTimeHours: 99.0,
        ));
        $this->assertSame(9.0, $uncapped->overtimeHours);
        $this->assertSame(9.0, $uncapped->totalWorkHours, 'exactly the 9 required hours remain as work');
    }

    // afterFinishWork alone does NOT re-price the segment zones (no double counting).
    public function test_after_finish_work_does_not_double_count_segment_zones(): void
    {
        $noDouble = $this->row(
            '2026-07-27 04:00',
            '2026-07-27 15:00',  // exactly when the 9 required hours complete
            new OvertimeFlags(beforeEarlyClockIn: true, afterFinishWork: true),
        );
        $this->assertSame(9.0, $noDouble['work']);
        $this->assertSame(2.0, $noDouble['ot'], 'outerPre credited once; no surplus yet');
    }

    // §9.4 row 8 — early departure, no OT. Unchanged from V1.
    public function test_row8_early_departure(): void
    {
        $r = $this->row('2026-07-27 12:00', '2026-07-27 18:00', new OvertimeFlags());

        $this->assertSame(6.0, $r['work']);
        $this->assertSame(0.0, $r['ot']);
    }

    // Regression guard: all flags off ⇒ identical numbers to StandardOvertimePolicy.
    public function test_flags_off_matches_legacy_policy(): void
    {
        $legacy = $this->standard->calculate($this->input('2026-07-27 12:00', '2026-07-27 22:00', new OvertimeFlags()));
        $v2     = $this->row('2026-07-27 12:00', '2026-07-27 22:00', new OvertimeFlags());

        $this->assertSame($legacy->overtimeHours, $v2['ot'], 'OT identical to V1 when all flags off');
    }

    // max_over_time caps AFTER all flags are credited.
    public function test_max_over_time_caps_total(): void
    {
        $r = $this->segmented->calculate($this->input(
            '2026-07-27 04:00',
            '2026-07-27 22:00',
            new OvertimeFlags(beforeEarlyClockIn: true, afterFinishWork: true),
            maxOverTimeHours: 1.0,
        ));

        $this->assertSame(1.0, $r->overtimeHours, 'Cap applies after all flag credits');
    }

    // max_over_time = 0 → no overtime even with flags on.
    public function test_zero_max_over_time_blocks_overtime(): void
    {
        $r = $this->segmented->calculate($this->input(
            '2026-07-27 04:00',
            '2026-07-27 15:00',
            new OvertimeFlags(beforeEarlyClockIn: true),
            maxOverTimeHours: 0.0,
        ));

        $this->assertSame(0.0, $r->overtimeHours);
    }

    // Invariant: work + OT == gross presence for every row (property-style check over the table).
    public function test_work_plus_overtime_equals_gross(): void
    {
        $cases = [
            ['2026-07-27 06:00', '2026-07-27 15:00', new OvertimeFlags()],
            ['2026-07-27 04:00', '2026-07-27 22:00', new OvertimeFlags(beforeEarlyClockIn: true, afterFinishWork: true)],
            ['2026-07-27 13:00', '2026-07-28 00:00', new OvertimeFlags(afterExtension: true)],
        ];

        foreach ($cases as [$ci, $co, $flags]) {
            $r = $this->row($ci, $co, $flags);
            $gross = round($r['work'] + $r['ot'], 2);
            $this->assertEqualsWithDelta(
                $r['gross'],
                $gross,
                0.01,
                "total_work_hours + overtime_hours must equal gross presence for {$ci}→{$co}"
            );
        }
    }
}
