<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Exceptions;

use RuntimeException;

final class EmployeeTaskException extends RuntimeException
{
    public static function notApproved(): self
    {
        return new self(__('The task must be in approved status to start.'), 422);
    }

    public static function notInProgress(): self
    {
        return new self(__('The task must be in progress to perform this action.'), 422);
    }

    public static function notPaused(): self
    {
        return new self(__('The task must be paused to resume.'), 422);
    }

    public static function notCancellable(): self
    {
        return new self(__('This task cannot be cancelled in its current status.'), 422);
    }

    public static function cannotCancel(): self
    {
        return new self(__('You can only cancel your own pending task requests.'), 403);
    }

    public static function procedureSettingNotConfigured(): self
    {
        return new self(__('No procedure setting is configured for employee task requests. Please contact your administrator.'), 422);
    }

    public static function pendingExtensionExists(): self
    {
        return new self(__('A pending extension request already exists for this task.'), 422);
    }

    public static function extensionNotAllowed(): self
    {
        return new self(__('Extensions can only be requested while the task is in progress or paused.'), 422);
    }

    public static function notFound(): self
    {
        return new self(__('Task request not found.'), 404);
    }

    public static function endRequiresLocation(): self
    {
        return new self(__('Ending a task requires latitude and longitude.'), 422);
    }

    public static function invalidStatus(string $current, string ...$expected): self
    {
        $expectedList = implode(', ', $expected);
        return new self("Task status is '{$current}', expected one of: {$expectedList}.", 422);
    }

    public static function notAuthorizedForStep(): self
    {
        return new self(__('You are not an authorized action-taker for the current approval step.'), 403);
    }

    public static function noProcedureStepsConfigured(): self
    {
        return new self(__('The procedure setting has no steps configured. Please contact your administrator.'), 422);
    }


    public static function extensionNotFound(): self
    {
        return new self(__('Extension request not found.'), 404);
    }

    public static function extensionAlreadyResolved(): self
    {
        return new self(__('This extension request has already been resolved.'), 422);
    }

    public static function cannotApproveExtension(string $reason = ''): self
    {
        $message = __('Cannot approve this extension request.');
        if ($reason) {
            $message .= " {$reason}";
        }
        return new self($message, 422);
    }

    public static function cannotRejectExtension(string $reason = ''): self
    {
        $message = __('Cannot reject this extension request.');
        if ($reason) {
            $message .= " {$reason}";
        }
        return new self($message, 422);
    }

    public static function extensionInvalidStatus(string $current, string ...$expected): self
    {
        $expectedList = implode(', ', $expected);
        return new self("Extension status is '{$current}', expected one of: {$expectedList}.", 422);
    }

    public static function taskForExtensionNotFound(string $taskId): self
    {
        return new self(__("The task associated with this extension (ID: {$taskId}) was not found."), 404);
    }

    public static function extensionApprovalSchedulingFailed(): self
    {
        return new self(__('Failed to schedule the extension approval job. Please contact your administrator.'), 500);
    }

    public static function approvalRequestNotAllowed(): self
    {
        return new self(__('A task approval request can only be submitted when the task is approved, in progress, paused, or completed.'), 422);
    }

    public static function pendingApprovalRequestExists(): self
    {
        return new self(__('A pending approval request already exists for this task.'), 422);
    }

    public static function approvalRequestNotFound(): self
    {
        return new self(__('Task approval request not found.'), 404);
    }

    public static function approvalRequestAlreadyResolved(): self
    {
        return new self(__('This approval request has already been resolved.'), 422);
    }

    public static function invalidProcedureSetting(): self
    {
        return new self(__('The selected internal procedure setting is invalid or does not belong to this task category.'), 422);
    }

    public static function pendingEndRequestExists(): self
    {
        return new self(__('A pending end request already exists for this task.'), 422);
    }

    public static function endRequestNotFound(): self
    {
        return new self(__('Task end request not found.'), 404);
    }

    public static function endRequestAlreadyResolved(): self
    {
        return new self(__('This end request has already been resolved.'), 422);
    }

    public static function notAllowedDuringShift(): self
    {
        return new self(__('This action is not allowed while you are within a work shift.'), 422);
    }

    public static function notAllowedOutsideShift(): self
    {
        return new self(__('This action is only allowed during an active work shift.'), 422);
    }

    public static function notAllowedOnHolidays(): self
    {
        return new self(__('This action is not allowed on holidays or non-working days.'), 422);
    }

    public static function cannotEndTaskOutsideLocation(): self
    {
        return new self(__('You must be within the task location to end this task.'), 422);
    }
}
