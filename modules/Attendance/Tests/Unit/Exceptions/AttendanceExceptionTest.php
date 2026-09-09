<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Exceptions;

use Modules\Attendance\Exceptions\AttendanceException;
use PHPUnit\Framework\TestCase;

class AttendanceExceptionTest extends TestCase
{
    public function test_clock_in_blocked_uses_plain_message_string(): void
    {
        $e = AttendanceException::clockInBlocked([[
            'type' => 'clock_in_too_early',
            'severity' => 'blocking',
            'message' => 'Clock-in is too early. You can clock in from 08:00.',
            'details' => [
                'window' => ['earliest_clock_in' => '2026-08-03T08:00:00+03:00'],
            ],
        ]]);

        $this->assertSame(
            'Clock-in is too early. You can clock in from 08:00.',
            $e->getMessage()
        );
        $this->assertIsString($e->getMessage());
        $this->assertArrayNotHasKey('details', $e->getViolations()[0]);
    }

    public function test_clock_in_blocked_prefers_nested_specific_message(): void
    {
        $e = AttendanceException::clockInBlocked([[
            'constraint_type' => 'time',
            'severity' => 'high',
            'message' => 'Shift enforcement violation detected.',
            'details' => [
                'violations' => [
                    ['type' => 'late', 'message' => 'You are 15 minutes late.'],
                ],
            ],
        ]]);

        $this->assertSame('You are 15 minutes late.', $e->getMessage());
    }

    public function test_clock_in_blocked_normalizes_single_associative_violation(): void
    {
        $e = AttendanceException::clockInBlocked([
            'constraint_type' => 'location',
            'severity' => 'high',
            'message' => 'Your location is outside of all assigned work branches.',
            'details' => ['lat' => 1.0, 'lng' => 2.0],
        ]);

        $this->assertSame(
            'Your location is outside of all assigned work branches.',
            $e->getMessage()
        );
    }

    public function test_already_clocked_in_is_plain_string(): void
    {
        $e = AttendanceException::alreadyClockedIn();

        $this->assertSame(
            'You are already clocked in. Please clock out first.',
            $e->getMessage()
        );
    }

    public function test_no_profile_photo_is_a_422(): void
    {
        $e = AttendanceException::noProfilePhoto();

        $this->assertSame(422, $e->getStatusCode());
        $this->assertStringContainsString('No profile photo found', $e->getMessage());
    }

    public function test_face_not_matched_includes_similarity_in_message(): void
    {
        $e = AttendanceException::faceNotMatched(42.5);

        $this->assertSame(422, $e->getStatusCode());
        $this->assertStringContainsString('42.5%', $e->getMessage());
    }

    public function test_face_not_matched_without_similarity_still_readable(): void
    {
        $e = AttendanceException::faceNotMatched();

        $this->assertSame(422, $e->getStatusCode());
        $this->assertStringContainsString('does not match your profile photo', $e->getMessage());
    }

    public function test_face_not_detected_is_a_422(): void
    {
        $e = AttendanceException::faceNotDetected();

        $this->assertSame(422, $e->getStatusCode());
    }

    public function test_face_verification_misconfigured_is_a_500(): void
    {
        $e = AttendanceException::faceVerificationMisconfigured();

        $this->assertSame(500, $e->getStatusCode());
    }

    public function test_face_verification_failed_wraps_reason(): void
    {
        $e = AttendanceException::faceVerificationFailed('Rekognition timeout');

        $this->assertSame(502, $e->getStatusCode());
        $this->assertStringContainsString('Rekognition timeout', $e->getMessage());
    }
}
