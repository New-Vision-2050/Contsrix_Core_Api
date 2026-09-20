<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\CarbonImmutable;
use Modules\Attendance\Support\AutoClockOutRules;
use PHPUnit\Framework\TestCase;

class AutoClockOutRulesTest extends TestCase
{
    public function test_disabled_fires_at_expected_end_and_stores_it_unchanged(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');

        $this->assertSame(
            '2026-09-10 20:00:00',
            AutoClockOutRules::triggerAt($expected, 120, false)->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2026-09-10 20:00:00',
            AutoClockOutRules::storedClockOutAt($expected, null, 540, false)->format('Y-m-d H:i:s')
        );
    }

    public function test_enabled_waits_out_the_extension_window(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');

        // extension_minutes = 120 → auto clock-out fires at 22:00.
        $this->assertSame(
            '2026-09-10 22:00:00',
            AutoClockOutRules::triggerAt($expected, 120, true)->format('Y-m-d H:i:s')
        );
    }

    public function test_enabled_without_extension_still_fires_at_expected_end(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');

        $this->assertSame(
            '2026-09-10 20:00:00',
            AutoClockOutRules::triggerAt($expected, 0, true)->format('Y-m-d H:i:s')
        );
    }

    public function test_enabled_stores_expected_minus_25_percent_penalty(): void
    {
        // Production scenario: 9h shift (540 min) ending 20:00 → penalty 2h15m → 17:45.
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');
        $stored   = AutoClockOutRules::storedClockOutAt($expected, null, 540, true, 25);

        $this->assertSame('2026-09-10 17:45:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_penalty_percent_is_configurable(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');

        // 10% of 480 min = 48 min → 20:00 − 0:48 = 19:12.
        $this->assertSame(
            '2026-09-10 19:12:00',
            AutoClockOutRules::storedClockOutAt($expected, null, 480, true, 10)->format('Y-m-d H:i:s')
        );
    }

    public function test_zero_penalty_percent_stores_expected_end(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');

        $this->assertSame(
            '2026-09-10 20:00:00',
            AutoClockOutRules::storedClockOutAt($expected, null, 540, true, 0)->format('Y-m-d H:i:s')
        );
    }

    public function test_stored_clock_out_never_precedes_clock_in(): void
    {
        // 1h shift with 25% penalty = 15 min, but the employee clocked in 10 min
        // before the expected end — the stored time clamps to the clock-in.
        $expected = CarbonImmutable::parse('2026-09-10 20:00:00', 'Asia/Riyadh');
        $clockIn  = CarbonImmutable::parse('2026-09-10 19:50:00', 'Asia/Riyadh');
        $stored   = AutoClockOutRules::storedClockOutAt($expected, $clockIn, 540, true, 25);

        $this->assertSame('2026-09-10 19:50:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_penalty_minutes_rounds_to_whole_minutes(): void
    {
        // 25% of 545 min = 136.25 → 136.
        $this->assertSame(136, AutoClockOutRules::penaltyMinutes(545, 25));
        $this->assertSame(0, AutoClockOutRules::penaltyMinutes(0, 25));
        $this->assertSame(0, AutoClockOutRules::penaltyMinutes(540, 0));
    }
}
