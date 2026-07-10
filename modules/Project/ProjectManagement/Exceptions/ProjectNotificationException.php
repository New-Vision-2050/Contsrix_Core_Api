<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Exceptions;

use RuntimeException;

class ProjectNotificationException extends RuntimeException
{
    public static function notFound(string $id): self
    {
        return new self("Project notification [{$id}] not found.");
    }

    public static function statusNotFound(string $type): self
    {
        return new self("{$type} status not found or inactive.");
    }

    public static function cannotApprove(string $status): self
    {
        return new self("Cannot approve a notification with status [{$status}].");
    }

    public static function cannotReject(string $status): self
    {
        return new self("Cannot reject a notification with status [{$status}].");
    }

    public static function taskTypeNotFound(): self
    {
        return new self('Project notification EmployeeTaskType not found. Ensure the EmployeeTaskTypeSeeder has been run.');
    }

    public static function linkedTaskNotFound(string $id): self
    {
        return new self("Project notification [{$id}] has no linked employee task.");
    }

    public static function procedureNotAvailable(): self
    {
        return new self('The requested procedure is not currently available for this notification.');
    }

    public static function voiceRecipientHasNoPhone(): self
    {
        return new self('The assigned user has no phone number, so the voice notification cannot be sent.');
    }

    public static function shiftHandoverRequiresAnotherEmployeeLocationConfirmation(): self
    {
        return new self('Shift handover requires another assigned employee to confirm the location before ending.');
    }

    public static function userNotFound(): self
    {
        return new self('The selected user was not found.');
    }

    public static function validationFailed(string $message): self
    {
        return new self($message);
    }
}
