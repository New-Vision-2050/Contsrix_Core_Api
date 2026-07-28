<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Calculator;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Domain\Calculator\StandardLatenessPolicy;
use PHPUnit\Framework\TestCase;

/**
 * R4 acceptance: lateness is strict from the shift start. 06:01 on a 06:00 shift is
 * 1 minute late. The grace period is dead and cannot suppress is_late.
 */
final class StrictLatenessPolicyTest extends TestCase
{
    private StandardLatenessPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StandardLatenessPolicy();
    }

    private function evaluate(string $start, ?string $clockIn, string $tz = 'Asia/Riyadh'): array
    {
        $input = new CalculatorInput(
            scheduledStart: CarbonImmutable::parse($start, $tz),
            scheduledEnd: CarbonImmutable::parse($start, $tz)->addHours(9),
            clockIn: $clockIn ? CarbonImmutable::parse($clockIn, $tz) : null,
            clockOut: null,
            totalBreakMinutes: 0,
            maxOverTimeHours: 0.0,
            timezone: $tz,
        );

        return $this->policy->evaluate($input);
    }

    // R4 acceptance test — 06:01 on a 06:00 start → late by 1 minute.
    public function test_one_minute_past_start_is_late(): void
    {
        [$isLate, $minutes] = $this->evaluate('2026-07-27 06:00', '2026-07-27 06:01');

        $this->assertTrue($isLate);
        $this->assertSame(1, $minutes);
    }

    public function test_exactly_at_start_is_not_late(): void
    {
        [$isLate] = $this->evaluate('2026-07-27 06:00', '2026-07-27 06:00');
        $this->assertFalse($isLate);
    }

    public function test_early_is_not_late(): void
    {
        [$isLate] = $this->evaluate('2026-07-27 06:00', '2026-07-27 05:30');
        $this->assertFalse($isLate);
    }

    public function test_no_clock_in_is_not_late(): void
    {
        [$isLate, $minutes] = $this->evaluate('2026-07-27 06:00', null);
        $this->assertFalse($isLate);
        $this->assertSame(0, $minutes);
    }

    public function test_minutes_measured_from_start(): void
    {
        [$isLate, $minutes] = $this->evaluate('2026-07-27 06:00', '2026-07-27 06:20');
        $this->assertTrue($isLate);
        $this->assertSame(20, $minutes);
    }

    // Compile-level guard: gracePeriodMinutes no longer exists on CalculatorInput (INV-25).
    public function test_calculator_input_has_no_grace_field(): void
    {
        $this->assertFalse(
            property_exists(CalculatorInput::class, 'gracePeriodMinutes'),
            'gracePeriodMinutes must not exist — grace cannot creep back into is_late'
        );
    }
}
