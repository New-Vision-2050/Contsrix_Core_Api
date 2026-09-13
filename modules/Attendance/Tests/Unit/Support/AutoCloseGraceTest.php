<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\CarbonImmutable;
use Modules\Attendance\Support\AutoCloseGrace;
use PHPUnit\Framework\TestCase;

class AutoCloseGraceTest extends TestCase
{
    public function test_zero_overtime_still_waits_two_hours(): void
    {
        $this->assertSame(120, AutoCloseGrace::delayMinutes(0.0));
    }

    public function test_shorter_overtime_does_not_shrink_the_two_hour_wait(): void
    {
        $this->assertSame(120, AutoCloseGrace::delayMinutes(1.0));
    }

    public function test_longer_overtime_keeps_the_overtime_window(): void
    {
        $this->assertSame(180, AutoCloseGrace::delayMinutes(3.0));
    }

    public function test_stored_clock_out_is_expected_minus_two_hours(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected);

        $this->assertSame('2026-09-10 15:30:00', $stored->format('Y-m-d H:i:s'));
    }

    public function test_stored_clock_out_does_not_precede_clock_in(): void
    {
        $expected = CarbonImmutable::parse('2026-09-10 17:30:00', 'Asia/Riyadh');
        $clockIn = CarbonImmutable::parse('2026-09-10 16:00:00', 'Asia/Riyadh');
        $stored = AutoCloseGrace::storedClockOutAt($expected, $clockIn);

        $this->assertSame('2026-09-10 16:00:00', $stored->format('Y-m-d H:i:s'));
    }
}
