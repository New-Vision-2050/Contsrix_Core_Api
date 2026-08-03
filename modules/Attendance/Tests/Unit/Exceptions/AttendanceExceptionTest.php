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
}
