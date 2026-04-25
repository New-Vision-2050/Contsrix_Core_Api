<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Calculator;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Breaks\AutoBreakComputer;
use Modules\Attendance\Domain\Breaks\BreakSegment;
use PHPUnit\Framework\TestCase;

final class AutoBreakComputerTest extends TestCase
{
    private AutoBreakComputer $computer;

    protected function setUp(): void
    {
        $this->computer = new AutoBreakComputer();
    }

    public function test_returns_null_when_new_clock_in_before_previous_clock_out(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 09:45:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertNull($result);
    }

    public function test_returns_null_when_times_are_equal(): void
    {
        $time = CarbonImmutable::parse('2024-01-15 10:00:00');

        $result = $this->computer->computeGap($time, $time);

        $this->assertNull($result);
    }

    public function test_returns_break_segment_for_valid_gap(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 10:30:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertInstanceOf(BreakSegment::class, $result);
        $this->assertSame(30, $result->durationMinutes);
    }

    public function test_break_segment_start_and_end_match_inputs(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 12:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 12:45:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertTrue($result->start->equalTo($clockOut));
        $this->assertTrue($result->end->equalTo($clockIn));
    }

    public function test_source_is_always_auto_gap(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 10:15:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertSame('auto_gap', $result->source);
    }

    public function test_duration_is_correct_for_one_minute_gap(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 10:01:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertSame(1, $result->durationMinutes);
    }

    public function test_duration_is_correct_for_multi_hour_gap(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 11:30:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertSame(90, $result->durationMinutes);
    }

    public function test_duration_truncates_partial_minutes(): void
    {
        // 30 minutes and 45 seconds → truncated to 30
        $clockOut = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn  = CarbonImmutable::parse('2024-01-15 10:30:45');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertSame(30, $result->durationMinutes);
    }

    public function test_works_across_midnight(): void
    {
        $clockOut = CarbonImmutable::parse('2024-01-15 23:45:00');
        $clockIn  = CarbonImmutable::parse('2024-01-16 00:15:00');

        $result = $this->computer->computeGap($clockOut, $clockIn);

        $this->assertInstanceOf(BreakSegment::class, $result);
        $this->assertSame(30, $result->durationMinutes);
    }

    public function test_state_leak_guard_two_calls_produce_independent_results(): void
    {
        $computer = new AutoBreakComputer();

        $clockOut1 = CarbonImmutable::parse('2024-01-15 10:00:00');
        $clockIn1  = CarbonImmutable::parse('2024-01-15 10:20:00');

        $clockOut2 = CarbonImmutable::parse('2024-01-15 14:00:00');
        $clockIn2  = CarbonImmutable::parse('2024-01-15 14:45:00');

        $result1 = $computer->computeGap($clockOut1, $clockIn1);
        $result2 = $computer->computeGap($clockOut2, $clockIn2);

        $this->assertSame(20, $result1->durationMinutes);
        $this->assertSame(45, $result2->durationMinutes);

        // Confirm first result was not mutated by second call
        $this->assertTrue($result1->start->equalTo($clockOut1));
        $this->assertTrue($result2->start->equalTo($clockOut2));
    }
}
