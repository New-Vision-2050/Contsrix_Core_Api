<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Notifications\OutZoneClockOutWarningNotification;
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
        $this->assertSame('att-1', $payload['attendance_id']);
        $this->assertArrayNotHasKey('clock_out_at', $payload);
        $this->assertSame(3, OutZoneClockOutWarning::NOTIFICATION_COUNT);
    }

    public function test_grace_expires_after_five_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 12:05:00', 'Asia/Riyadh'));

        $attendance = new Attendance();
        $attendance->timezone = 'Asia/Riyadh';
        $attendance->out_zone_warning_at = '2026-08-31 12:00:00';

        $this->assertTrue(OutZoneClockOutWarning::graceExpired($attendance));
    }

    public function test_voice_notification_is_not_queued(): void
    {
        $this->assertFalse(
            is_subclass_of(OutZoneClockOutWarningNotification::class, ShouldQueue::class),
            'Out-zone voice must dial in-request like project site-status calls'
        );
    }

    public function test_voice_phone_adds_country_code_and_avoids_double_prefix(): void
    {
        $notification = new OutZoneClockOutWarningNotification();

        $user = new \stdClass();
        $user->phone = '05 1234-5678';
        $user->phone_code = '966';
        $this->assertSame('+966512345678', $notification->buildInternationalPhoneNumber($user));

        $user->phone = '966512345678';
        $user->phone_code = '966';
        $this->assertSame('+966512345678', $notification->buildInternationalPhoneNumber($user));
    }
}
