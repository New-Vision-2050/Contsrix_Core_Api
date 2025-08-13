<?php

declare(strict_types=1);

namespace Modules\Attendance\Exceptions;

use App\Exceptions\CustomException;

class LeaveRequestException extends CustomException
{
    public static function leaveRequestNotFound(): self
    {
        return new self('Leave request not found.', 404);
    }

    public static function insufficientLeaveBalance(): self
    {
        return new self('Insufficient leave balance for this request.', 400);
    }

    public static function overlappingLeaveRequest(): self
    {
        return new self('You already have a leave request for this period.', 400);
    }

    public static function invalidDateRange(): self
    {
        return new self('End date must be after start date.', 400);
    }

    public static function pastDateNotAllowed(): self
    {
        return new self('Cannot create leave request for past dates.', 400);
    }

    public static function leaveRequestAlreadyApproved(): self
    {
        return new self('This leave request has already been approved.', 400);
    }

    public static function leaveRequestAlreadyRejected(): self
    {
        return new self('This leave request has already been rejected.', 400);
    }

    public static function cannotCancelApprovedLeave(): self
    {
        return new self('Cannot cancel an approved leave request.', 400);
    }

    public static function unauthorizedToApprove(): self
    {
        return new self('You are not authorized to approve this leave request.', 403);
    }

    public static function cannotModifyAfterApproval(): self
    {
        return new self('Cannot modify leave request after approval.', 403);
    }

    public static function minimumNoticeRequired(int $days): self
    {
        return new self("This leave type requires at least {$days} days notice.", 400);
    }

    public static function exceedsMaximumDays(int $maxDays): self
    {
        return new self("This leave request exceeds the maximum allowed days ({$maxDays}).", 400);
    }

    public static function blackoutPeriod(): self
    {
        return new self('Leave requests are not allowed during this blackout period.', 400);
    }

    public static function leaveTypeNotActive(): self
    {
        return new self('This leave type is not currently active.', 400);
    }

    public static function attachmentRequired(): self
    {
        return new self('This leave type requires supporting documentation.', 400);
    }
}
