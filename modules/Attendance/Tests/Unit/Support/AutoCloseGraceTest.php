<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\CarbonImmutable;
use Modules\Attendance\Support\AutoCloseGrace;
use PHPUnit\Framework\TestCase;

class AutoCloseGraceTest extends TestCase
{
    public function test_disabled_closes_at_shift_end_with_no_wait(): void
    {
        $this->assertSame(0, AutoCloseGrace::delayMinutes(0.0, false));
        $this->assertSame(0, AutoCloseGrace::delayMinutes(3.0, false));
    }

    public function test_disabled_stores_expected_end(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, null, false);

        $this->assertSame('2026-09-10 17:30:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_enabled_zero_overtime_waits_two_hours(): void
    {
        $this->assertSame(120, AutoCloseGrace::delayMinutes(0.0, true));
    }

    public function test_enabled_shorter_overtime_does_not_shrink_the_two_hour_wait(): void
    {
        $this->assertSame(120, AutoCloseGrace::delayMinutes(1.0, true));
    }

    public function test_enabled_longer_overtime_keeps_the_overtime_window(): void
    {
        $this->assertSame(180, AutoCloseGrace::delayMinutes(3.0, true));
    }

    public function test_enabled_stores_expected_minus_two_hours(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, null, true);

        $this->assertSame('2026-09-10 15:30:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_enabled_stored_clock_out_does_not_precede_clock_in(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $clockIn = CarbonImmutable::parse('2026-09-10 16:00:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, $clockIn, true);

        $this->assertSame('2026-09-10 16:00:00', $stored->format('Y-m-d H:i:s'));
    }
}
