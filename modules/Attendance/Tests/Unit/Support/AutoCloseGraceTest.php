<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Support\AutoCloseGrace;
use PHPUnit\Framework\TestCase;

class AutoCloseGraceTest extends TestCase
{
    public function test_wait_follows_constraint_extension_minutes(): void
    {
        $this->assertSame(120, AutoCloseGrace::delayMinutes(0.0, 120));
    }

    public function test_zero_extension_closes_at_shift_end(): void
    {
        $this->assertSame(0, AutoCloseGrace::delayMinutes(0.0, 0));
    }

    public function test_stored_clock_out_is_expected_minus_extension(): void
    {
        $expected = \Carbon\CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, 120);

        $this->assertSame('2026-09-10 15:30:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_stored_clock_out_does_not_precede_clock_in(): void
    {
        $expected = \Carbon\CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $clockIn = \Carbon\CarbonImmutable::parse('2026-09-10 16:00:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, 120, $clockIn);

        $this->assertSame('2026-09-10 16:00:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_zero_extension_stores_expected_end(): void
    {
        $expected = \Carbon\CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, 0);

        $this->assertSame('2026-09-10 17:30:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_shorter_overtime_does_not_shrink_the_extension_wait(): void
    {
        $this->assertSame(120, AutoCloseGrace::delayMinutes(1.0, 120));
    }

    public function test_longer_overtime_keeps_the_overtime_window(): void
    {
        $this->assertSame(180, AutoCloseGrace::delayMinutes(3.0, 120));
    }
}
