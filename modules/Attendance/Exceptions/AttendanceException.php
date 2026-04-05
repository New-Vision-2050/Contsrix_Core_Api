<?php

declare(strict_types=1);

namespace Modules\Attendance\Exceptions;

use App\Exceptions\CustomException;

class AttendanceException extends CustomException
{
    public static function alreadyClockedIn(): self
    {
        return new self('You are already clocked in. Please clock out first.', 400);
    }

    public static function notClockedIn(): self
    {
        return new self('You are not currently clocked in.', 400);
    }

    public static function alreadyClockedOut(): self
    {
        return new self('You have already clocked out for today.', 400);
    }

    public static function onBreak(): self
    {
        return new self('You are currently on break. Please end your break first.', 400);
    }

    public static function notOnBreak(): self
    {
        return new self('You are not currently on break.', 400);
    }

    public static function alreadyOnBreak(): self
    {
        return new self('You are already on break. Please end your current break first.', 400);
    }

    public static function attendanceNotFound(): self
    {
        return new self('Attendance record not found.', 404);
    }

    public static function cannotModifyPastAttendance(): self
    {
        return new self('Cannot modify attendance records from previous days.', 403);
    }

    public static function invalidClockOutTime(): self
    {
        return new self('Clock out time cannot be before clock in time.', 400);
    }

    public static function attendanceAlreadyApproved(): self
    {
        return new self('This attendance record has already been approved.', 400);
    }

    public static function unauthorizedToApprove(): self
    {
        return new self('You are not authorized to approve this attendance record.', 403);
    }

    public static function cannotDeleteApprovedAttendance(): self
    {
        return new self('Cannot delete approved attendance records.', 403);
    }

    /**
     * Thrown when a user tries to reject an attendance record that is already approved.
     */
    public static function cannotRejectApprovedAttendance(): self
    {
        // You can customize the message and HTTP status code as needed.
        // 400 (Bad Request) or 409 (Conflict) are good choices.
        return new self('Cannot reject an attendance record that has already been approved.', 409);
    }

    /**
     * Thrown when a user is not found.
     */
    public static function userNotFound(): self
    {
        return new self('User not found.', 404);
    }

    /**
     * Thrown when updating/deleting a constraint while linked employees still have an open shift.
     */
    public static function cannotModifyConstraintWithOpenAttendance(): self
    {
        return new self(
            'Cannot modify this attendance constraint while any linked employee is still clocked in (clock out is pending).',
            409
        );
    }
}
