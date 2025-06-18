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
}
