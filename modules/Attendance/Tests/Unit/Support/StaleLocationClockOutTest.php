<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\StaleLocationClockOut;
use PHPUnit\Framework\TestCase;

class StaleLocationClockOutTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_not_stale_when_clock_in_is_under_forty_five_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 12:44:00', 'Asia/Riyadh'));

        $attendance = $this->openShift('2026-09-02 12:00:00');

        $this->assertFalse(StaleLocationClockOut::isStale($attendance));
    }

    public function test_clock_in_without_later_pings_stales_at_forty_five_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 12:45:00', 'Asia/Riyadh'));

        $attendance = $this->openShift('2026-09-02 12:00:00');

        $this->assertTrue(StaleLocationClockOut::isStale($attendance));
        $this->assertSame(
            '2026-09-02 12:45:00',
            StaleLocationClockOut::closeAt($attendance)?->format('Y-m-d H:i:s')
        );
    }

    public function test_a_recent_tracking_ping_keeps_the_shift_open(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 13:10:00', 'Asia/Riyadh'));

        $attendance = $this->openShift('2026-09-02 12:00:00', [
            ['latitude' => 21.62, 'longitude' => 39.12, 'timestamp' => '2026-09-02 12:30:00'],
            ['latitude' => 21.62, 'longitude' => 39.12, 'timestamp' => '2026-09-02 13:00:00'],
        ]);

        $this->assertFalse(StaleLocationClockOut::isStale($attendance));
        $this->assertSame(
            '2026-09-02 13:00:00',
            StaleLocationClockOut::lastHeartbeatAt($attendance)?->format('Y-m-d H:i:s')
        );
    }

    public function test_stale_after_last_ping_plus_forty_five_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 13:45:00', 'Asia/Riyadh'));

        $attendance = $this->openShift('2026-09-02 12:00:00', [
            ['latitude' => 21.62, 'longitude' => 39.12, 'timestamp' => '2026-09-02 13:00:00'],
        ]);

        $this->assertTrue(StaleLocationClockOut::isStale($attendance));
        $this->assertSame(
            '2026-09-02 13:45:00',
            StaleLocationClockOut::closeAt($attendance)?->format('Y-m-d H:i:s')
        );
    }

    public function test_iso_tracking_timestamps_count_as_heartbeats(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 13:10:00', 'Asia/Riyadh'));

        $attendance = $this->openShift('2026-09-02 12:00:00', [
            ['latitude' => 21.62, 'longitude' => 39.12, 'timestamp' => '2026-09-02T13:00:00+03:00'],
        ]);

        $this->assertFalse(StaleLocationClockOut::isStale($attendance));
        $this->assertSame(
            '2026-09-02 13:00:00',
            StaleLocationClockOut::lastHeartbeatAt($attendance)?->format('Y-m-d H:i:s')
        );
    }

    public function test_already_clocked_out_is_not_stale(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 14:00:00', 'Asia/Riyadh'));

        $attendance = $this->openShift('2026-09-02 12:00:00');
        $attendance->clock_out_time = '2026-09-02 13:00:00';

        $this->assertFalse(StaleLocationClockOut::isStale($attendance));
    }

    /**
     * @param  list<array<string, mixed>>  $tracking
     */
    private function openShift(string $clockIn, array $tracking = []): Attendance
    {
        $attendance = new Attendance();
        $attendance->timezone = 'Asia/Riyadh';
        $attendance->clock_in_time = $clockIn;
        $attendance->clock_out_time = null;
        $attendance->location_tracking = $tracking;

        return $attendance;
    }
}
