<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\OutZoneClockOutWarning;
use PHPUnit\Framework\TestCase;

class OutZoneClockOutWarningTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_payload_is_null_until_a_warning_exists(): void
    {
        $attendance = new Attendance();
        $attendance->id = 'att-1';
        $attendance->clock_out_time = null;

        $this->assertNull(OutZoneClockOutWarning::payload($attendance));
    }

    public function test_payload_asks_mobile_to_confirm_location(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:03:00', 'Asia/Riyadh'));

        $attendance = new Attendance();
        $attendance->id = 'att-1';
        $attendance->timezone = 'Asia/Riyadh';
        $attendance->clock_out_time = null;
        $attendance->out_zone_warning_at = '2026-08-31 12:00:00';

        $payload = OutZoneClockOutWarning::payload($attendance);

        $this->assertTrue($payload['needs_location_confirm']);
        $this->assertSame(OutZoneClockOutWarning::CONFIRM_PROMPT, $payload['message']);
        $this->assertSame(OutZoneClockOutWarning::VOICE_MESSAGE, $payload['voice_message']);
        $this->assertSame(120, $payload['remaining_seconds']);
        $this->assertFalse(OutZoneClockOutWarning::graceExpired($attendance));
    }

    public function test_grace_expires_after_five_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:05:00', 'Asia/Riyadh'));

        $attendance = new Attendance();
        $attendance->timezone = 'Asia/Riyadh';
        $attendance->out_zone_warning_at = '2026-08-31 12:00:00';

        $this->assertTrue(OutZoneClockOutWarning::graceExpired($attendance));
    }
}
