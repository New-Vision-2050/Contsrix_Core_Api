<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Modules\Attendance\Services\AttendanceTimeCalculationService;
use PHPUnit\Framework\TestCase;

class AttendanceTimeCalculationServiceTest extends TestCase
{
    private AttendanceTimeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceTimeCalculationService();
    }

    public function test_example_short_shift_late_and_overtime(): void
    {
        $tz = 'Asia/Riyadh';
        $date = '2026-04-25';
        $clockIn = "{$date} 16:04:00";
        $clockOut = "{$date} 16:10:00";

        $r = $this->service->calculate(
            $clockIn,
            $clockOut,
            '16:00',
            '16:05',
            $tz
        );

        $this->assertSame(6, $r->workMinutes);
        $this->assertSame(4, $r->delayMinutes);
        $this->assertSame(5, $r->overtimeMinutes);
        $this->assertFalse($r->isEarlyDeparture);

        $this->assertSame('00:06', $this->service->formatHhMm($r->workMinutes));
        $this->assertSame('00:04', $this->service->formatHhMm($r->delayMinutes));
        $this->assertSame('00:05', $this->service->formatHhMm($r->overtimeMinutes));
    }

    public function test_early_check_in_zero_delay(): void
    {
        $tz = 'UTC';
        $d = '2026-01-10';
        $r = $this->service->calculate("{$d} 08:50:00", "{$d} 17:00:00", '09:00', '17:00', $tz);

        $this->assertSame(0, $r->delayMinutes);
        $this->assertSame(490, $r->workMinutes);
    }

    public function test_early_check_out_zero_overtime(): void
    {
        $tz = 'UTC';
        $d = '2026-01-10';
        $r = $this->service->calculate("{$d} 09:00:00", "{$d} 16:00:00", '09:00', '17:00', $tz);

        $this->assertSame(0, $r->overtimeMinutes);
        $this->assertTrue($r->isEarlyDeparture);
        $this->assertSame(60, $r->earlyDepartureMinutes);
    }

    public function test_same_time_zero_work(): void
    {
        $tz = 'UTC';
        $d = '2026-01-10 12:00:00';
        $r = $this->service->calculate($d, $d, '12:00', '12:00', $tz);

        $this->assertSame(0, $r->workMinutes);
        $this->assertSame('00:00', $this->service->formatHhMm($r->workMinutes));
    }
}
