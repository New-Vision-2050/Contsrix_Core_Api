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
}
